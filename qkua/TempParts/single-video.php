<?php
$post_id = get_the_id();
$video_meta = get_post_meta($post_id, 'single_video_metabox', true );
$video_list = !empty($video_meta['group']) ? $video_meta['group'] : array();

?>

<article class="single-article qk-radius box tab-pane active">
    
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

<article class="qk-radius tab-pane" ref="videoChapters">
    <div id="video-chapters" ref="videoChapters">
        <div class="chapter box mg-b" v-for="(item,i) in list">
            <h3 v-text="item.chapter_title" v-if="item.chapter_title"></h3>
            <div class="chapter-desc" v-text="item.chapter_desc" v-if="item.chapter_desc"></div>
            <ul class="video-list">
                <li v-for="(video,index) in item.video_list">
                    <a :href="video.link" class="qk-flex">
                        <i class="ri-play-circle-fill"></i>
                        {{i+1}}-{{index+1}} {{video.title}}
                    </a>
                    <!--<div style=" width: 48px; text-align: center; "><i class="ri-lock-fill" style="font-size: 18px; color: var(--color-text-placeholder); line-height: 18px;"></i></div>-->
                </li>
            </ul>
        </div>
    </div>
</article>