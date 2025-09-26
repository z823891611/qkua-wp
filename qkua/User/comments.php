<?php
$user_id = get_query_var('author');
$pages =  Qk\Modules\Common\Comment::get_user_comment_count($user_id);

?>
<div id="comments-page" class="comments-page w-h" ref="commentsPage" v-cloak>
    <transition name="fade">
        <ul class="comments-list" v-if="data.length">
            <li class="comments-item box" v-for="(item,index) in data" :key="index">
                <div class="comments-date" v-text="item.comment.date"></div>
                <div class="comments-content text-ellipsis" v-text="item.comment.content"></div>
                <div class="article-or-replies" >
                    <span class="text-ellipsis">{{item.comment_parent ? '回复评论：': '回复帖子：'}}
                        <template v-if="item.comment_parent">
                            <a :href="item.comment_post.link + '#comment-' + item.comment_parent.id"v-text="item.comment_parent.content"></a>
                        </template>
                        <template v-else>
                            <a :href="item.comment_post.link" v-text="item.comment_post.title"></a>
                        </template>
                    </span>
                </div>
            </li>
        </ul>
    </transition>
    <div class="loading empty qk-radius box" v-if="!data.length && loading && !isDataEmpty"></div>
    <template v-else-if="!data.length && isDataEmpty">
        <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
    </template>
    <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => ceil($pages/10) ), 'json', 'auto','change'); ?>
</div>