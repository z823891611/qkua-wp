<?php 
namespace Qk\Modules\Templates;
use Qk\Modules\Common\Message;

class Footer{
    
    public function init(){
        //add_action('get_footer', array($this,'footer_html'));
        add_action('qk_content_after', array($this,'footer_html'));
        add_action('wp_footer',array($this,'footer_settings'),10);
        add_action('qk_footer_after',array($this,'mobile_footer_tabbar'),10);
        // add_action('wp_footer',array($this,'weixin_share'), 9999);
        //add_action('qk_footer_top',array($this,'modules_loader'), 10);
    }
    
    public function footer_html(){
        //vue组件相关的html
       // echo self::vue_template();
    }
    
    public function footer_settings(){
        $footer_html = qk_get_option('footer_code');
        if($footer_html){
           echo $footer_html;
        }
    }
    
    public static function vue_template(){ 
        $html = '
        <div id="modal" class="modal" ref="modal" @touchmove.prevent="" @wheel.prevent="">
            <div class="modal-backdrop"></div>
            <div class="modal-dialog" :style="style">
                <div class="modal-content" v-if="html" v-html="html" @click.stop=""></div>
                <div class="modal-content" @click.stop="" v-else >
                    <component :is="component" :data="data" :show="show" ref="child"><span class="close"><i class="ri-close-line" @click="close"></i></span></component>
                </div>
                <!--<div class="touch-close" @touchstart="handlerTouchstart" @touchmove="handlerTouchmove($event)" @touchend="handlerTouchend" v-if="clientWidth < 756">关闭</div>-->
            </div>
        </div>';
        echo $html;
    }
    
    public static function mobile_footer_tabbar(){
        if (!wp_is_mobile()) return;
        
        if(apply_filters('qk_is_page', 'write') || apply_filters('qk_is_page', 'moment')) return;

        // 获取当前页面的URL
        //$current_url = home_url(add_query_arg(array(), $wp->request));
        
        $tabbar = qk_get_option('footer_mobile_tabbar');
        
        if(empty($tabbar) || !is_array($tabbar)) return;
        
        $sidebar_list = array();
        
        $html = '<div class="footer-mobile-tabbar mobile-show">
                <div class="toolbar-inner">';
        
        foreach ($tabbar as $value){
            
            $type = $value['type'];
            
            $name = '';
            
            if(!empty($value['name'])) {
                $name = '<div class="name">'.$value['name'].'</div>';
            }
            
            $icon = '';
            $current_icon = '';
            if(!empty($value['icon'])) {
                $icon = '<i class="'.$value['icon'].'"></i>';
            }
            
            if(!empty($value['icon_current'])) {
                $current_icon = '<i class="'.$value['icon_current'].'"></i>';
            }
            
            if(!empty($value['icon_html'])) {
                $icon = $value['icon_html'];
            }
            
            if(!empty($value['icon_html_current'])) {
                $current_icon = esc_html($value['icon_html_current']);
            }

            $link = '';
            if(!empty($value['link'])) {
                $link = esc_url($value['link']);
            }
            
            $class = 'no-hover item-' . $type;
            
            if(self::is_current_url($link)){
                $class .= ' current';
                $current_icon = !empty($current_icon) ? $current_icon : $icon;
            }else{
                $current_icon = $icon;
            }
            
            //未登录提示登录
            if(!is_user_logged_in() && (strpos($link,'account') !== false || strpos($link,'message') !== false)){
                $value['event'] = 'createModal(\'login\')';
            }
            
            if(!empty($value['event'])) {
                $link = 'javascript:'.$value['event'];
            }
            
            $badge = '';
            if(!empty($value['badge'])){
                $badge = '<b class="badge">'.$value['badge'].'</b>';
            }
            
            if($type == 'message'){
                $message_count = Message::get_unread_message_count();
                $badge = !empty($message_count['total']) ? '<b class="badge red">'.$message_count['total'].'</b>':'';
            }
            
            if($type == 'custom' || $type == 'message'){
                $html .= '<a href="'.$link.'" class="'.$class.'">
                    <div class="icon">'.$current_icon.'</div>
                    '.$name.'
                    '.$badge.'
                </a>';
            }elseif ($type == 'public') {
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
                
                $html .= '<div class="item-public">
                    <div class="icon">'.$current_icon.'</div>
                    <div class="publish-menu-wrap">
                        <div class="publish-menu-container box">
                            <div class="publish-list">
                            '.$link.'
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }
        
        $html .= '</div>
        </div>';
        
        echo $html;

    }
    
    /**
     * 检查当前URL是否与特定项目URL匹配的函数
     * 
     * @param string $item_url 要与当前URL进行比较的项目URL
     * @return bool 如果当前URL与项目URL匹配，则为true，否则为false
     */
    public static function is_current_url($url) {
        global $wp_rewrite; // 使用全局变量 $wp_rewrite
    
        // 检查是否已经缓存
        if (!isset($GLOBALS['qk_current_url_matches'])) {
    
            $_root_relative_current = untrailingslashit( $_SERVER['REQUEST_URI'] );
    
            // 如果是自定义页面，则在进入比较块之前将查询变量从URL中删除。
            if ( is_customize_preview() ) {
                $_root_relative_current = strtok( untrailingslashit( $_SERVER['REQUEST_URI'] ), '?' );
            }
            
            $current_url        = set_url_scheme( ( is_ssl() ? 'https://' : 'http://' )  . $_SERVER['HTTP_HOST'] . $_root_relative_current );
            $_indexless_current = untrailingslashit( preg_replace( '/' . preg_quote( $wp_rewrite->index, '/' ) . '$/', '', $current_url ) );
        
            $matches = array(
                $current_url,
                urldecode( $current_url ),
                $_indexless_current,
                urldecode( $_indexless_current ),
                $_root_relative_current,
                urldecode( $_root_relative_current ),
            );
            
            // 将结果存入缓存
            $GLOBALS['qk_current_url_matches'] = $matches;
        }else {
            $matches = $GLOBALS['qk_current_url_matches'];
        }
        
        $raw_item_url = strpos( $url, '#' ) ? substr( $url, 0, strpos( $url, '#' ) ) : $url;
        $item_url = set_url_scheme( untrailingslashit( $raw_item_url ) );
        
        $result = $raw_item_url && in_array( $item_url, $matches, true );
    
        return $result;
    }
}