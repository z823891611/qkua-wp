<?php
use Qk\Modules\Common\User;

$account_page = get_query_var('qk_account_page');
$user_id = get_current_user_id(); //get_queried_object_id() get_queried_object()->ID
$is_account_home = false;

if($account_page == 'index'){
    $account_page = 'assets';
    $is_account_home = true;
}

if(!$user_id){
    wp_safe_redirect(home_url().'/404');
    exit;
}

$credit = get_user_meta($user_id,'qk_credit',true);
$credit = $credit ? (int)$credit : 0;

$money = get_user_meta($user_id,'qk_money',true);
$money = $money ? $money : 0;

$user_data = User::get_user_public_data($user_id,true); //get_queried_object() get_author_info($user_id)
$stats_count = User::get_user_stats_count($user_id); //获取统计计数
$followers_count = User::get_user_followers_stats_count($user_id); //获取关注数计数

$quicks = qk_custom_account_quick_arg();
$links = qk_custom_account_links_arg();
$array = array_merge($links,$quicks);
$array['vip'] = array('name' => '会员中心');
$array['assets'] = array('name' => '我的钱包');
$array['distribution'] = array('name' => '推广中心');
$pageTitle = $is_account_home ? '个人中心' : (isset($array[$account_page]) ? $array[$account_page]['name'] : '');

$link = get_author_posts_url($user_id);

$commission = \Qk\Modules\Common\Distribution::get_user_commission($user_id);

get_header();

wp_localize_script( 'qk-account', 'qk_account',array(
    'author_id'=>$user_id,
));

?>
<div id="account" class="account wrapper">
    <div class="back-warp box mobile-show">
        <div class="back-box">
            <div class="back" onclick="history.back()"><i class="ri-arrow-left-s-line"></i></div>
            <div class="page-title"><?php echo $pageTitle; ?></div>
        </div>
    </div>
    <div class="account-header mg-b <?php echo !$is_account_home ? 'account-mobile-hidden' :'' ?>">
        <div class="mask-wrapper" style="background-image: url(<?php echo $user_data['cover'] ?>);"></div>
        <div class="account-panel box">
            <div class="account-profile">
                <div class="left-user-info">
                    <?php echo $user_data['avatar_html'] ?>
                    <div class="user-info">
                        <div class="user-info-name">
                            <span class="user-name"><?php echo $user_data['name'] ?></span>
                            <?php if($user_data['lv']){?>
                                <span class="user-lv"><img src="<?php echo $user_data['lv']['icon']; ?>" class="lv-img-icon"></span>
                            <?php }?>
                            <?php if($user_data['vip']){?>
                                <span class="user-vip"><img src="<?php echo $user_data['vip']['image']; ?>" class="vip-img-icon"></span>
                            <?php }?>
                            <!--<span class="user-auth">Qk主题官方</span>-->
                        </div>
                        <div class="desc text-ellipsis">
                            <div class="verify">
                                <?php if(qk_get_option('verify_open')){?>
                                <?php if(empty($user_data['verify'])){?>
                                    <a href="<?php echo qk_get_custom_page_url('verify'); ?>">
                                        暂未认证，去认证 <i class="ri-arrow-right-s-line"></i>
                                    </a>
                                <?php }else{?>
                                    <div class="user-auth">
                                        <span class="auth-description"><?php echo $user_data['verify']['name'].'：'.$user_data['verify']['title']; ?></span>
                                    </div>
                                <?php }}?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="right-user-action">
                    <a href="<?php echo $user_data['link']; ?>" class="profile-primary-button no-hover">个人主页</a>
                </div>
            </div>
        </div>
    </div>
    <!---#account-header----->
    <div class="account-page-content">
        <div class="account-page-left mg-r <?php echo !$is_account_home ? 'account-mobile-hidden' :'' ?>">
            <div class="vip-card">
                <a class="vip-info qk-flex no-hover" href="<?php echo qk_get_account_url('vip') ?>">
                    <div class="vip-name"></i><?php echo $user_data['vip'] ? $user_data['vip']['name'] : '会员'?></div>
                    <div class="vip-expire-time qk-flex"><?php echo $user_data['vip'] ? $user_data['vip']['date'] == 0 ? '永不到期' : $user_data['vip']['date'].' 到期' : '开通会员'?><i class="ri-arrow-right-s-line"></i></div>
                </a>
            </div>
            <div class="counts-item qk-flex">
                <a class="single-count-item" href="<?php echo $link.'/dynamic' ?>">
                    <div class="count-num"><?php echo $stats_count['posts_count'] ?></div>
                    <div class="count-text">动态</div>
                </a>
                <a class="single-count-item" href="<?php echo $link.'/comments' ?>">
                    <div class="count-num"><?php echo $stats_count['comments_count'] ?></div>
                    <div class="count-text">评论</div>
                </a>
                <a class="single-count-item" href="<?php echo $link.'/favorite' ?>">
                    <div class="count-num"><?php echo $stats_count['favorites_count'] ?></div>
                    <div class="count-text">收藏</div>
                </a>
                <a class="single-count-item" href="<?php echo $link.'/fans/follow'; ?>">
                    <div class="count-num"><?php echo $followers_count['follow'] ?></div>
                    <div class="count-text">关注</div>
                </a>
                <a class="single-count-item" href="<?php echo $link.'/fans/fans'; ?>">
                    <div class="count-num"><?php echo $followers_count['fans'] ?></div>
                    <div class="count-text">粉丝</div>
                </a>
            </div>
            <div class="user-assets-item">
                <div class="title">我的钱包</div>
                <div class="user-assets qk-flex">
                    <a href="<?php echo qk_get_account_url('assets') ?>" class="user-money-card">
                        <div class="user-assets-name">余额<i class="ri-arrow-right-s-line"></i></div>
                        <div class="user-assets-num"><?php echo $money ?></div>
                    </a>
                    <a href="<?php echo qk_get_account_url('assets') ?>" class="user-credit-card">
                        <div class="user-assets-name">积分<i class="ri-arrow-right-s-line"></i></div>
                        <div class="user-assets-num"><?php echo $credit ?></div>
                    </a>
                </div>
            </div>
            <div class="distribution-card">
                <div class="title">推广返佣</div>
                <a href="<?php echo qk_get_account_url('distribution') ?>">
                    <div class="income-info">
                        <div class="total">
                            <span class="money">
                                <span class="unit">￥</span><?php echo $commission['money']; ?>
                            </span>
                            <i class="ri-arrow-right-s-line"></i>
                        </div>
                        <div class="withdraw">
                            <div class="left">累计收益 
                                <span class="money">￥<?php echo $commission['data']['total']; ?></span>
                            </div>
                            <div class="right">已提现 
                                <span class="money">￥<?php echo $commission['withdrawn']; ?></span>
                            </div>
                        </div>
                    </div>
                    <!--<div class="bar"><div class="bar-sub" style="width: 55.4776%;background-color: rgb(20, 196, 191);"></div><div class="bar-sub" style="width: 45.5224%; background-color: rgb(197, 242, 104);"></div><div class="bar-sub" style="width: 22%;background-color: rgb(97, 136, 255);"></div></div>-->
                    <!--<div class="income-count">-->
                    <!--    <div class="item">-->
                    <!--        <div class="item-line">-->
                    <!--            <div class="dot"></div>-->
                    <!--            <div class="name">一级收益</div>-->
                    <!--        </div>-->
                    <!--        <div class="count">￥84.90</div>-->
                    <!--    </div>-->
                    <!--    <div class="item">-->
                    <!--        <div class="item-line">-->
                    <!--            <div class="dot"></div>-->
                    <!--            <div class="name">二级收益</div>-->
                    <!--        </div>-->
                    <!--        <div class="count">￥84.90</div>-->
                    <!--    </div>-->
                    <!--    <div class="item">-->
                    <!--        <div class="item-line">-->
                    <!--            <div class="dot"></div>-->
                    <!--            <div class="name">三级收益</div>-->
                    <!--        </div>-->
                    <!--        <div class="count">￥84.90</div>-->
                    <!--    </div>-->
                    <!--</div>-->
                </a>
            </div>
            <div class="quick-panel">
                <?php foreach ($quicks as $key => $value) {?>
                    <a href="<?php echo qk_get_account_url($key) ?>" class="panel-item<?php echo ($account_page == $key ? ' active' : '');?>">
                        <i class="<?php echo $value['icon'] ?>"></i>
                        <div><?php echo $value['name'] ?></div>
                    </a>
                <?php }?>
            </div>
            <div class="tab-links">
                <?php foreach ($links as $key => $value) {?>
                <a href="<?php echo qk_get_account_url($key) ?>" class="link-item<?php echo ($account_page == $key ? ' active' : '');?>">
                    <div class="link-title qk-flex">
                        <i class="<?php echo $value['icon'] ?>"></i> 
                        <span><?php echo $value['name'] ?></span>
                    </div> 
                    <i class="ri-arrow-right-s-line"></i>
                </a>
                <?php }?>
            </div>
            <!---#account-tabsbar----->
        </div>
        <div class="account-page-right <?php echo $is_account_home ? 'account-mobile-hidden' :'' ?>">
            <?php 
                if($account_page){
                    get_template_part(apply_filters('qk_account_template_part','Account/'.$account_page));
                }
            ?>
        </div>
    </div>
    <!---#account-page-content----->
</div>
<style>


@media screen and (max-width:768px){
    .account-page-content .account-page-left {
        width: 100%;
        margin: 0;
    }
    
    .account-mobile-hidden{
    	display: none !important;
    }
}

.income-info .total {
    font-size: 18px;
    font-weight: 600;
    color: #1a7af8;
    margin-bottom: 12px;
    line-height: 24px;
}

.income-info {
    margin-top: 12px;
}

.withdraw {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    font-size: 13px;
    color: var(--color-text-secondary);
    line-height: 13px;
}

.total .unit {
    font-weight: 600;
    font-size: 14px;
}

.income-count {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    grid-gap: 8px;
}

span.money {
    color: var(--color-text-primary);
    display: inline-flex;
}

.right {
    cursor: pointer;
}

.income-count .dot {
    border-radius: 4px;
    width: 12px;
    height: 6px;
    margin-right: .17067rem;
    background-color: rgb(20, 196, 191);
}

.item-line {
    font-size: 12px;
    display: flex;
    align-items: flex-start;
    grid-gap: 2px;
    flex-direction: column;
}

.income-count .item {
    background: var(--bg-muted-color);
    padding: 8px;
    border-radius: var(--radius);
    color: var(--color-text-secondary);
    flex: 1;
}

.income-count .item .count {
    color: var(--color-text-primary);
    font-size: 13px;
    line-height: 13px;
    margin-top: 6px;
}

.income-count .item:nth-child(2) .dot {
    background-color: #c5f268;
}

.income-count .item:nth-child(3) .dot {
    background-color: #6188ff;
}

.bar {
    height: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: row;
    border-radius: var(--radius);
    margin-top: 12px;
}

.income-info .total .money {
    font-size: 18px;
    font-weight: 600;
    color: #1a7af8;
}

.income-info .total i {
    font-size: 18px;
    color: var(--color-text-secondary);
    line-height: 16px;
}

</style>
<?php

get_footer();
