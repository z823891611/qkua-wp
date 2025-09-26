<?php
use Qk\Modules\Common\Circle;
/**
 * 默认文章内容页 post-style-1
 */
$post_id = get_the_id();
$user_id = get_current_user_id();

$moment_data = Circle::get_moment_data($post_id,$user_id);
$post_meta = $moment_data['meta'];

$author = $moment_data['author'];

$image_list = Circle::get_moment_image_list($moment_data['attachment']['image'],$post_id);

$term_list = Circle::get_moment_circle_and_topic_list($moment_data);

?>
<article class="single-article circle-single qk-radius box">

    <?php do_action('qk_single_article_before'); ?>

    <div class="article-header">
        <h1<?php echo !$moment_data['title'] ? ' class="pian"' : ''; ?>><?php echo get_the_title(); ?></h1>
        <div class="post-meta">
            <div class="post-user-info qk-flex">
                <div class="left qk-flex">
                    <?php echo $author['avatar_html'] ?>
                    <div class="user-info">
                        <?php echo $author['name_html'] ?>
                        <div class="post-meta-row qk-flex">
                            <span class="post-date"><?php echo $moment_data['date'] ?></span>
                            <span> · </span>
                            <span class="post-views">浏览<?php echo $post_meta['views'] ?></span>
                            <?php if(user_can($user_id, 'administrator' )){?>
                                <span> ·</span>
                                <span class="post-edit qk-flex"><a href="<?php echo get_edit_post_link($post_id); ?>" target="_blank">编辑</a></span>
                            <?php }?>
                       </div>
                    </div>
                </div>
                <div class="right">
                    <button class="follow" @click="onFollow()" v-if="!is_follow"><i class="ri-heart-add-line"></i><span>关注</span></button>
                    <button class="no-follow follow" @click="onFollow()" v-else  v-cloak><i class="ri-heart-fill"></i><span>已关注</span></button>
                    <!--<button class="letter"  @click="whisper()"><i class="ri-chat-3-line"></i>私信</button>-->
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
    <?php echo $image_list;?>
    <?php echo $term_list;?>
    <?php do_action('qk_single_article_after'); ?>
</article>
<!--#正文-->