<?php
namespace Qk\Modules\Templates;

use Qk\Modules\Common\User;
//顶部模块

class Header{

    public function init(){
        add_action('qk_header',array($this,'qk_header'),3);
        add_action('qk_content_before',array($this,'channel_menu'));
        add_filter( 'channel_menu_bottom', array($this,'channel_menu_bottom_more'));
    }
    
    /**
     * 页面顶部
     *
     * @return string 顶部的HTML代码
     * @version 1.0.0
     * @since 2023
     */
    public static function qk_header(){
        
        //$fixed = qk_get_option('top_menu_fixed');
        $options = qk_get_option();
        
        $arg = array(
            'theme_location' => 'top-menu',
            'container_id'=>'top-menu',
            //'container_class'=> 'left-entry',
            'echo' => FALSE,
            'fallback_cb' => '__return_false',
        );
    
        $menu = wp_nav_menu($arg);
        $button = '';
        if(has_nav_menu( 'channel-menu' )) {
            $button = '
            <div class="mobile-show">
                <div id="mobile-menu-button" @click="showMenu" class="menu-icon">
                    <i class="ri-menu-2-line"></i>
                </div>
            </div>';
        }
        
        $html ='
            <header class="header'.($options['header_show_banner'] && (!$options['header_home_show_banner'] || is_home()) ? ' transparent' : '').'">
                <div class="header-top'.($options['top_menu_fixed'] ? ' fixed' : '').'">
                <div class="header-top-wrap wrapper">
                    <div class="left-entry">
                        '.$button.'
                        '.self::logo().'
                        '.$menu.'
                    </div>
                    '.self::search_form().'
                    <div class="right-entry">
                        '.self::search().'
                        '.self::message().'
                        '.self::theme_switch().'
                        '.self::check_in().'
                        '.self::publish().'
                        '.self::user().'
                    </div>
                </div>
                </div>
                '.self::header_banner().'
            </header>
        ';
        
        echo $html;
    }
    
    public static function search_form(){ 
        $show = qk_get_option('top_menu_search_show');
        
        if(!$show) return;
        return '
        <div class="center-entry">
            <div class="menu-search-mask" onclick="mobileSearch.showSearch()"></div>
            <search></search>
        </div>';
    }
    
    /**
     * 顶部LOGO的HTML代码
     *
     * @return string LOGO的HTML字符串
     * @version 1.0.0
     * @since 2023
     */
    public static function logo($h1 = true){
        
        if(is_home() || is_front_page()){
            if($h1){
                $logo = '<h1>'.self::logo_link().'</h1>';
            }else{
                $logo = self::logo_link();
            }
        }else{
            $logo = self::logo_link();
        }
        $html = '<div class="header-logo">'.$logo.'</div>';

        return apply_filters('qk_header_logo',$html);
    }
    
    public static function logo_link(){

        $text_logo = qk_get_option('text_logo');
        $img_logo = qk_get_option('img_logo');

        $html = '<a rel="home" class="logo" href="'.QK_HOME_URI.'">';

        //$body_class = get_body_class();

        if($img_logo){
            $html .= '<img itemprop="logo" src="'.$img_logo.'" alt="'.get_bloginfo('name').'">';
        }else{
            $html .= '<p class="site-title">'.$text_logo.'</p>';
        };
        $html .= '</a>';

        return apply_filters('qk_header_logo_link',$html,$text_logo,$img_logo);
    }
    
    public static function header_banner(){
        
        //是否只在网站首页显示 header_home_show_banner
        $banner_show = qk_get_option('header_show_banner');
        
        $banner_img = qk_get_option('header_banner_img');

        if(!$banner_show || !$banner_img) return;
        
        $banner_home_show = qk_get_option('header_home_show_banner');
        if($banner_home_show && !is_home()) return;
        
        $img_logo = qk_get_option('img_logo');

        //list( $plugin_file, $plugin_data ) = $item;
        
        $banner_html = '';
        if($banner_img) {
            ////i0.hdslb.com/bfs/archive/956de2627e2cc1a9da53ea1d8762cea33e8ea6e5.png@3840w_360h_1c_90q
            $banner_html = '
            <picture class="banner-img">
                <source srcset="'.$banner_img.'">
                <img src="'.$banner_img.'"> 
            </picture>';
        }
        
        return '
    <div class="header-banner">
        '.$banner_html.'
        <div class="banner-inner">
            <a href="/" class="inner-logo">
                <img class="logo-img" width="162" height="78" src="'.$img_logo.'" alt="'.get_bloginfo('name').'">
            </a>
        </div>
        <div class="taper-line"></div>
    </div>';
    }
    
    public static function message(){
        return '<div class="menu-message">
            <a class="no-hover" rel="nofollow" href="'.qk_get_custom_page_url('message').'">
                <b class="badge red" v-if="count > 0" v-text="count" v-cloak>1</b>
                <i class="ri-notification-3-line"></i>
            </a>
        </div>';
    }
    
    public static function search(){
        return '
        <div class="menu-search mobile-show">
            <div id="mobile-search-button" @click="showSearch" class="search-icon">
                <i class="ri-search-line"></i>
            </div>
        </div>';
    }
    
    /**
     * 暗黑模式切换器的HTML代码
     * @return {string} 包含暗黑模式切换器的HTML代码
     */ 
    public static function theme_switch() {
        $show = qk_get_option('top_menu_theme_switch_show');
        
        if(!$show) return;
        $theme_mode = qk_getcookie('theme_mode');
        $default_theme = qk_get_option('theme_mode');
        $checked = $theme_mode ? ($theme_mode === 'dark-theme' ? 'checked' : '') : ($default_theme === 'dark-theme' ? 'checked' : '');
        return '
    <div class="menu-theme-switch mobile-hidden">
        <label class="theme-toggle dark-mode">
            <input type="checkbox" ' . $checked . '>
            <span class="slider"></span>
        </label>
    </div>
        ';
    }
    
    /**
     * 签到按钮
     */ 
    public static function check_in() {
        $show = qk_get_option('top_menu_check_in_show');
        
        if(!$show) return;
        return '
    <div class="menu-check-in mobile-hidden">
        <div class="check-in-btn" v-text="isCheckIn ? \'已签到\' : \'签到\'">签到</div>
        <div class="check-in-menu-wrap">
            <calendar-checkin :is-check-in="isCheckIn" :consecutive-days="consecutiveDays" @checkin-success="checkin"></calendar-checkin>
        </div>
    </div>
        ';
    }
    
    /**
     * 发布按钮
     */ 
    public static function publish() {
        $show = qk_get_option('top_menu_publish_show');
        
        if(!$show) return;
        $links = qk_get_option('top_menu_publish_links');
        $links = is_array($links) ? $links : array();
        
        $link = '';
        
        foreach ($links as $value) {
            $link .= '
            <a href="'.$value['link'].'" class="publish-item qk-flex" rel="nofollow">
                <div class="img-icon">
                    <img src="'.$value['icon'].'" alt="'.$value['title'].'">
                </div>
                <div class="link-title">
                    <p class="type-text">'.$value['title'].'</p>
                </div>
            </a>';
        }
        
        return '
    <div class="menu-publish-box mobile-hidden">
        <div class="menu-publish-btn bg-text">发布</div>
        <div class="publish-menu-wrap">
            <div class="publish-menu-container box">
                <div class="publish-list">
                '.$link.'
                </div>
            </div>
        </div>
    </div>
        ';
    }
    public static function user() {
        $user_id =  get_current_user_id();
        $links = qk_get_option('top_menu_user_links');
        $links = is_array($links) ? $links : array();
        
        if($user_id) {
            $user_data = User::get_user_public_data($user_id);
            $user_vip = $user_data['vip'];
            
            $credit = get_user_meta($user_id,'qk_credit',true);
            $credit = $credit ? (int)$credit : 0;
            
            $money = get_user_meta($user_id,'qk_money',true);
            $money = $money ? $money : 0;
            
            $avatar_html = $user_data['avatar_html'];
        }else{
            $avatar_html = '<div class="menu-logun-btn" onclick="createModal(\'login\')">登录</div>';
        }
        
        if(user_can($user_id, 'administrator' )) {
            $links[] = array(
                'title' => '后台管理',
                'icon' => 'ri-settings-line',
                'link' => get_admin_url()
            );
        }
        
        $link = '';
        
        foreach ($links as $value) {
            $link .= '
            <a href="'.$value['link'].'" class="link-item qk-flex" rel="nofollow">
                <div class="link-title qk-flex">
                    <i class="'.$value['icon'].'"></i>
                    <span>'.$value['title'].'</span>
                </div>
                <i class="ri-arrow-right-s-line"></i>
            </a>';
        }
        
        return '
        <div class="menu-user-box">
            '.$avatar_html.($user_id ?
    	    '<div class="user-menu-wrap">
                <div class="user-menu-container box">
                    <div class="user-menu-content">
                        <div class="user-info-item qk-flex">
                            <div class="user-info qk-flex">
                                 <a href="'.$user_data['link'].'">
                                    '.$avatar_html.'
                                </a>
                                <div class="user-name">
                                    '.$user_data['name_html'].'
                                    <div class="desc text-ellipsis">'.$user_data['desc'].'</div>
                                </div>
                            </div>
                            <!--<a class="user-info-btn" href="'.$user_data['link'].'">我的主页</a>-->
                        </div>
                        <div class="vip-panel-item qk-flex">
                            <a href="'.qk_get_custom_page_url('vip').'">
                                <div class="vip-panel qk-flex">
                                    <div class="vip-panel-info qk-flex">
                                        <div class="vip-icon"><i class="ri-vip-crown-2-fill"></i></div>
                                        <div class="vip-name">'.(!$user_vip ? '会员' : $user_vip['name']).'</div>
                                        <div class="divider"></div>
                                        <div class="vip-expire-time">'.(!$user_vip ? '未开通' : ( $user_vip['time'] == 0 ? '永久' : $user_vip['time'].'天后到期')).'</div>
                                    </div>
                                    <button class="vip-btn" @click.stop.prevent="payVip()">'.(!$user_vip ? '去开通' : ( $user_vip['time'] == 0 ? '去升级' :'去续费')).'</button>
                                </div>
                            </a>
                        </div>
                        <div class="user-assets-item">
                            <!--<div class="user-assets-title">我的资产</div>-->
                            <div class="user-assets qk-flex">
                                <a href="'.qk_get_account_url('assets').'" class="user-money-card" @click.stop.prevent="recharge(\'balance\')">
                                    <div class="user-assets-name">余额<i class="ri-arrow-right-s-line"></i></div>
                                    <div class="user-assets-num">'.$money.'</div>
                                    <div class="assets-icon" style="background-image: url(https://qhstaticssl.kujiale.com/image/jpeg/1664278568424/38231ED8F3E6F0D2C02BFBDE797335D9.jpg);"></div>
                                </a>
                                <a href="'.qk_get_account_url('assets').'" class="user-credit-card" @click.stop.prevent="recharge(\'credit\')">
                                    <div class="user-assets-name">积分<i class="ri-arrow-right-s-line"></i></div>
                                    <div class="user-assets-num">'.$credit.'</div>
                                    <div class="assets-icon" style="background-image: url(&quot;//qhstaticssl.kujiale.com/image/jpeg/1664278535165/F559D2E3C1E5BA95C7054468BCF5BF95.jpg&quot;);"></div>
                                </a>
                            </div>
                        </div>
                        <div class="links-item">
                            '.$link.'
                        </div>
                        <div class="split-line"></div>
                        <div class="logout-item qk-flex" @click="loginOut()">
                            <i class="ri-logout-circle-r-line"></i>
                            <span>退出登录</span>
                        </div>
                    </div>
                </div>
            </div>':'').'
        </div>';
    }
    
    //左侧菜单
    public static function channel_menu() {
        
        //是否只在网站首页显示
        $channel_show = qk_get_option('header_show_channel');
        
        if($channel_show && !is_home()) return '';
        
        if(apply_filters('qk_is_page', 'video') || apply_filters('qk_is_page', 'episode')) return;
        
        // 定义正则表达式和替换字符串
        $pattern = '/<li><a href="#title">(.*?)<\/a><\/li>/';
        $replacement = '<li class="menu-sub-title"><span>$1</span></li>';
        
        
        // 查询 channel-menu 的菜单项
        $menu = '';
        if ( has_nav_menu( 'channel-menu' ) ) {
            $arg = array(
                'theme_location' => 'channel-menu',
                'container_id' => 'channel-menu',
                'container_class' => '',
                'echo' => false,
                'fallback_cb' => '__return_false',
            );
            
            $menu = wp_nav_menu( $arg );
            // 替换菜单项
            $menu = preg_replace($pattern, $replacement, $menu);
        }
        
        // 查询 channel-menu-bottom 的菜单项
        $menu_bottom = '';
        if ( has_nav_menu( 'channel-menu-bottom' ) ) {
            $arg = array(
                'theme_location' => 'channel-menu-bottom',
                'container_id' => 'channel-menu-bottom',
                'container_class' => '',
                'echo' => false,
                'fallback_cb' => '__return_false',
                'after' => '</ul>' . apply_filters( 'channel_menu_bottom',''),
            );
            
            $menu_bottom = wp_nav_menu( $arg );
            // 替换菜单项
            $menu_bottom = preg_replace($pattern, $replacement, $menu_bottom);
        }
    
        if ( !$menu && !$menu_bottom ) return;
    
        $html = '
        <div class="sidebar-menu">
            <div class="sidebar-menu-inner">
                ' . $menu .  $menu_bottom . '
            </div>
            <div class="sidebar-menu-mask mobile-show" onclick="mobileMenu.showMenu()"></div>
        </div>';
    
        echo $html;
    }
    
    public static function channel_menu_bottom_more( $content ) {
        //是否只在网站首页显示
        $more_open = qk_get_option('sidebar_menu_more_open');
        if(!$more_open) return '';
        
        $theme_mode = qk_getcookie('theme_mode');
        $default_theme = qk_get_option('theme_mode');
        $checked = $theme_mode ? ($theme_mode === 'dark-theme' ? 'checked' : '') : ($default_theme === 'dark-theme' ? 'checked' : '');
        
        $data = qk_get_option('sidebar_menu_more');

        $title = !empty($data['name']) ? $data['name'] : '更多';
        
        $links = !empty($data['links']) && is_array($data['links']) ? $data['links'] : array();
        
        $link = '';
        
        foreach ($links as $value) {
            $link .= '
            <a href="'.$value['link'].'" rel="nofollow" class="no-hover">
                <span>'.$value['title'].'</span>
                <i class="ri-arrow-right-s-line"></i>
            </a>';
        }
        
        // 在这里添加您的自定义内容
        $content .= '
        <div class="more-menu-container" @click.stop="">
            <div class="more-information" @click.stop="show = !show">
                <i class="ri-menu-line"></i>'.$title.'
            </div>
            <div class="more-menu-wrap" v-show="show" v-cloak>
                <div class="box">
                    <div class="more-menu-links">
                        '.$link.'
                        <div class="menu-sub-title"><span>设置</span></div>
                        <div class="qk-flex">
                            <span>切换主题</span>
                            <div class="menu-theme-switch">
                                <label class="theme-toggle dark-mode">
                                    <input type="checkbox" '.$checked.'>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        return $content;
    }
    
}