<?php

use Qk\Modules\Common\Post;
/**
 * 默认文章内容页 post-style-1
 */
$post_id = get_the_id();
$post_meta = Post::get_post_meta($post_id);
//print_r($post_meta);
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
            <!--<div class="post-user-info qk-flex">-->
            <!--    <div class="left qk-flex">-->
            <!--        <?php echo qk_get_avatar(array('src'=>$post_meta['user_avatar'],'alt'=>$post_meta['user_name'].'的头像'));?>-->
            <!--        <div class="user-info">-->
                        <!--<a href="<?php echo $post_meta['user_link'] ?>" class="user-name"><?php echo $post_meta['user_name'] ?></a>-->
            <!--            <?php echo Qk\Modules\Common\User::get_user_name_html($post_meta['user_id'],$post_meta['user_name']);?>-->
            <!--            <div class="desc"><?php echo $post_meta['user_desc'] ?></div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--    <div class="right qk-flex">-->
            <!--        <button class="follow qk-flex" @click="onFollow()" v-if="!is_follow"><i class="ri-heart-add-line"></i><span>关注</span></button>-->
            <!--        <button class="no-follow follow qk-flex" @click="onFollow()" v-else  v-cloak><i class="ri-heart-fill"></i><span>已关注</span></button>-->
            <!--        <button class="letter qk-flex"><i class="ri-chat-3-line"></i>私信</button>-->
            <!--    </div>    -->
            <!--</div>-->
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