<?php
/**
 * Qkua 子主题 – 样式稳定 + 前台投稿写入七夸字段 + 后台设置
 * 关键点：
 * - 只按“固定路径”加载父主题 CSS，避免丢样式出现紫色/下划线/错位
 * - 不在前端注入任何 CSS/JS
 * - 后台：设置 → Qkua 子主题设置
 * - 保存文章时：锁分类、过滤标签、写入七夸 video/下载/权限字段
 */

if (!defined('ABSPATH')) exit;

define('QKUA_CHILD_OPT', 'qkua_child_settings');

/* ----------------------------------------------------------------
 * A. 样式加载 —— 按路径确保父主题样式一定加载，然后才加载子主题
 * ---------------------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {
    // 父主题主样式
    wp_enqueue_style(
        'qkua-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        null
    );
    // 父主题前端样式
    wp_enqueue_style(
        'qkua-parent-front',
        get_template_directory_uri() . '/Assets/fontend/style.css',
        ['qkua-parent-style'],
        null
    );
    // 父主题移动端样式
    wp_enqueue_style(
        'qkua-parent-mobile',
        get_template_directory_uri() . '/Assets/fontend/mobile.css',
        ['qkua-parent-front'],
        null
    );
    // （可选）写作页样式；不确定可注释掉
    // wp_enqueue_style(
    //     'qkua-parent-write',
    //     get_template_directory_uri() . '/Assets/fontend/write.css',
    //     ['qkua-parent-mobile'],
    //     null
    // );

    // 子主题样式最后加载（仅很小覆盖，不改布局）
    wp_enqueue_style(
        'qkua-child-style',
        get_stylesheet_uri(),
        ['qkua-parent-mobile'],
        '1.0.4'
    );
}, 20);

/* ----------------------------------------------------------------
 * B. 后台设置
 * ---------------------------------------------------------------- */
function qkua_child_default_settings() {
    return [
        'force_category'   => 1,                 // 锁定分类
        'default_category' => 4,                 // 默认分类ID（你站“积分免费下载”）
        'force_download'   => 1,                 // 锁定下载权限
        'download_rule'    => 'lv|credit=500',   // 七夸权限规则（示例）
        'tag_blocklist'    => '裸露,不雅,成人,色情', // 标签屏蔽
        // 前台表单字段名（有则读取；没有也不影响保存）
        'video_field'      => 'steam_video_url',
        'download_field'   => 'steam_download_url',
        'download_title'   => 'steam_download_title'
    ];
}
function qkua_child_get_settings() {
    $opt = get_option(QKUA_CHILD_OPT, []);
    if (!is_array($opt)) $opt = [];
    return wp_parse_args($opt, qkua_child_default_settings());
}

add_action('admin_menu', function () {
    add_options_page(
        'Qkua 子主题设置',
        'Qkua 子主题设置',
        'manage_options',
        'qkua-child-settings',
        function(){
            ?>
            <div class="wrap">
                <h1>Qkua 子主题设置</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('qkua_child_settings_group');
                    do_settings_sections('qkua-child-settings');
                    submit_button();
                    ?>
                </form>
                <p style="margin-top:12px;">
                    保存文章时会自动写入七夸字段：
                    <code>qk_single_post_video_group</code>,
                    <code>qk_single_post_download_open</code>,
                    <code>qk_single_post_download_group</code>,
                    <code>qk_post_content_hide_role</code>
                    （若为积分，额外写 <code>qk_post_price</code>）。
                </p>
            </div>
            <?php
        }
    );
});

add_action('admin_init', function () {
    register_setting('qkua_child_settings_group', QKUA_CHILD_OPT, 'qkua_child_sanitize');

    add_settings_section('qkua_child_main', '基础设置', '__return_false', 'qkua-child-settings');

    add_settings_field('force_category', '锁定分类', function(){
        $s = qkua_child_get_settings();
        echo '<label><input type="checkbox" name="'.QKUA_CHILD_OPT.'[force_category]" value="1" '.checked($s['force_category'],1,false).'> 开启</label>';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('default_category', '默认分类ID', function(){
        $s = qkua_child_get_settings();
        echo '<input type="number" name="'.QKUA_CHILD_OPT.'[default_category]" value="'.esc_attr($s['default_category']).'" class="regular-text">';
        echo '<p class="description">填写后台分类ID（如“积分免费下载”）</p>';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('force_download', '锁定下载权限', function(){
        $s = qkua_child_get_settings();
        echo '<label><input type="checkbox" name="'.QKUA_CHILD_OPT.'[force_download]" value="1" '.checked($s['force_download'],1,false).'> 开启</label>';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('download_rule', '默认下载权限规则', function(){
        $s = qkua_child_get_settings();
        echo '<input type="text" name="'.QKUA_CHILD_OPT.'[download_rule]" value="'.esc_attr($s['download_rule']).'" class="regular-text">';
        echo '<p class="description">示例：<code>lv|credit=500</code> / <code>login</code> / <code>comment</code> / <code>all|free</code> / <code>money=10</code> / <code>vip1|free</code></p>';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('tag_blocklist', '标签屏蔽词', function(){
        $s = qkua_child_get_settings();
        echo '<input type="text" name="'.QKUA_CHILD_OPT.'[tag_blocklist]" value="'.esc_attr($s['tag_blocklist']).'" class="regular-text">';
        echo '<p class="description">逗号分隔，如：裸露,不雅,成人</p>';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('video_field', '前台“视频地址”字段名', function(){
        $s = qkua_child_get_settings();
        echo '<input type="text" name="'.QKUA_CHILD_OPT.'[video_field]" value="'.esc_attr($s['video_field']).'" class="regular-text">';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('download_field', '前台“下载地址”字段名', function(){
        $s = qkua_child_get_settings();
        echo '<input type="text" name="'.QKUA_CHILD_OPT.'[download_field]" value="'.esc_attr($s['download_field']).'" class="regular-text">';
    }, 'qkua-child-settings', 'qkua_child_main');

    add_settings_field('download_title', '前台“下载标题”字段名（可选）', function(){
        $s = qkua_child_get_settings();
        echo '<input type="text" name="'.QKUA_CHILD_OPT.'[download_title]" value="'.esc_attr($s['download_title']).'" class="regular-text">';
    }, 'qkua-child-settings', 'qkua_child_main');
});

function qkua_child_sanitize($in) {
    $d = qkua_child_default_settings();
    $out = [];
    $out['force_category']   = empty($in['force_category']) ? 0 : 1;
    $out['default_category'] = isset($in['default_category']) ? max(0, intval($in['default_category'])) : $d['default_category'];
    $out['force_download']   = empty($in['force_download']) ? 0 : 1;
    $out['download_rule']    = isset($in['download_rule']) ? sanitize_text_field($in['download_rule']) : $d['download_rule'];
    $out['tag_blocklist']    = isset($in['tag_blocklist']) ? sanitize_text_field($in['tag_blocklist']) : $d['tag_blocklist'];
    $out['video_field']      = isset($in['video_field']) ? sanitize_key($in['video_field']) : $d['video_field'];
    $out['download_field']   = isset($in['download_field']) ? sanitize_key($in['download_field']) : $d['download_field'];
    $out['download_title']   = isset($in['download_title']) ? sanitize_key($in['download_title']) : $d['download_title'];
    return $out;
}

/* ----------------------------------------------------------------
 * C. 工具：多字节包含判断
 * ---------------------------------------------------------------- */
function qkua_child_contains($haystack, $needle) {
    if ($needle === '') return false;
    if (function_exists('mb_stripos')) {
        return mb_stripos($haystack, $needle) !== false;
    }
    return stripos($haystack, $needle) !== false;
}

/* ----------------------------------------------------------------
 * D. 保存文章：锁分类 / 过滤标签 / 写入七夸字段
 * ---------------------------------------------------------------- */
add_action('save_post', function($post_id, $post, $update){
    if ($post->post_type !== 'post') return;
    if (wp_is_post_revision($post_id) || $post->post_status === 'auto-draft') return;

    $s = qkua_child_get_settings();

    // 1) 锁定分类
    if ($s['force_category'] && $s['default_category']) {
        wp_set_post_terms($post_id, [intval($s['default_category'])], 'category', false);
    }

    // 2) 标签过滤（屏蔽词）
    $block = array_filter(array_map('trim', explode(',', $s['tag_blocklist'])));
    if (!empty($block)) {
        // 收集已有 + 新提交的标签
        $current = wp_get_post_terms($post_id, 'post_tag', ['fields' => 'names']);
        if (is_wp_error($current) || !is_array($current)) $current = [];

        $incoming = [];
        // 支持多种字段名：steam_tags / tags / post_tags / tax_input[post_tag]
        $candidates = [];
        if (isset($_POST['steam_tags']))   $candidates[] = wp_unslash($_POST['steam_tags']);
        if (isset($_POST['tags']))         $candidates[] = wp_unslash($_POST['tags']);
        if (isset($_POST['post_tags']))    $candidates[] = wp_unslash($_POST['post_tags']);
        if (isset($_POST['tax_input']['post_tag'])) $candidates[] = wp_unslash($_POST['tax_input']['post_tag']);

        foreach ($candidates as $cand) {
            if (is_array($cand)) {
                foreach ($cand as $t) $incoming[] = trim($t);
            } else {
                $arr = preg_split('/[,，\s]+/u', (string)$cand);
                foreach ($arr as $t) if ($t !== '') $incoming[] = $t;
            }
        }

        $all = array_unique(array_filter(array_merge($current, $incoming)));
        $keep = [];
        foreach ($all as $t) {
            $bad = false;
            foreach ($block as $b) {
                if ($b !== '' && qkua_child_contains($t, $b)) { $bad = true; break; }
            }
            if (!$bad) $keep[] = $t;
        }
        wp_set_post_terms($post_id, $keep, 'post_tag', false);
    }

    // 3) 取前台提交的视频/下载字段（容错多个键名）
    $video_url = '';
    $download_url = '';
    $download_title = '学习版/清单';

    $video_keys    = array_unique(array_filter([$s['video_field'], 'video_url', 'steam_video_url']));
    $download_keys = array_unique(array_filter([$s['download_field'], 'download_url', 'steam_download_url']));
    $title_keys    = array_unique(array_filter([$s['download_title'], 'download_title', 'steam_download_title']));

    foreach ($video_keys as $k) {
        if (isset($_POST[$k]) && trim(wp_unslash($_POST[$k])) !== '') {
            $video_url = trim(wp_unslash($_POST[$k]));
            break;
        }
    }
    foreach ($download_keys as $k) {
        if (isset($_POST[$k]) && trim(wp_unslash($_POST[$k])) !== '') {
            $download_url = trim(wp_unslash($_POST[$k]));
            break;
        }
    }
    foreach ($title_keys as $k) {
        if (isset($_POST[$k]) && trim(wp_unslash($_POST[$k])) !== '') {
            $download_title = sanitize_text_field(wp_unslash($_POST[$k]));
            break;
        }
    }

    // 4) 写入七夸：视频分组
    if (!empty($video_url)) {
        $video_group = [
            [
                'title'  => '在线播放',
                'url'    => esc_url_raw($video_url),
                'player' => '',
                'parse'  => '',
                'thumb'  => '',
                'pwd'    => '',
            ],
        ];
        update_post_meta($post_id, 'qk_single_post_video_group', $video_group);
    }

    // 5) 写入七夸：下载开关 + 下载分组 + 权限 + 隐藏内容权限
    if (!empty($download_url)) {
        update_post_meta($post_id, 'qk_single_post_download_open', 1);

        $rule = $s['force_download'] ? $s['download_rule'] : 'all|free';
        $role = 'free';
        $credit = '';

        if (stripos($rule, 'credit=') !== false) {
            if (preg_match('/credit=([0-9]+)/i', $rule, $m)) {
                $credit = $m[1];
            }
            $role = 'credit';
            if ($credit !== '') update_post_meta($post_id, 'qk_post_price', intval($credit));
        } elseif (preg_match('/^money=([0-9]+)/i', $rule)) {
            $role = $rule; // money=10 等，原样给 role（如主题需要可另外写价格）
        } else {
            $role = $rule; // login / comment / password / all|free / vip1|free ...
        }

        $dl_group = [
            [
                'title'  => $download_title,
                'url'    => esc_url_raw($download_url),
                'role'   => $role,
                'credit' => $credit,
                'thumb'  => '',
                'pwd'    => '',
            ],
        ];
        update_post_meta($post_id, 'qk_single_post_download_group', $dl_group);

        // 同步“内容隐藏权限”（你要求：锁死 500 积分或后台设置值）
        update_post_meta($post_id, 'qk_post_content_hide_role', $rule);
    }

}, 20, 3);

// === 1. 把 Steam 导入按钮移动到标题输入框下方 ===
add_action('wp_footer', function () {
    if (is_page() || is_single()) return; // 仅在前台投稿页执行
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const steamBtn = document.getElementById('steam-import-fallback');
        const titleField = document.querySelector('input[name="post_title"]'); 
        if (steamBtn && titleField) {
            steamBtn.style.position = 'static';
            steamBtn.style.marginTop = '10px';
            steamBtn.style.background = '#3178f2';
            steamBtn.style.color = '#fff';
            steamBtn.style.padding = '5px 10px';
            titleField.insertAdjacentElement('afterend', steamBtn);
        }
    });
    </script>
    <?php
});

// === 2. 新增前台投稿字段：视频地址 & 下载地址 ===
add_action('add_meta_boxes', function () {
    add_meta_box('extra_fields', '扩展字段（七夸适配）', function ($post) {
        $video = get_post_meta($post->ID, 'qk_single_post_video_group', true);
        $download = get_post_meta($post->ID, 'qk_single_post_download_group', true);
        ?>
        <p><label>视频地址：</label>
        <input type="text" style="width:100%" name="extra_video" value="<?php echo esc_attr($video); ?>"></p>
        <p><label>下载地址：</label>
        <input type="text" style="width:100%" name="extra_download" value="<?php echo esc_attr($download); ?>"></p>
        <?php
    }, 'post', 'normal', 'high');
});

add_action('save_post', function ($post_id) {
    if (isset($_POST['extra_video'])) {
        update_post_meta($post_id, 'qk_single_post_video_group', sanitize_text_field($_POST['extra_video']));
    }
    if (isset($_POST['extra_download'])) {
        update_post_meta($post_id, 'qk_single_post_download_group', sanitize_text_field($_POST['extra_download']));
    }
});

// === 3. 后台子主题设置页面（默认下载权限） ===
add_action('admin_menu', function () {
    add_menu_page('投稿默认设置', '投稿默认设置', 'manage_options', 'child-theme-settings', 'child_theme_settings_page');
});

function child_theme_settings_page() {
    if (isset($_POST['default_permission'])) {
        update_option('child_default_permission', sanitize_text_field($_POST['default_permission']));
        echo '<div class="updated"><p>保存成功</p></div>';
    }
    $default = get_option('child_default_permission', 'lv|credit=500');
    ?>
    <div class="wrap"><h1>投稿默认设置</h1>
        <form method="post">
            <p>默认下载权限（例如 lv|credit=500）：</p>
            <input type="text" name="default_permission" value="<?php echo esc_attr($default); ?>" style="width:400px">
            <p><input type="submit" class="button-primary" value="保存"></p>
        </form>
    </div>
    <?php
}

// 在保存文章时自动写入默认权限
add_action('save_post', function ($post_id) {
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    $default = get_option('child_default_permission', 'lv|credit=500');
    if (!get_post_meta($post_id, 'qk_single_post_download_group', true)) {
        update_post_meta($post_id, 'qk_single_post_download_group', $default);
    }
});
