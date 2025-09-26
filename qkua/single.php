<?php
    get_header();
    $post_type = get_post_type();
    $qk_custom_post_type = qk_custom_post_type(); //获取自定义文章类型
    $post_id = get_the_id();
    $views = (int)get_post_meta($post_id,'views',true);
    update_post_meta($post_id,'views',$views+1);
?>
    <?php do_action('qk_single_wrapper_before'); ?>
    
    <div class="qk-single-content wrapper">
        
        <div id="primary-home" class="content-area">
            
            <?php while ( have_posts() ) : the_post();
                
                do_action('qk_single_content_before');
        
                get_template_part( 'TempParts/single',isset($qk_custom_post_type[$post_type]) ? $post_type : 'post');
                
                do_action('qk_single_content_after');
        
            if ( (comments_open() || get_comments_number()) ) :
                comments_template();
            endif;
        
            endwhile; ?>
        </div>
        
        <?php 
           //小工具
            get_sidebar(); 
        ?>
        
    </div>
    
    <?php do_action('qk_single_wrapper_after'); ?>
    
<?php
get_footer();