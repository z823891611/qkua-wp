<?php
$user_id = get_query_var('author');
$user_page = get_query_var('qk_user_page');
$slug_type = explode('/', $user_page);
$slug_type = isset($slug_type[1]) ? $slug_type[1] : '';

$pages =  Qk\Modules\Common\User::get_user_meta_count($user_id,'qk_'.$slug_type);

$api = $slug_type == 'follow' ? 'getFollowList' : '';
$index = $slug_type == 'follow' ? 1 : 0;
?>

<div id="follows-page" class="follows-page box w-h" ref="followsPage" data-index="<?php echo $index ?>" v-cloak>
    <div id="tabs" class="tabs">
        <ul class="tabs-nav">
            <li class="<?php echo (0 == $index ? 'active' : '');?>">{{qk_author.is_self ? '我' : 'TA'}}的粉丝</li>
            <li class="<?php echo (1 == $index ? 'active' : '');?>">{{qk_author.is_self ? '我' : 'TA'}}的关注</li>
            <div class="active-bar"></div>
        </ul>
        <div class="tabs-content">
            <ul class="relation-list" v-if="data.length">
                <li class="list-item" v-for="(item,index) in data">
                    <a :href="item.link">
                        <div class="user-avatar">
                            <img :src="item.avatar" class="avatar-face w-h">
                        </div>
                    </a>
                    <div class="user-info">
                        <div v-html="item.name"></div>
                        <div class="desc text-ellipsis" v-text="item.desc"></div>
                    </div>
                    <div class="user-action button" :class="[{'bg-text':item.is_follow}]" @click="onFollow(item)"><span>{{item.is_follow ? '已':''}}关注</span></div>
                </li>
            </ul>
            <div class="loading empty qk-radius box" v-else-if="!data.length && loading && !isDataEmpty"></div>
            <template v-else-if="!data.length && isDataEmpty">
                <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
            </template>
        </div>
    </div>
    <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => ceil($pages/10) ), 'json', 'page','change'); ?>
</div>