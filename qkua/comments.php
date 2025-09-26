<?php
if ( post_password_required() ) {
    return;
}

$post_id = get_the_id();
$user_id = get_current_user_id();
$paged   = get_query_var('cpage') ? get_query_var('cpage') : 1;

/**
 * 将主题设置的开关/字段做统一兼容：
 * - 对于 switcher（可能返回 bool 或 ['enabled'=>bool]），统一转为 bool
 * - 对于可能返回复杂结构的字段，提供数组兜底
 */
if (!function_exists('qk_bool_option')) {
    function qk_bool_option($val) {
        if (is_array($val)) {
            // 常见：['enabled' => true/false] 或 ['enabled'=>'on']
            if (array_key_exists('enabled', $val)) {
                return (bool)$val['enabled'];
            }
            // 有些主题把开关存在 ['enabled'] 以外的键，尝试取第一个布尔值
            foreach ($val as $v) {
                if (is_bool($v) || $v === '0' || $v === '1' || $v === 0 || $v === 1 || $v === 'on' || $v === 'off') {
                    return filter_var($v, FILTER_VALIDATE_BOOLEAN);
                }
            }
            return !empty($val); // 最后兜底：数组非空视为 true
        }
        // 字符串 'on'/'off' 或 '0'/'1'
        if (is_string($val)) {
            if ($val === 'on') return true;
            if ($val === 'off') return false;
        }
        return (bool)$val;
    }
}

if (!function_exists('qk_array_option')) {
    function qk_array_option($val, $key_candidates = array()) {
        // 优先从指定候选键中挑一个数组出来
        if (is_array($val)) {
            foreach ($key_candidates as $k) {
                if (isset($val[$k]) && is_array($val[$k])) {
                    return $val[$k];
                }
            }
            // 如果本身就是数组且没有候选键，直接返回
            return $val;
        }
        return array();
    }
}

$comment_count = get_comments_number();
$comment_open  = comments_open();

// comment_close 可能是 switcher，做布尔兼容
$comment_close = qk_bool_option(qk_get_option('comment_close'));
if (!$comment_open || $comment_close) return;

// 加载类型 分页按钮或自动加载
$type            = qk_get_option('comment_pagination_type');
$placeholder     = qk_get_option('comment_placeholder');
$submit_text     = qk_get_option('comment_submit_text');
$comment_title   = qk_get_option('comment_title');

// 开关类统一布尔化
$show_order          = qk_bool_option(qk_get_option('comment_show_order'));
$show_orderby_author = qk_bool_option(qk_get_option('comment_show_orderby_author'));

// 排序选项可能在不同键里，这里做兼容：
// 常见情况：qk_get_option('comment_orderby') 返回
//  - 直接是 ['hot'=>'最热','new'=>'最新'] 这样的数组；或
//  - ['enabled'=>['hot'=>'最热','new'=>'最新']]；或其他键名
$comment_orderby_raw = qk_get_option('comment_orderby');
$comment_orderby     = qk_array_option($comment_orderby_raw, array('enabled', 'items', 'options'));

// 表情开关（可能为 bool 或 ['enabled'=>bool]）
$comment_use_smiles = qk_bool_option(qk_get_option('comment_use_smiles'));

$comment_order = get_option('comment_order');
// print_r(get_option('comments_per_page',10));
// print_r(get_option('comment_orde','asc'));
// print_r(get_permalink());
// print_r(get_option('comment_order'));
?>

<div class="comments-wrap tab-pane">
    <div class="comments-hred widget-title"><?php echo esc_html($comment_title); ?>（<?php echo (int)$comment_count; ?>）</div>
    <!--#评论wrap-->
    <div id="comments" class="comments-area box">
        <div id="respond" class="comment-send" ref="respond">
            <div class="comment-user-avatar">
                <?php echo 
                    qk_get_avatar(array(
                        'src' => get_avatar_url($user_id, array('size' => 43)),
                        'alt' => $user_id ? get_the_author_meta('display_name', $user_id) : '游客的头像'
                    )); 
                ?>
            </div>
            <div class="comment-textarea-container">
                <textarea id="textarea" ref="textarea" placeholder="<?php echo esc_attr($placeholder); ?>"></textarea>
                <div class="comment-button">
                    <div class="comment-botton-left">
                        <?php if ($comment_use_smiles) { ?>
                            <span class="comment-emoji" @click.stop="showEmoji = !showEmoji"><i class="ri-emotion-happy-line"></i>表情</span>
                            <qk-emoji v-model="showEmoji" @emoji-click="handleClick"></qk-emoji>
                        <?php } ?>
                    </div>
                    <button type="submit" class="comment-submit" @click="submit()"><?php echo esc_html($submit_text); ?></button>
                </div>
            </div>
        </div>

        <?php if ($show_order) { ?>
        <div class="comment-orderby">
            <div class="comment-orderby-left">
                <?php if ($show_orderby_author) { ?>
                    <span :class="{active:param.author == false}" @click="tabClick(false)">全部评论</span>
                    <span :class="{active:param.author}" @click="tabClick(true)">只看作者</span>
                <?php } ?>
            </div>
            <div class="comment-orderby-rigth">
                <?php
                // 只有在是“键值数组”时才渲染排序项
                if (is_array($comment_orderby) && !empty($comment_orderby)) {
                    foreach ($comment_orderby as $key => $value) {
                        $k = esc_attr($key);
                        $v = esc_html($value);
                        echo '<span :class="{active:param.orderby == \''.$k.'\'}" @click="changeOrder(\''.$k.'\')">'.$v.'</span>';
                    }
                }
                ?>
            </div>
        </div>
        <?php } ?>

        <div class="comments-area-content">
            <ol class="comment-list" ref="commentList">
                <?php
                    if (have_comments()) {
                        wp_list_comments(
                            array(
                                'type'          => 'comment',
                                'callback'      => array('Qk\Modules\Common\Comment','comment_callback'),
                                'end-callback'  => array('Qk\Modules\Common\Comment','comment_callback_end'),
                                'max_depth'     => 2,
                            )
                        );
                    } else {
                        echo '<li class="comment-list-empty" ref="commentEmpty">暂时还没有评论哦</li>';
                    }
                ?>
            </ol>
            <!--#评论List-->
        </div>
        <!--#评论内容-->
        <?php echo qk_ajax_pagenav( array( 'paged' => $paged, 'pages' => get_comment_pages_count() ), 'comment', $type ); ?>
    </div>
</div>
<!--#评论wrap-->
