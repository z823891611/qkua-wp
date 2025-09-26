<?php
use Qk\Modules\Common\Circle;
/**
 * 
 * 圈子话题
 * 
 * */
get_header();

$topic_id = get_queried_object_id();

if(empty($topic_id)){
    wp_safe_redirect(home_url().'/404');
    exit;
}
$views = (int)get_term_meta($topic_id,'views',true);
update_term_meta($topic_id,'views',$views+1);
$topic = Circle::get_topic_data($topic_id);

wp_localize_script( 'qk-circle', 'qk_circle',$topic);

?>
<?php do_action('qk_archive_topic_top',$topic); ?>

<div class="qk-single-content wrapper">

    <?php do_action('qk_archive_topic_before'); ?>

    <div id="primary-home" class="content-area">
        <main class="site-main">
            <?php
                do_action('qk_archive_topic_content_before');
                
                get_template_part( 'TempParts/Circle/circle','moment-list');

                do_action('qk_archive_topic_content_after');

            ?>
        </main>
    </div>

    <?php do_action('qk_archive_topic_after'); ?>

    <?php get_sidebar(); ?>

</div>
 
<?php
get_footer();
