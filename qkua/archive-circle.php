<?php
/**
 * 社区圈子首页
 */
get_header();

?>

<?php do_action('qk_archive_circle_top'); ?>

<div class="qk-single-content wrapper">
    
    <?php get_template_part( 'TempParts/Circle/circle','left-sidebar'); ?>
    
    <?php do_action('qk_archive_circle_before'); ?>
    
    <div id="primary-home" class="content-area">
        
        <?php do_action('qk_archive_circle_content_before');?>
        
        <main class="site-main">
            <?php get_template_part( 'TempParts/Circle/circle','editor'); ?>
            <?php get_template_part( 'TempParts/Circle/circle','moment-list'); ?>
        </main>
        
        <?php  do_action('qk_archive_circle_content_after'); ?>

    </div>

    <?php do_action('qk_archive_circle_after'); ?>

    <?php get_sidebar(); ?>

</div>

<?php
get_footer();