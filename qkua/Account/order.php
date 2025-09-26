<div class="order-page" ref="orderPage">
    <div class="order-body">
        <div id="tabs" class="tabs">
            <ul class="tabs-nav box qk-radius" style=" padding: 16px; height: auto; ">
                <li class="active">全部</li>
                <li class="">已完成</li>
                <li class="">待支付</li>
                <div class="active-bar"></div>
            </ul>
            <div class="tabs-content">
                <div class="order-list" v-if="data.length" v-cloak>
                    <div class="order-item" v-for="(item,index) in data">
                        <div class="store-info">
                            <div class="store-name">
                                <i class="ri-store-2-line" style="font-size: 20px;margin-right: 4px;"></i> {{item._order_type}}
                            </div>
                            <div class="order-status" :class="'status-' + item.order_state">{{item._order_state}}</div>
                        </div>
                        <div class="product-info">
                            <div class="product-image">
                                <img :src="item.product.thumb" class="w-h">
                            </div>
                            <div class="product-details">
                                <div class="product-name">{{item.product.name}}</div>
                                <div class="product-quantity">{{item.product.count}}</div>
                            </div>
                            <div class="product-price">{{(item.pay_type == 'credit' ? '积分' : '￥') + item.order_price}}</div>

                        </div>
                        <div class="total-amount"> 实付款 {{(item.pay_type == 'credit' ? '积分' : '￥') + item.order_total}}</div>
                        <div class="order-action">
                            <button class="delete-order" @click="deleteOrder(item)">删除订单</button>
                            <a :href="item.product.whisper" class="button apply-after-sales" v-if="(item.post_id == '0' && isWithin7Days(item.order_date)) || item.post_id != '0'">申请售后</a>
                            <button class="pay-now" v-if="item.order_state == '0'">立即支付</button>
                        </div>
                        <div class="order-info">
                            <div class="order-number">
                                <div class="label">订单编号</div>
                                <div class="value">#{{item.order_id}}</div>
                            </div>
                            <div class="order-date">
                                <div class="label">订单时间</div>
                                <div class="value">{{item.order_date}}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="loading empty qk-radius" v-else-if="!data.length && loading && !isDataEmpty"></div>
                <template v-else-if="!data.length && isDataEmpty">
                    <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
                </template>
            </div>
        </div>
        <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1 ), 'json', 'auto','change'); ?>
    </div>
</div>