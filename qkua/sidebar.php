<?php
$sidebars_widgets = wp_get_sidebars_widgets();

$is_page = is_page();
$is_post = is_singular('post');

if($is_post || $is_page){
    $post_id = get_the_id();
    //文章页面小工具
    $show_widget = Qk\Modules\Templates\Single::get_single_post_settings($post_id,'single_sidebar_open');
    if(!$show_widget) return;
}

$options = qk_get_option();

$is_video = is_singular('video');
$is_episode = is_singular('episode');

if($is_video || $is_episode) {

    if($is_video && !$options['qk_video_options']['sidebar_open']) return;
    
    if($is_episode && !$options['qk_episode_options']['sidebar_open']) return;
}

$is_circle_single = is_singular('circle');

$is_circle_cat = is_tax('circle_cat');

if($is_circle_cat && empty($options['circle_layout']['sidebar_open'])) return;

$is_circle_home = is_post_type_archive('circle');

if($is_circle_home && empty($options['circle_home_layout']['sidebar_open'])) return;

$is_topic = is_tax('topic');

if($is_topic && empty($options['topic_layout']['sidebar_open'])) return;

//圈子首页
$is_circle = apply_filters('qk_is_page', 'circle');

//分类
// $tax = get_queried_object();
// $taxonomy = isset($tax->taxonomy) ? $tax->taxonomy : '';
// $is_circle_cat = false;
// $is_topic = false;

// if($taxonomy == 'circle_cat') {
//     $is_circle_cat = true;
// }elseif ($taxonomy == 'topic') {
//     $is_topic = true;
// }

?>

<aside id="secondary" class="widget-area">
   <?php
        switch (true) {
            case $is_circle:
                dynamic_sidebar('circle-home-sidebar');
                break;
            case $is_circle_cat:
                dynamic_sidebar('circle-sidebar');
                break;
            case $is_circle_single:
                dynamic_sidebar('circle-single-sidebar');
                break;
            case $is_topic:
                dynamic_sidebar('topic-sidebar');
                break;
            case $is_post:
                dynamic_sidebar('post-sidebar');
                break;
            case $is_video:
                dynamic_sidebar('video-sidebar');
                break;
            case $is_page:
                dynamic_sidebar('page-sidebar');
                break;
            default:
                dynamic_sidebar('default-sidebar');
                break;
        }
    ?>
</aside>
<!--#小工具-->