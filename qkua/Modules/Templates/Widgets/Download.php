<?php namespace Qk\Modules\Templates\Widgets;

class Download {

    //小工具slug
    protected $widget_slug = 'qk_widget_download';

    //短代码名
    protected static $shortcode = 'qk_widget_download';
    
    public function __construct() {

        \CSF::createWidget( $this->widget_slug, array(
            'title'       => 'Qk-文章内页下载小工具',
            'classname'   => $this->widget_slug,
            'description' => '“文章内页下载”小工具（只在内页生效）',
            'fields'      => array(
                array(
                    'id'      => 'mobile_show',
                    'type'    => 'select',
                    'title'   => '移动端是否可见',
                    'options' => array(
                        1     => '显示', 
                        0     => '隐藏',
                    ),
                    'default' => 0,
                ),
            )
        ));
    }
    
    /**
     * 显示小工具
     *
     * @param [type] $args
     * @param [type] $instance
     *
     * @return void
     * @version 1.0.0
     * @since 2023
     */
    public static function widget( $args, $instance ) {
        
        $post_id = get_the_ID();
        $user_id = get_current_user_id();
        
        $download_open = get_post_meta($post_id,'qk_single_post_download_open',true);

        //是否开启文章下载功能
        if(!$download_open) return;
        
        $html = '
            <div class="scroll-tabs-wrapper" ref="scrollTab" v-if="list.length > 1">
                <ul class="tabs-content">
                    <li :class="[\'tab-item\',{\'active\':i === index}]" v-for="(item,i) in list" :key="i" @click="changeTab(i)">
                        <span v-text="item.title"></span>
                    </li>
                </ul>
            </div>
            <div class="qk-download-box box qk-radius" v-if="data">
                <div class="title">
                    <h2 v-text="data.title"></h2>
                </div>
                <div class="rights" v-if="data">
                    <div @click="show = !show" :class="[{open:show}]">
                    
                        <div class="current-user"  v-if="!data.current_user.can.allow">
                            <span v-if="data.current_user.can.type == \'money\'">需支付 <span style=" color: var(--color-primary); ">¥</span> <span style=" font-size: 20px; color: var(--color-primary); " v-text="data.current_user.can.value">28</span>
                            </span>
                            <span v-if="data.current_user.can.type == \'credit\'">需支付 <span style=" font-size: 20px; color: var(--color-primary); " v-text="data.current_user.can.value"></span><span style=" color: var(--color-primary); "> 积分</span>
                            </span>
                            <span v-if="data.current_user.can.type == \'free\'">免费下载</span>
                            <span v-if="data.current_user.can.type == \'comment\'">评论后下载</span>
                            <span v-if="data.current_user.can.type == \'login\'">登录后下载</span>
                            <span v-if="data.current_user.can.type == \'password\'">输入密码下载</span>
                            <span v-if="data.current_user.can.type == \'none\'">当前为指定权限用户下载</span>
                        </div>
                        <div class="current-user" v-else>
                            <span v-if="data.current_user.can.free_count">今日免费下载剩余 <span style=" font-size: 20px; color: var(--color-primary); ">{{data.current_user.can.free_count}}</span> 次 </span>
                            <span v-else>已获得下载权限</span>
                        </div>
                        
                        <i class="ri-arrow-right-s-line"></i>
                    </div>
                    <ul class="list" v-if="show">
                        <li class="item" v-for="(item,index) in data.rights" :class="item.lv == data.current_user.lv.lv.lv || item.lv == data.current_user.lv.vip.lv ? \'current\' : \'\'">
                            <div>
                                <span>{{item.name}}</span>
                            </div>
                            <div>
                                <span v-if="item.type == \'money\'">￥<span v-text="item.value"></span></span>
                                <span v-if="item.type == \'credit\'"><span v-text="item.value"></span> 积分</span>
                                <span v-if="item.type == \'free\'">免费下载</span>
                                <span v-if="item.type == \'comment\'">评论后下载</span>
                                <span v-if="item.type == \'login\'">登录后下载</span>
                                <span v-if="item.type == \'password\'">输入密码下载</span>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="download-btn">
                    <button @click="go()">
                        <i class="ri-download-fill"></i>下载
                    </button>
                </div>
                <div class="attrs-list" v-if="data.attrs.length">
                    <div class="attr-item" v-for="(item,index) in data.attrs">
                        <span>{{item.key}}</span>
                        <span>{{item.value}}</span>
                    </div>
                </div>
                <div class="bottom" @click="payVip">开通会员免费下载</div>
            </div>
        ';
        // 如果 $widget 是空的， 重建缓存
        if ( empty( $widget )) {
            $widget = '';
            $args['before_widget'] = str_replace(' box qk-radius', '', $args['before_widget']);
            
            $widget .= !$instance['mobile_show'] ? str_replace('class="','class="mobile-hidden ',$args['before_widget']) : $args['before_widget'];
            $widget .= '<div class="widget-box">';
            $widget .= !empty( $instance['title'] ) ? $args['before_title']. esc_attr( $instance['title'] ) .$args['after_title'] : '';
            $widget .= '<div id="qk-download-box" class="download-widget" ref="downloadBox" v-cloak> '.$html.'</div>';
            $widget .= '</div>';
            $widget .= $args['after_widget'];
            
            
            // if(QK_OPEN_CACHE){
            //     wp_cache_set( $args['cache_id'], $widget, 'widget', WEEK_IN_SECONDS );
            // }
            
        }
        
        echo $widget;
    }
}