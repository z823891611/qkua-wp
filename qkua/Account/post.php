<?php
use Qk\Modules\Common\User;

$user_id = get_current_user_id();
$stats_count = User::get_user_posts_stats($user_id);

?>

<div class="post-page" ref="articlePage">
    <div class="post-data">
        <div class="section-title">稿件数据</div>
        <div class="post-data-list">
            <?php foreach ($stats_count as $value): ?>
                <div class="item-card">
                    <div class="name"><?php echo $value['name']?></div>
                    <div class="num"><?php echo $value['count']?></div>
                </div>
            <?php endforeach; ?>
            
        </div>
    </div>
    <div class="post-manage" style="height: 100%; flex: 1;">
        <div id="tabs" class="tabs">
            <ul class="tabs-nav">
                <li class="active">文章管理</li>
                <li>帖子管理</li>
                <li>视频管理</li>
                <li>商品管理</li>
                <div class="active-bar"></div>
            </ul>
            <div class="tabs-content">
                <div class="post-list post-2" v-if="data.length" v-cloak>
                    <ul class="qk-grid">
                        <li class="post-list-item" v-for="(item,index) in data">
                            <div class="item-in">
                                <div class="post-module-thumb" v-if="item.thumb">
                                    <div class="qk-radius post-thumbnail" style="padding-top: 65%;">
                                        <a :href="item.link" rel="nofollow" class="thumb-link">
                                            <img class="post-thumb w-h qk-radius lazyload" :data-src="item.thumb">
                                        </a>
                                    </div>
                                </div>
                                <div class="post-info">
                                    
                                    <h2 class="text-ellipsis">
                                        <a :href="item.link" v-text="item.title"></a>
                                        <span class="post-status" :class="item.post_status" v-text="item.status"></span>
                                    </h2>
                                    <div class="post-info-buttom">
                                        <div class="buttom-left">
                                            
                                            <span class="post-date" v-text="item.date"></span>
                                            <span class="post-views">
                                                阅读 {{item.post_meta.views}}
                                            </span>
                                            <span class="comment">
                                                评论 {{item.post_meta.comment}}
                                            </span>
                                            <span class="like">
                                                喜欢 {{item.post_meta.like}}
                                            </span>
                                            <span class="collect">
                                                收藏 {{item.post_meta.collect}}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="post-action" v-if="item.post_status == 'draft' || item.post_status == 'pending'">
                                    <a class="edit bg-text" :href="'/write?id='+item.id" target="_blank">编辑</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="loading empty qk-radius box" v-else-if="!data.length && loading && !isDataEmpty"></div>
                <template v-else-if="!data.length && isDataEmpty">
                    <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
                </template>
            </div>
        </div>
        <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1 ), 'json', 'page','change'); ?>
    </div>
</div>