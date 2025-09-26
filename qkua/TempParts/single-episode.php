<?php
use Qk\Modules\Common\Post;

$post_id = get_the_id();
$post_meta = Post::get_post_meta($post_id);
?>
<article class="single-article qk-radius box">

    <?php do_action('qk_single_article_before'); ?>

    <div class="article-header">
        <h1><?php echo get_the_title(); ?></h1>
        <div class="post-meta">
            <div class="post-meta-row qk-flex">
                <div class="left qk-flex">
                    <span class="post_date qk-flex"><i class="ri-time-line"></i><?php echo $post_meta['date'] ?></span>
                    <span class="post_views qk-flex"><i class="ri-eye-line"></i><?php echo $post_meta['views'] ?></span>
                </div>
                <div class="right">
                    <?php echo Qk\Modules\Templates\Modules\Posts::get_post_cats_list($post_meta['cats']); ?>
               </div>
           </div>
        </div>
    </div>
    <div class="article-content">
        <?php do_action('qk_single_post_content_before'); ?>
        <?php if($excerpt){ ?>
            <div class="content-excerpt">
                <?php echo get_the_excerpt(); ?>
            </div>
        <?php } ?>
        <?php the_content(); ?>
        <?php do_action('qk_single_post_content_after'); ?>
    </div>

    <?php do_action('qk_single_article_after'); ?>
</article>
<!--#正文-->