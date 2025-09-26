<?php
$user_id = get_query_var('author');
// $pages =  Qk\Modules\Common\Comment::get_user_comment_count($user_id);

?>
<div id="dynamic-page" class="dynamic-page w-h" ref="dynamicPage" v-cloak>
    <transition name="fade">
        <ul class="dynamic-list" v-if="data.length">
            <li class="dynamic-item box" v-for="(item,index) in data" :key="index">
                <a :href="item.user_data.link" class="no-hover" v-html="item.user_data.avatar_html"></a>
                <a :href="item.link" class="no-hover">
                    <div class="item-header">
                        <div v-html="item.user_data.name_html"></div>
                        <div class="date">{{item.date}} · 发布了文章</div>
                    </div>
                    <div class="item-body">
                        <div class="title">{{item.title}}</div>
                        <div class="content" v-html="item.desc"></div>
                        <div class="image-list mg-t">
                            <div class="image-item mode-base" v-if="item.thumb && item.images.length <= 1">
                                <img class="w-h lazyload" :data-src="item.thumb" src="">
                            </div>
                            <div class="image-item" v-for="(v,index) in item.images" v-if="item.images.length > 1 && index < 3">
                                <img class="w-h lazyload" :data-src="v" src="">
                            </div>
                        </div>
                    </div>
                    <div class="item-footer">
                        <div class="post-meta qk-flex">
                            <span :num="item.post_meta.like" class="like qk-flex">
                                <i class="ri-thumb-up-line"></i>
                            </span>
                            <span :num="item.post_meta.comment" class="comment qk-flex">
                                <i class="ri-message-3-line"></i>
                            </span>
                            <span :num="item.post_meta.collect" class="collect qk-flex">
                                <i class="ri-star-smile-line"></i>
                            </span>
                        </div>
                    </div>
                </a>
            </li>
        </ul>
    </transition>
    <div class="loading empty qk-radius box" v-if="!data.length && loading && !isDataEmpty"></div>
    <template v-else-if="!data.length && isDataEmpty">
        <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
    </template>
    <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1 ), 'json', 'page','change'); ?>
</div>