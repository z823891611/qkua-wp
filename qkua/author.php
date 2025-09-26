<?php
use Qk\Modules\Common\User;

$user_page = get_query_var('qk_user_page')?:'index';
$user_id =  get_query_var('author'); //get_queried_object_id() get_queried_object()->ID
$link = get_author_posts_url($user_id);

get_header();

$user_data = User::get_user_public_data($user_id,true); //get_queried_object() get_author_info($user_id)

if(!isset($user_data['name'])){
    wp_safe_redirect(home_url().'/404');
    exit;
}

$stats_count = User::get_user_stats_count($user_id);
$followers_count = User::get_user_followers_stats_count($user_id); //获取关注数计数

wp_localize_script( 'qk-author', 'qk_author',array(
    'author_id'=>$user_id,
    'is_self' => $user_id == get_current_user_id() ? true : false //是否是自己
));

// if (function_exists('dynamic_sidebar')) {
//     echo '<div class="container fluid-widget">';
//     dynamic_sidebar('all_top_fluid');
//     dynamic_sidebar('single_top_fluid');
//     echo '</div>';
// }
//print_r($user_page);

?>

<div id="author" class="author">
    <div class="author-header">
        <div class="mask-wrapper" style="background-image: url(<?php echo $user_data['cover'] ?>);">
            <div class="wrapper">
                <div class="author-container">
                    <div class="avatar-bg"></div>
                    <div class="author-info">
                        <div class="author-profile">
                            <div class="left">
                                <?php echo $user_data['avatar_html']; ?>
                            </div>
                            <div class="right">
                                <div class="user-info">
                                    <?php echo $user_data['name_html']?>
                                    <div class="desc">
                                        <?php if(qk_get_option('verify_open')){?>
                                        
                                            <?php if(empty($user_data['verify'])){?>
                                                <a href="<?php echo qk_get_custom_page_url('verify'); ?>">
                                                    暂未认证，去认证 <i class="ri-arrow-right-s-line"></i>
                                                </a>
                                            <?php }else{?>
                                                <div class="user-auth">
                                                    <span><?php echo $user_data['verify']['name'].'：'.$user_data['verify']['title']; ?></span>
                                                </div>
                                            <?php }?>
                                        <?php }?>
                                    </div>
                                </div>
                                <div class="statistics">
                                    <a href="<?php echo $link.'/fans/follow'; ?>" class="<?php echo ($user_page == 'fans/follow' ? 'active' : '');?>">
                                        <span class="text">关注</span>
                                        <span class="num"><?php echo $followers_count['follow'] ?></span>
                                    </a>
                                    <a href="<?php echo $link.'/fans/fans'; ?>" class="<?php echo ($user_page == 'fans/fans' ? 'active' : '');?>">
                                        <span class="text">粉丝</span>
                                        <span class="num"><?php echo $followers_count['fans'] ?></span>
                                    </a>
                                    <div title="视频、动态、专栏累计获赞 <?php echo $stats_count['posts_like_count'] ?>">
                                        <span class="text">获赞</span>
                                        <span class="num"><?php echo $stats_count['posts_like_count'] ?></span></div>
                                    <div title="截止现在，阅读数总计为 <?php echo $stats_count['posts_views_count'] ?>">
                                        <span class="text">阅读</span>
                                        <span class="num"><?php echo $stats_count['posts_views_count'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="author-profile-bottom">
                            <div class="left-desc">
                                <?php if(!empty($user_data['ip_location'])): ?>
                                    <div class="list">
                                        <div class="tag-item">
                                            <i class="ri-map-pin-fill"></i>
                                            <span class="text">IP属地：<?php echo $user_data['ip_location'] ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="desc text-ellipsis"><?php echo $user_data['desc'] ? str_replace(array('{{','}}'),'',wptexturize(sanitize_textarea_field(esc_attr($user_data['desc'])))) : __('这个人很懒，什么都没有留下！','Qk'); ?>
                                </div>
                            </div>
                            <div class="right-action" v-cloak>
                                <div class="profile-info-button" @click="whisper()" v-show="!is_self && loaded">私信</div>
                                <div class="profile-primary-button" :class="[{'no-follow':is_follow}]" @click="onFollow()" v-show="!is_self && loaded" v-cloak>{{is_follow ? '已关注' : '关注 TA'}}</div>
                                <a class="profile-primary-button mobile-hidden no-hover" rel="nofollow" href="<?php echo qk_get_account_url(''); ?>"  v-show="is_self && loaded" v-cloak>个人中心</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="author-container-mask"></div>
        </div>
    </div>
    <!---#author-header----->
    <div class="author-body wrapper">
        <div class="author-tabsbar box">
            <div class="tab-links">
                <a href="<?php echo $link; ?>" class="<?php echo ($user_page == 'index' ? 'active' : '');?>">
                    <!--<i class="ri-home-smile-fill"></i>-->
                    <span class="text">发布</span>
                    <?php echo $stats_count['posts_count'] ? '<span class="num">'.$stats_count['posts_count'].'</span>':''?>
                </a>
                <a href="<?php echo $link.'/dynamic'; ?>" class="<?php echo ($user_page == 'dynamic' ? 'active' : '');?>">
                    <!--<i class="ri-meteor-fill"></i>-->
                    <span class="text">动态</span>
                </a>
                <!--<a href="<?php echo $link.'/post'; ?>" class="<?php echo ($user_page == 'post' ? 'active' : '');?>">-->
                <!--    <i class="ri-draft-fill"></i>-->
                <!--    <span class="text">投稿</span>-->
                <!--    <?php echo $stats_count['posts_count'] ? '<span class="num">'.$stats_count['posts_count'].'</span>':''?>-->
                <!--</a>-->
                <a href="<?php echo $link.'/favorite'; ?>" class="<?php echo ($user_page == 'favorite' ? 'active' : '');?>">
                    <!--<i class="ri-star-smile-fill"></i>-->
                    <span class="text">收藏</span>
                    <span class="num"><?php echo $stats_count['favorites_count'] ?></span>
                </a>
                <a href="<?php echo $link.'/comments'; ?>" class="<?php echo ($user_page == 'comments' ? 'active' : '');?>">
                    <!--<i class="ri-message-3-fill"></i>-->
                    <span class="text">评论</span>
                    <span class="num"><?php echo $stats_count['comments_count'] ?></span>
                </a>
            </div>
            <!---#tab-links----->
        </div>
        <!---#author-tabsbar----->
        <div class="author-page-content">
            <div class="author-page-left">
                <?php 
                    if($user_page){
                        if(strpos($user_page,'fans') !== false){
                            get_template_part('User/fans');
                        }else{
                            get_template_part('User/'.$user_page);
                        }
                        
                    }
                ?>
            </div>
            <!--<div class="author-page-right"></div>-->
        </div>
    <!---#author-page-content----->
    </div>
</div>

<?php
get_footer();
