<?php
$user_id =  get_current_user_id();
$credit = get_user_meta($user_id,'qk_credit',true);
$credit = $credit ? (int)$credit : 0;

$money = get_user_meta($user_id,'qk_money',true);
$money = $money ? $money : 0;

$withdrawal = qk_get_option('money_withdrawal');
?>
<div class="assets-page" ref="assetsPage">
    <div class="assets-header">
        <div class="money-card box">
            <div class="assets-title">余额</div>
            <div class="assets-info qk-flex">
                <div class="assets-info-left qk-flex">
                    <span class="unit">￥</span>
                    <span class="num"><?php echo $money ?></span>
                </div>
                <div class="assets-info-rigth">
                    <?php if(qk_get_option('money_withdrawal_open') && !empty($withdrawal)){ 
                        
                        $settings = array(
                            'loading'=>false,
                            'keepAlive'=>false,
                            'props'=>array(
                                'type'=>'money',
                                'money'=>$money,
                                'ratio'=>$withdrawal['ratio'],
                                'limit'=>$withdrawal['limit'],
                            )
                        );
                    ?>
                        <button class="bg-text" onclick='createModal("withdrawal",<?php echo json_encode($settings); ?>)'>提现</button>
                    <?php } ?>
                    <button onclick="createModal('recharge',{props:{type:'balance'}})">充值余额</button>
                </div>
            </div>
            <div class="assets-bottom"></div>
        </div>
        <div class="credit-card box">
            <div class="assets-title">积分</div>
            <div class="assets-info qk-flex">
                <div class="assets-info-left qk-flex">
                    <span class="unit"><i class="ri-copper-diamond-line"></i></span>
                    <span class="num"><?php echo $credit ?></span>
                </div>
                <div class="assets-info-rigth"><button class="bg-text" onclick="createModal('recharge',{props:{type:'credit'}})">购买积分</button></div>
            </div>
            <a class="assets-bottom" href="<?php echo qk_get_account_url('task'); ?>">做任务赚积分</a>
        </div>
    </div>
    <div class="assets-content box qk-radius">
        <div id="tabs" class="tabs">
            <ul class="tabs-nav">
                <li class="active">余额记录</li>
                <li>积分记录</li>
                <div class="active-bar"></div>
            </ul>
            <div class="tabs-content">
                <div class="record-list" v-if="data.length" v-cloak>
                    <div class="record-item" v-for="(item,index) in data">
                        <div class="record-type">
                            <div class="record-title" v-text="item.type_text"></div>
                            <div class="record-value" :class="[item.record_type,{red:item.value < 0}]"><b v-text="item.value > 0 ?'+' + item.value : item.value"></b> {{item.record_type == 'credit' ? '积分' : '元'}}</div>
                        </div>
                        <div class="record-detail">
                            <div class="record-desc" v-text="item.content"></div>
                            <div class="record-date" v-text="item.date"></div>
                        </div>
                    </div>
                </div>
                <div class="loading empty qk-radius" v-else-if="!data.length && loading && !isDataEmpty"></div>
                <template v-else-if="!data.length && isDataEmpty">
                    <?php echo qk_get_empty('暂无记录','empty.svg'); ?>
                </template>
            </div>
            <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1 ), 'json', 'auto','change'); ?>
        </div>
    </div>
</div>
<style>

.tabs-content .empty {
    min-height: 100%; 
}
</style>