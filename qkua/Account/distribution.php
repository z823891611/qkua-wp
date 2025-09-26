<?php
use Qk\Modules\Common\Distribution;
use Qk\Modules\Common\Orders;
use Qk\Modules\Common\User;

$user_id =  get_current_user_id();

$ref = QK_HOME_URI.'?ref='.$user_id;

//$distribution = Distribution::get_user_distribution($user_id);
$types = Orders::get_order_type();
unset($types['money_chongzhi']);

$commission = Distribution::get_user_commission($user_id);
//print_r($distribution);
// $distribution = qk_get_option('distribution');
$roles = User::get_user_roles();
$user_vip = get_user_meta($user_id,'qk_vip',true);
$distribution_lv = $user_vip ?:'lv';

?>
<div class="distribution-page" ref="distributionPage">
    <div class="distribution-header box qk-radius">
        <div class="section-title">佣金收益数据</div>
        <div class="income-box">
            <div class="income-info">
                <div class="income-total">
                    <div class="name">总收益</div>
                    <div class="money">
                        <span class="unit">￥</span><?php echo $commission['data']['total']; ?>
                    </div>
                    <div class="income-withdrawn">已提现 
                        <span class="money">￥<?php echo $commission['withdrawn']; ?></span>
                    </div>
                </div>
                <div class="income-withdraw-amount">
                    <div class="name">可提现</div>
                    <div class="money">
                        <span class="unit">￥</span><?php echo $commission['money']; ?>
                    </div>
                </div>
                <div class="income-withdraw">
                    <?php if(qk_get_option('money_withdrawal_open') && !empty($commission)){ 
                        $withdrawal = qk_get_option('commission_withdrawal');
                        $settings = array(
                            'loading'=>false,
                            'keepAlive'=>false,
                            'props'=>array(
                                'type'=>'commission',
                                'money'=>$commission['money'],
                                'ratio'=>$withdrawal['ratio'],
                                'limit'=>$withdrawal['limit'],
                            )
                        );
                    ?>
                        <button onclick='createModal("withdrawal",<?php echo json_encode($settings); ?>)'>立即提现</button>
                    <?php } ?>
                </div>
            </div>
            <div class="income-count">
                <div class="item">
                    <div class="item-line">
                        <div class="dot"></div>
                        <div class="name">一级收益</div>
                    </div>
                    <div class="count">￥<?php echo $commission['data']['lv1']; ?></div>
                </div>
                <div class="item">
                    <div class="item-line">
                        <div class="dot"></div>
                        <div class="name">二级收益</div>
                    </div>
                    <div class="count">￥<?php echo $commission['data']['lv2']; ?></div>
                </div>
                <div class="item">
                    <div class="item-line">
                        <div class="dot"></div>
                        <div class="name">三级收益</div>
                    </div>
                    <div class="count">￥<?php echo $commission['data']['lv3']; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="distribution-content box qk-radius" style=" flex: 1; ">
        <div id="tabs" class="tabs">
            <ul class="tabs-nav">
                <li class="active">推广详情</li>
                <li>佣金明细</li>
                <li>关联用户</li>
                <div class="active-bar"></div>
            </ul>
            <div class="tabs-content">
                <div class="tab-item article-content" v-if="index == 0">
                    <div class="distribution-info">
                        <div class="row">
                            <span>推广链接：</span>
                            <span class="bg-text" @click="copyText('<?php echo $ref; ?>')"><?php echo $ref; ?></span>
                            <button class="bg-text" @click="copyText('<?php echo $ref; ?>')">复制链接</button>
                            <!--<button>推广海报</button>-->
                        </div>
                        <div class="row">
                            <span>佣金比例：</span>
                            <table>
                                <thead>
                                    <tr>
                                        <td>分销等级</td>
                                        <td>一级关联</td>
                                        <td>二级关联</td>
                                        <td>三级关联</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (qk_get_option('distribution') as $key => $value) { ?>
                                        <tr style="<?php if($distribution_lv == $key){ echo 'background: var(--bg-text-color); color: var(--color-primary);';} ?>">
                                    <?php if(!isset($roles[$key])){?>
                                            <td>普通用户</td>
                                    <?php }else{ ?>
                                            <td><?php echo $roles[$key]['name'] ?></td>
                                    <?php } ?>
                                            <td><?php echo $value['lv1_ratio']; ?>%</td>
                                            <td><?php echo $value['lv2_ratio']; ?>%</td>
                                            <td><?php echo $value['lv3_ratio']; ?>%</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <span>返佣产品：</span>
                            <table>
                                <thead>
                                    <tr>
                                        <td>返佣产品</td>
                                        
                                        <?php foreach (qk_get_option('distribution') as $key => $value) { ?>
                                            <?php if(!isset($roles[$key])){?>
                                            <td style="<?php if($distribution_lv == $key){ echo 'background: var(--bg-text-color); color: var(--color-primary);';} ?>">普通用户</td>
                                            <?php }else{ ?>
                                            <td style="<?php if($distribution_lv == $key){ echo 'background: var(--bg-text-color); color: var(--color-primary);';} ?>"><?php echo $roles[$key]['name'] ?></td>
                                    <?php }} ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($types as $key => $value) { ?>
                                        <tr>
                                            <td><?php echo $value; ?></td>
                                            <?php foreach (qk_get_option('distribution') as $k => $v) { ?>
                                                <td style="<?php if($distribution_lv == $k){ echo 'background: var(--bg-text-color);';} ?>">
                                                    <?php if (in_array($key,$v['types']) !== false) { ?>
                                                        <i class="ri-checkbox-circle-fill" style="color:green"></i>
                                                    <?php } else{ ?>
                                                        <i class="ri-close-circle-fill" style="color:red"></i>
                                                    <?php } ?>
                                                </td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <span>订单佣金：</span>
                            <span>(*级)关联用户订单付款金额 x (*级)佣金比例</span>
                        </div>
                    </div>
                    <div class="distribution-guide">
                        <div class="title">
                            <span>新手推广指南</span>
                        </div>
                        <div class="guide">
                            <P>一、获取推广链接：复制其推广链接或推广海报（登录后生成的分享链接都为你的推广链接），放在论坛/博客/QQ/微信/群聊等页面上吸引客户点击。</P>
                            <P>二、建立客户关联：全新客户通过点击您的推广链接进行注册/登录，即与您建立关联，关联期为永久。</P>
                            <P>三、推广有效订单：客户购买指定返佣产品即生成有效订单与推广佣金。</P>
                            <P>四、获得推广佣金：当佣金积累到50元之后，即可申请提现，申请提现后需后台人工处理，一般2-8小时，请耐心等待。</P>
                        </div>
                    </div>
                </div>
                <div class="tab-item" v-else-if="index == 1 && orderList.length" v-cloak>
                    <div class="record-list" v-cloak>
                        <div class="record-item" v-for="(item,index) in orderList">
                            <div class="record-type">
                                <div class="record-title">{{item.type_text}}</div>
                                <div class="record-value money" :class="[{red:item.value < 0}]"><b v-text="item.value > 0 ?'+' + item.value : item.value"></b> 元</div>
                            </div>
                            <div class="record-detail">
                                <div class="record-desc" v-text="item.content"></div>
                                <div class="record-date" v-text="item.date"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-item" v-else-if="index == 2 && userList.length" v-cloak>
                    <ul class="user-list">
                        <li class="list-item" v-for="(item,index) in userList" :key="index">
                            <a :href="item.link" v-html="item.avatar_html"></a>
                            <div class="user-info">
                                <div class="user-info-name" v-html="item.name_html"></div>
                                <div class="desc" v-text="item.partner_lv"></div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="loading empty qk-radius" v-else-if="!data.length && loading && !isDataEmpty"></div>
                <template v-else-if="!data.length && isDataEmpty">
                    <?php echo qk_get_empty('暂无记录','empty.svg'); ?>
                </template>
            </div>
            <template v-if="index != 0">
                <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1 ), 'json', 'page','change'); ?>
            </template>
        </div>
    </div>
</div>
<style>

/*.tabs-content .empty {*/
/*    min-height: 100%; */
/*}*/

.distribution-page > div{
    padding: 16px;
}

.distribution-page .income-info {
    padding: 20px;
    background: var(--bg-muted-color);
    border-radius: var(--radius);
    display: flex;
    justify-content: space-between;
    color: var(--color-text-secondary);
    font-size: 14px;
    line-height: 14px;
    gap: 24px;
}

.income-withdraw {
    flex: 4;
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.income-total {
    flex: 2;
}

.distribution-page .income-info .name {
}

.distribution-page .income-info > * > .money {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text-primary);
    line-height: 24px;
    margin: 16px 0;
}

.distribution-page .income-info .money .unit {
    font-size: 18px;
}

.distribution-page .income-count .item .count {
    font-size: 16px;
    line-height: 16px;
    font-weight: 600;
}

.distribution-page .income-box .income-count .item {
    background: none;
}

.distribution-page .income-count {
    max-width: 456px;
}

.distribution-page .income-count .item .item-line {
}

.distribution-info {
    display: flex;
    flex-direction: column;
    grid-gap: 20px;
    padding-top: 12px;
}

.distribution-info table td {
    text-align: center;
}
.distribution-info .row > span:first-of-type {
    color: var(--color-text-primary);
    font-size: 15px;
    font-weight: 600;
}

.distribution-info span.bg-text {
    padding: 5px 12px;
    border-radius: var(--radius);
}

.distribution-guide .title {
    display: inline-flex;
    box-sizing: border-box;
    width: 126px;
    height: 30px;
    position: relative;
    align-items: center;
    background-image: url(https://cloudcache.tencent-cloud.com/qcloud/ui/activity-v2/build/cpsControlCard/images/img-cps-guide.png);
    background-size: 100%;
    padding-left: 42px;
    color: var(--color-white);
    font-size: 13px;
    margin-bottom: 16px;
}

.distribution-guide {
    margin-top: 20px;
}

.guide {
    font-size: 14px;
    line-height: 32px;
    color: var(--color-text-primary);
}

.row table {
    margin-top: 12px;
}

.user-list .list-item {
    display: flex;
    align-items: center;
    padding: 16px 0;
}

.user-list .list-item + .list-item{
    border-top: 1px solid var(--border-color-base);
}

.user-list .user-avatar {
    --avatar-size: 42px;
}

.user-list .user-info {
    flex-grow: 1;
    margin: 0 12px;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
}

.user-list .user-info .desc {
    font-size: 12px;
    color: var(--color-text-secondary);
    line-height: 18px;
}

.distribution-info .row:first-of-type {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    grid-gap: 12px;
}
</style>