<?php
/**
 * VIP页面
 */
get_header();

$vip_page = qk_get_option('qk_vip_page');

?>
<div class="vip-page">
    <div class="vip-bg"></div>
    <div class="vip-header">
        <div class="wrapper">
            <h1><?php echo $vip_page['title'] ?></h1>
            <div class="desc"><?php echo $vip_page['desc'] ?></div>
        </div>
    </div>
    <div class="vip-body wrapper" ref="vipPage">
        <div class="content-area">
            <main id="main" class="site-main">
                <div class="vip-list" v-cloak>
                    <div class="vip-item" v-for="(item,index) in data">
                        <div class="timedown-box" v-if="item.vip.discount < 100">
                            <div class="time-countdown-box">
                                <span class="time">{{ time.hour }}</span>
                                <span class="time">{{ time.minute }}</span>
                                <span class="time">{{ time.second }}</span>
                                <span class="time ms">{{ time.millisecond }}</span>
                            </div>
                        </div>
                        <h2 v-text="item.name"></h2>
                        <div class="subtitle" style="margin-top: 8px;color: var(--color-text-secondary);" v-text="item.desc"></div>
                        <div class="vip-price">
                            <span class="unit">￥</span>
                            <span class="num">{{Math.ceil(item.vip.price * (item.vip.discount/100))}}.00</span>
                            <span>元</span>
                            <div style="display: inline-block;color: var(--color-text-secondary);">/{{item.vip.name}}起</div>
                            <div class="badge gradient" v-if="item.vip.discount < 100">限时 {{item.vip.discount/10}}折</div>
                        </div>
                        <div class="original-price">
                            <span class="num" v-if="item.vip.discount < 100">￥{{item.vip.price}}.00 /{{item.vip.name}}起</span>
                        </div>
                        <div class="pay-button" style=" padding: 0; margin-top: 16px;" @click="vipPay(index)">
                            <button class="bg-text" style=" border-radius: 50px; ">
                                <span v-if="user.vip.lv && user.vip.lv.slice(3) == index">立即续费</span>
                                <span v-else>立即开通</span>
                            </button>
                        </div>
                        <div class="rights">
                            <ul class="rights-list" style=" margin: 0 10%; ">
                                <li v-if="item.free_read === '1'">
                                    <span class="icon bg-text">
                                        <i class="ri-check-line"></i>
                                    </span>
                                    <span>查看所有隐藏内容 <b>{{item.free_read_count >= 9999 ?'不限':item.free_read_count}}</b> 次/日</span>
                                </li>
                                <li v-if="item.free_download === '1'">
                                    <span class="icon bg-text">
                                        <i class="ri-check-line"></i>
                                    </span>
                                    <span>下载所有资源 <b>{{item.free_download_count  >= 9999 ?'不限':item.free_download_count}}</b> 次/日</span>
                                </li>
                                <li v-if="item.free_video === '1'">
                                    <span class="icon bg-text">
                                        <i class="ri-check-line"></i>
                                    </span>
                                    <span>观看所有视频 <b>{{item.free_video_count  >= 9999 ?'不限':item.free_video_count}}</b> 次/日</span>
                                </li>
                                <li v-if="item.signin_bonus.credit">
                                    <span class="icon bg-text">
                                        <i class="ri-check-line"></i>
                                    </span>
                                    <span>签到额外奖励 <b>{{item.signin_bonus.credit}}</b> 积分/日</span>
                                </li>
                                <li v-if="item.signin_bonus.exp">
                                    <span class="icon bg-text">
                                        <i class="ri-check-line"></i>
                                    </span>
                                    <span>签到额外奖励 <b>{{item.signin_bonus.exp}}</b> 经验/日</span>
                                </li>
                                <li v-if="item.signin_bonus.exp">
                                    <span class="icon bg-text">
                                        <i class="ri-check-line"></i>
                                    </span>
                                    <span>免广告</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div class="vip-footer wrapper">
        <h2>常见问题</h2>
        <div class="vip-faq">
            <div class="collapse" data-accordion="true">
                <?php foreach($vip_page['faqs'] as $v): ?>
                  <div class="collapse-item">
                    <div class="collapse-header"><span><?php echo $v['key'] ?></span><i class="ri-arrow-down-s-line"></i></div>
                    <div class="collapse-content">
                        <div class="text"><?php echo $v['value'] ?></div>
                    </div>
                  </div>
                  <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
<style>
.vip-footer{
    padding-top: 60px;
    padding-bottom: 60px;
    position: relative;
}

.vip-footer h2 {
    font-size: 24px;
    margin-bottom: 24px;
    text-align: center;
}

.vip-faq {
    margin: auto 12%;
}



.vip-page {
    position: relative;
    height: 100%;
}

.vip-bg {
    position: absolute;
    top: -16px;
    width: 100%;
    height: 100%;
    background: url(/wp-content/themes/qkua/Assets/fontend/images/bg-header-base.svg) center center no-repeat;
    background-size: contain;
    z-index: 0;
}

.vip-body.wrapper {
    position: relative;
    background: url(/wp-content/themes/qkua/Assets/fontend/images/bg-header.png) center center no-repeat;
    background-size: auto;
}



.vip-header {
    text-align: center;
    padding-top: 44px;
    padding-bottom: 60px;
}

.vip-header h1 {
    font-size: 35px;
    margin-bottom: 12px;
}

.vip-list {
    display: flex;
    flex-flow: wrap;
    justify-content: center;
    grid-gap: 24px;
    min-height: 456px;
}

.vip-list[v-cloak]{
    display: flex!important;
    visibility: hidden;
}

.vip-item {
    padding: 16px;
    width: calc(25% - 12px);
    text-align: center;
    border-radius: var(--radius);
    border-radius: 12px;
    background: var(--bg-main-color);
    min-width: 288px;
    box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.05);
    position: relative;
}

.vip-item h2 {
    font-size: 24px;
}

.vip-item >* + * {
    margin-top: 24px;
    position: relative;
}

.vip-price {color: var(--color-primary);}

.vip-price .num {
    font-size: 28px;
    font-weight: 600;
}

.vip-price span:last-of-type {
    font-size: 12px;
    line-height: 1;
    border-radius: 50%;
    margin-left: -14px;
    padding: 2px 4px;
    background: var(--bg-main-color);
    object-fit: contain;
    transform: scale(.9);
    display: inline-block;
}

.vip-price .badge {
    right: 20px;
    top: -4px;
    border-radius: 25px;
}

span.unit {
    font-weight: 600;
    font-size: 16px;
}
span.icon.bg-text {
    border-radius: 100%;
    padding: 1px;
    line-height: 1;
    margin-right: 4px;
}

.rights-list li {
    margin-top: 12px;
    display: flex;
    align-items: center;
    font-size: 13px;
    color: var(--color-text-regular);
}

ul.rights-list b {
    color: var(--color-primary);
    font-weight: normal;
    vertical-align: bottom;
}

.original-price {
    margin-top: 4px;
    line-height: 16px;
    text-decoration: line-through;
    color: var(--color-text-placeholder);
    min-height: 16px;
}







.timedown-box {
    position: absolute;
    z-index: 0;
    top: -32px;
    left: 0;
    width: 100%;
    height: 52px;
    background: url(/wp-content/themes/qkua/Assets/fontend/images/countdown-bg.png) no-repeat 50%;
    background-size: cover;
}

.time-countdown-box {
    display: flex;
    padding-top: 4px;
    padding-left: 28.5%;
}

.time-countdown-box .time {
    margin-right: 10.5%;
    width: 24px;
    height: 24px;
    letter-spacing: 1px;
     font-family: Impact; 
    font-weight: 400;
    font-size: 12px;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
}

.timedown-box:after {
    content:"";
    width: 100%;
    display: block;
    height: 20px;
    background: var(--bg-main-color);
    /* box-shadow: 0px -2px 2px 0px rgba(0, 0, 0, 0.05); */
    border-radius: 12px 12px 0 0;
    position: absolute;
    bottom: 0;
}


.timedown-box + h2 {
    margin: 0;
}

@media screen and (max-width:768px){
    .vip-item {
        margin-top: 32px;
    }
}
</style>
<?php

get_footer();