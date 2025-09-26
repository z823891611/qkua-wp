<?php
use Qk\Modules\Common\Circle;
/**
 * 圈子
 * */
get_header();

$circle_id = get_queried_object_id();

if(empty($circle_id)){
    wp_safe_redirect(home_url().'/404');
    exit;
}

$circle = Circle::get_circle_data($circle_id);

$recommends = get_term_meta($circle_id, 'qk_circle_recommends', true);
$recommends = !empty($recommends) && is_array($recommends) ? $recommends :array();

$is_mobile = wp_is_mobile();
$info_show = Circle::get_circle_settings($circle_id,'circle_info_show');
$input_show = Circle::get_circle_settings($circle_id,'circle_input_show');

//圈子板块（标签）
$tags = get_term_meta($circle_id, 'qk_circle_tags', true);
$tags = !empty($tags) && is_array($tags) ? $tags :array();

$badges = Circle::get_circle_badge($circle_id);

$views = (int)get_term_meta($circle_id,'views',true);
update_term_meta($circle_id,'views',$views+1);

$privacy = get_term_meta($circle_id,'qk_circle_privacy',true);
$privacy = !$privacy || $privacy == 'public'? true: false;

wp_localize_script( 'qk-circle', 'qk_circle',$circle);

?>
<div class="qk-single-content wrapper">
    
    <?php get_template_part( 'TempParts/Circle/circle','left-sidebar'); ?>
    
    <div id="primary-home" class="content-area">
        <main class="site-main">
            <?php if($info_show && ($info_show == 'all' || ($info_show == 'pc' && !$is_mobile) || ($info_show == 'mobile' && $is_mobile))): ?>
            <div class="circle-info-wrap">
                <div class="circle-cover" style="background-image: url(<?php echo $circle['cover'] ?>);"></div>
                <div class="circle-info box">
                    <div class="circle-info-top">
                        <div class="circle-image">
                            <?php echo qk_get_img(array('src'=>$circle['icon'],'class'=>array('circle-image-face','w-h'),'alt'=>$circle['name']));?>
                        </div>
                        <div class="circle-title">
                            <h1 class="circle-name" @click="seeInfo"><?php echo $circle['name'] ?><i class="ri-arrow-right-s-line"></i></h1>
                            <div class="circle-desc circle-data"><span>圈友 <?php echo $circle['user_count'] ?></span> · <span>帖子 <?php echo $circle['post_count'] ?></span></div>
                        </div>
                        <div class="follow-button">
                            <button @click="joinCircle">加入</button>
                        </div>
                    </div>
                    <div class="circle-info-bottom">
                        <div class="circle-info-tag">
                            <?php foreach ($badges as $key => $v): ?>
                                <div class="tag-item <?php echo $key ?>">
                                    <i class="<?php echo $v['icon'] ?>"></i>
                                    <span><?php echo $v['name'] ?></span>
                                </div>
                            <?php endforeach ?>
                            <a class="tag-item user" href="<?php echo $circle['admin']['link'] ?>">
                                <img src="<?php echo $circle['admin']['avatar'] ?>" width="22px" height="22px">
                                <span><?php echo $circle['admin']['name'] ?></span>
                                <span> 创建</span>
                            </a>
                        </div>
                        <?php if($circle['desc']): ?>
                            <p class="circle-desc"><?php echo $circle['desc'] ?></p>
                        <?php endif ?>
                        <!--<div class="circle-moderator-apply">-->
                        <!--    <a href="#" class="moderator-apply">-->
                        <!--        <div class="">-->
                        <!--            <h3 class="title"> 版主认证 </h3>-->
                        <!--            <p> 申请成为该论坛的版主 </p>-->
                        <!--        </div>-->
                        <!--    </a>-->
                        <!--</div>-->
                        <?php if(!empty($recommends)): ?>
                            <div class="scroll-swiper-wrapper">
                                <ul class="swiper-content">
                                    <?php foreach ($recommends as $v): ?>
                                        <li class="swiper-slide">
                                            <a href="<?php echo $v['link'] ?>">
                                            <div class="thumb">
                                                <img src="<?php echo $v['icon'] ?>" class="w-h">
                                            </div>
                                            <span class="text-ellipsis"><?php echo $v['name'] ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <?php endif ?>
            <?php if($privacy || $circle['in_circle']): ?>
                <?php 
                    if($input_show &&
                        ($input_show == 'all' 
                        || ($input_show == 'pc' && !$is_mobile) 
                        || ($input_show == 'mobile' && $is_mobile)
                    )) {
                        get_template_part( 'TempParts/Circle/circle','editor'); 
                    }
                ?>
                <?php get_template_part( 'TempParts/Circle/circle','moment-list'); ?>
            <?php else: ?>
                <?php echo qk_get_empty('您需要加入该圈子才能查看帖子','no-right.svg'); ?>
            <?php endif; ?>
        </main>
    </div>
    
    <?php get_sidebar(); ?>
    
</div>

<?php
get_footer();
