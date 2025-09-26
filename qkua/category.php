<?php
/**
 * 分类存档页面
 */
get_header();

$term = get_queried_object();

$show_sidebar = isset($term->term_id) ? get_term_meta($term->term_id,'qk_show_sidebar',true) : false;

//获取自定义类型
$tax = isset($term->taxonomy) ? $term->taxonomy : 'normal';
?>

<?php do_action('qk_archive_'.$tax.'_top'); ?>

<div class="qk-single-content wrapper <?php echo $tax; echo $show_sidebar ? ' single-sidebar-show' : ' single-sidebar-hidden'; ?> ">

    <?php do_action('qk_archive_'.$tax.'_before'); ?>

    <div id="primary-home" class="content-area">

            <?php

                do_action('qk_archive_'.$tax.'_content_before');

                if($tax){

                    get_template_part( 'TempParts/Archive/content',$tax);

                }else{
                    while ( have_posts() ) :
                        the_post();

                        get_template_part( 'TempParts/content', get_post_type() );
        
                    endwhile;

                }

                do_action('qk_archive_'.$tax.'_content_after');

            ?>

    </div>

    <?php 
        if($show_sidebar) get_sidebar(); 
    ?>

</div>
<?php
get_footer();