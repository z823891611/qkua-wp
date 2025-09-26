<?php
use Qk\Modules\Common\Circle;
/**
 * 
 * 圈子文章list
 * 
*/

$paged = get_query_var('paged') ? get_query_var('paged') : 1;

$tax = get_queried_object();
$term_id = isset($tax->term_id) ? $tax->term_id : 0;
$taxonomy = isset($tax->taxonomy) ? $tax->taxonomy : '';
//$term_id = get_queried_object_id();

$left_sidebar = Circle::get_show_left_sidebar($tax);
$tabs = Circle::get_tabbar($tax);
$default_index = Circle::get_default_tabbar_index($tax);

$args = isset($tabs[$default_index]) ? $tabs[$default_index] : array();
$args['paged'] = $paged;

if($term_id){
    if($taxonomy == 'circle_cat' && !isset($args['circle_cat'])) {
        $args['circle_cat'] = $term_id;
    }

    if($taxonomy == 'topic' && !isset($args['topic'])) {
        $args['topic'] = $term_id;
    }
}


$qk_list_opt = array();


foreach ($tabs as $value) {
    $qk_list_opt[] = array(
        'name' => $value['name'],
        'tab_type' => $value['tab_type'],
        'list_style_type' => !empty($value['list_style_type']) ? $value['list_style_type'] : 'list-1',
        'video_play_type' => !empty($value['video_play_type']) ? $value['video_play_type'] : 'click',
    );
}

wp_localize_script( 'qk-circle', 'qk_list_opt',array(
    'opts' => $qk_list_opt,
    'tabIndex' => $default_index,
));

?>
<div class="circle-content-wrap" ref="circleContentWrap">
    <div class="circle-scroll-to"></div>
    <div class="circle-tabs-nav <?php echo $left_sidebar ? 'mobile-show' : '';?>">
        <div class="circle-tabs-nav-inner">
            <div id="tabs" class="tabs scroll-tabs">
                <ul class="tabs-nav">
                    <?php foreach ($tabs as $key => $value) :?>
                    <li class="<?php echo $default_index == $key ? 'active' :''?>" @click="changeTab(<?php echo $key ?>,'<?php echo $value['tab_type'] ?>')">
                        <span><?php echo $value['name'] ?></span>
                    </li>
                    <?php endforeach; ?>
                    <div class="active-bar"></div>
                </ul>
             </div>
             <div class="orderby-wrap">
                <div class="orderby"><span v-text="orderbyList[orderby]">默认排序</span> <i class="ri-arrow-down-s-line"></i></div>
                <ul class="orderby-list box">
                    <li class="orderby-item" :class="[{active:orderby == index}]" v-for="(item,index) in orderbyList" v-text="item" :key="index" @click="changeOrderby(index)"></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="circle-sticky-posts" v-show="tabIndex == <?php echo $default_index;?> && !circles.length">
        <?php do_action('qk_circle_sticky_posts'); ?>
    </div>
    <?php if(!$term_id): ?>
        <div class="circles-warpper box" v-show="tabType == 'circle'" v-cloak>
            <div class="circle-list-wrap"  v-if="circleTabs.length">
                <div class="circle-cat-tabs">
                    <div id="tabs" class="tabs">
                        <ul class="tabs-nav">
                            <li :class="[{active:circleTabIndex == 'all'}]" @click="circleCatChange('all')">全部</li>
                            <li :class="[{active:circleTabIndex == index}]" v-text="item.name"  v-for="(item,index) in circleTabs" :key="index" @click="circleCatChange(index)"></li>
                        </ul>
                    </div>
                </div>
                <div class="circle-groups" v-if="circleList.length">
                    <div class="circle-group-item" v-for="(circle,i) in circleList" :key="i" v-if="circle.list.length">
                        <div class="group-title" v-text="circle.cat_name" v-if="circleTabIndex == 'all'"></div>
                        <div class="circle-list qk-grid" v-if="circleList.length">
                            <div class="circle-item" v-for="(item,index) in circle.list" :key="index">
                                <a :href="item.link" class="circle-item-inner no-hover">
                                    <div class="circle-info">
                                        <div class="circle-image">
                                            <img :src="item.icon" width="46" height="46" class="circle-image-face w-h">
                                        </div>
                                        <div class="circle-detail">
                                            <div class="circle-title">
                                                <h2 class="circle-name text-ellipsis" v-text="item.name"></h2>
                                                <div class="circle-info-tag">
                                                    <div :class="['tag-item',i]" v-for="(badge,i) in item.circle_badge">
                                                        <i :class="badge.icon"></i>
                                                        <span v-text="badge.name"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="circle-desc text-ellipsis" v-text="item.desc"></div>
                                            <div class="circle-count">
                                                <span v-text="'圈友 '+item.user_count"></span>
                                                <span v-text="'贴数 '+item.post_count"></span>
                                                <span v-text="'今日发帖 '+item.today_post_count" v-if="item.today_post_count > 0"></span>
                                                <span v-text="'互动数 '+item.comment_count"></span>
                                                <span v-text="'今日互动数 '+item.today_comment_count" v-if="item.today_comment_count > 0"></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="circle-moment-list<?php echo !empty($args['list_style_type']) && $args['list_style_type'] == 'list-3' && !wp_is_mobile() ? ' qk-waterfall' : '';?>" :class="waterfallClass" ref="momentList" v-show="!loading && !isDataEmpty && tabType != 'circle'">
        <?php
            $data = \Qk\Modules\Common\Circle::get_moment_list($args);
            
            if(!empty($data['data'])) {
                echo implode("", $data['data']);
            }
        ?>
    </div>
    
    <div class="loading empty qk-radius box" v-if="loading && !isDataEmpty" v-cloak></div>
    <template v-else-if="(tabType != 'circle' && isDataEmpty || (!isDataEmpty && <?php echo empty($data['data']) ? 1 : 0; ?>)) || (tabType == 'circle' && !circleList.length)">
        <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
    </template>
    
    <?php
        echo qk_ajax_pagenav( array( 'paged' => $paged, 'pages' => $data['pages'] ), 'json', 'auto','listChange' ) 
    ?>
</div>
