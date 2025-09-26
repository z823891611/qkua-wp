<?php namespace Qk\Modules\Templates;
use Qk\Modules\Templates\Single;
//模块加载文件
class Main{
    public function init(){
        
        //加载css和js
        add_action( 'wp_enqueue_scripts', array( $this, 'setup_frontend_scripts' ),10 );
        
        add_filter('wrapper_width', array( $this, 'width'),10,3);
        add_filter('sidebar_width', array( $this, 'width'),10,3);
        
        //加载模板
        $this->load_templates();
    }
    
     /**
     * 加载前台使用的CSS和JS文件
     *
     */
    public function setup_frontend_scripts(){
        
        //禁止加载WP自带的jquery.js
        if (!is_admin() && !apply_filters('qk_is_page', 'write')) {
            wp_deregister_script('jquery');
            wp_deregister_script('l10n');
        }

        //加载css rest
        wp_enqueue_style( 'qk-style-main', get_stylesheet_uri() , array() , QK_VERSION , 'all');
        
        //加载主题样式
        wp_enqueue_style( 'qk-style', QK_THEME_URI.'/Assets/fontend/style.css' , array() , QK_VERSION , 'all');
        
        //幻灯样式
        wp_enqueue_style( 'flickity', QK_THEME_URI.'/Assets/fontend/library/flickity.css' , array() , QK_VERSION , 'all');
        
        //幻灯样式 fancybox
        wp_enqueue_style( 'fancybox', QK_THEME_URI.'/Assets/fontend/library/fancybox.css' , array() , QK_VERSION , 'all');
        
        //饿了么ui
        //wp_enqueue_style( 'element-ui',QK_THEME_URI.'/Assets/fontend/library/element-ui/index.css' , array() , QK_VERSION , 'all');
        
        //加载字体图标
        wp_enqueue_style( 'qk-fonts', QK_THEME_URI.'/Assets/fontend/fonts/remixicon.css' , array() , QK_VERSION , 'all');
        
        if(is_singular()){
        //代码高亮样式
            wp_enqueue_style( 'prism', QK_THEME_URI.'/Assets/fontend/library/highlight/styles/prism.css', array(), QK_VERSION , 'all' );
        }
        
        /************************************************js************************************************/
        //幻灯
        wp_enqueue_script( 'flickity', QK_THEME_URI.'/Assets/fontend/library/flickity.pkgd.min.js', array(), QK_VERSION , true );
        
        //幻灯
        wp_enqueue_script( 'fancybox', QK_THEME_URI.'/Assets/fontend/library/fancybox.umd.js', array(), QK_VERSION , true );
        
        //betterScroll
        wp_enqueue_script( 'betterScroll', QK_THEME_URI.'/Assets/fontend/library/betterScroll.min.js', array(), QK_VERSION , true );
        
        //滑块验证
        //wp_enqueue_script( 'slidercaptcha', QK_THEME_URI.'/Assets/fontend/library/slidercaptcha.js', array(), QK_VERSION , true );
        
        //加载Vue
        wp_enqueue_script( 'vue', QK_THEME_URI.'/Assets/fontend/library/vue.min.js', array(), QK_VERSION , true );
        
        //加载axios
        wp_enqueue_script( 'axios', QK_THEME_URI.'/Assets/fontend/library/axios.min.js', array(), QK_VERSION , true );
        
        //饿了么ui
        //wp_enqueue_script( 'element-ui', '//cdn.bootcdn.net/ajax/libs/element-ui/2.15.13/index.js', array(), QK_VERSION , true );
        
        //瀑布流
        wp_enqueue_script( 'packery', QK_THEME_URI.'/Assets/fontend/library/packery.pkgd.min.js', array(), QK_VERSION , true );
        
        //懒加载
        wp_enqueue_script( 'lazyload', QK_THEME_URI.'/Assets/fontend/library/lazyload.min.js', array(), QK_VERSION , true );
        
        //一个小型的独立脚本，用于自动调整文本区域高度。
        wp_enqueue_script( 'autosize', QK_THEME_URI.'/Assets/fontend/library/autosize.min.js', array(), QK_VERSION , true );
        
        //添加一个指令，用于侦听单击事件并滚动到元素。 //VueScrollTo.scrollTo(element, 500, { easing: 'ease-in' });
        wp_enqueue_script( 'vue-scrollto', QK_THEME_URI.'/Assets/fontend/library/vue-scrollto.js', array(), QK_VERSION , true );
        
        if(qk_get_option('single_highlightjs_open') && is_singular()){
            //代码高亮
            wp_enqueue_script( 'prism', QK_THEME_URI.'/Assets/fontend/library/highlight/prism.js', array(), QK_VERSION , true );
        }
        
        //加载js rest
        wp_enqueue_script( 'qk-main', QK_THEME_URI.'/Assets/fontend/main.min.js', array(), QK_VERSION , true );
        
        //加载用户主页js
        if(is_author()){
            wp_enqueue_script( 'qk-author', QK_THEME_URI.'/Assets/fontend/author.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-author', QK_THEME_URI.'/Assets/fontend/author.css', array(), QK_VERSION , 'all' );
        }
        
         if((is_single() || is_page()) && !is_front_page()){
            wp_enqueue_script( 'qk-single', QK_THEME_URI.'/Assets/fontend/single.min.js', array(), QK_VERSION , true );
        }
        
        if(apply_filters('qk_is_page', 'video')){
            wp_enqueue_script( 'qk-video', QK_THEME_URI.'/Assets/fontend/video.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-video', QK_THEME_URI.'/Assets/fontend/video.css', array(), QK_VERSION , 'all' );
        }
        
        // 获取页面模板文件的路径
        // $template_file = get_page_template();
        if(apply_filters('qk_is_page', 'circle') || apply_filters('qk_is_page', 'moment') || is_tax('circle_cat') || is_tax('topic') || is_search()){
            wp_enqueue_script( 'qk-circle', QK_THEME_URI.'/Assets/fontend/circle.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-circle', QK_THEME_URI.'/Assets/fontend/circle.css', array(), QK_VERSION , 'all' );
        }
        
        if(apply_filters('qk_is_page', 'write')){
            //饿了么ui
            wp_enqueue_script( 'element-ui', '//cdn.bootcdn.net/ajax/libs/element-ui/2.15.13/index.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'element-ui',QK_THEME_URI.'/Assets/fontend/library/element-ui/index.css' , array() , QK_VERSION , 'all');
            //tinymce 编辑器
            //wp_enqueue_script( 'qk-tinymce-editor', QK_THEME_URI.'/Assets/fontend/library/tinymce/tinymce.min.js', array(), QK_VERSION , true );
            wp_enqueue_script( 'qk-write', QK_THEME_URI.'/Assets/fontend/write.min.js', array(), QK_VERSION , true );
            
            wp_enqueue_style( 'qk-write', QK_THEME_URI.'/Assets/fontend/write.css', array(), QK_VERSION , 'all' );
        }
        
        if(apply_filters('qk_is_page', 'message')){
            wp_enqueue_script( 'qk-message', QK_THEME_URI.'/Assets/fontend/message.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-message', QK_THEME_URI.'/Assets/fontend/message.css', array(), QK_VERSION , 'all' );
        }
        
        //个人中心
        if(apply_filters('qk_is_account','')){
            wp_enqueue_script( 'qk-account', QK_THEME_URI.'/Assets/fontend/account.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-account', QK_THEME_URI.'/Assets/fontend/account.css', array(), QK_VERSION , 'all' );
        }
        
        //vip页面
        if(apply_filters('qk_is_page', 'vip')){
            wp_enqueue_script( 'qk-vip', QK_THEME_URI.'/Assets/fontend/vip.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-vip', QK_THEME_URI.'/Assets/fontend/vip.css', array(), QK_VERSION , 'all' );
        }
        
        //认证页面
        if(apply_filters('qk_is_page', 'verify')){
            wp_enqueue_script( 'qk-verify', QK_THEME_URI.'/Assets/fontend/verify.js', array(), QK_VERSION , true );
            wp_enqueue_style( 'qk-verify', QK_THEME_URI.'/Assets/fontend/verify.css', array(), QK_VERSION , 'all' );
        }
        
        
        //加载移动端主题样式
        wp_enqueue_style( 'qk-mobile', QK_THEME_URI.'/Assets/fontend/mobile.css' , array() , QK_VERSION , 'all');
        
        //自定义样式
        $options = qk_get_option();

        //($options['header_show_banner'] && (!$options['header_home_show_banner'] || is_home()) ? 'transparent' : $options['top_menu_bg_color'])
        $css = '    [v-cloak]{
        display: none!important
    }
    :root{
        --site-width:2560px;
        --wrapper-width:'.apply_filters('wrapper_width',$options,$options['wrapper_width'],'wrapper').'px;
        --sidebar-width:'.apply_filters('sidebar_width',$options,$options['sidebar_width'],'sidebar').'px;
        --radius:'.$options['radius'].'px;
        --btn-radius:'.$options['btn_radius'].'px;
        --gap:'.$options['qk_gap'].'px;
        
        --top-menu-width:'.$options['top_menu_width'].'px;
        --top-menu-height:'.$options['top_menu_height'].'px;
        --top-menu-bg-color:'.$options['top_menu_bg_color'].';
        --top-menu-text-color:'.$options['top_menu_text_color'].';
        
        --theme-color:'.$options['theme_color'].';
        --color-primary:var(--theme-color);
        --color-text-primary: #333333; /**主要文字色**/
        --color-text-regular: #61666D; /**常规文字色**/
        --color-text-secondary: #9499A0; /**次要文字色**/
        --color-text-placeholder: #C9CCD0; /****占位文字色*****/
        --border-color-base: #f7f7f7; /**边框色**/
        --border-color-muted: #f7f7f7;
        --color-white: #FFFFFF;
        --bg-body-color:'.apply_filters('bg_color',$options['bg_color']).';
        --bg-main-color:var(--color-white); /**box**/
        --bg-text-color:'.qk_hex2rgb($options['theme_color']).';
        --bg-muted-color: var(--bg-body-color);
    }';
        
        wp_add_inline_style( 'qk-style', $css );
        wp_add_inline_style( 'parent-style', $css );
    }
    
    public static function width($options,$width,$type){
        //$options = qk_get_option();
        
        if($type == 'wrapper') {
            
            //圈子首页
            if(is_post_type_archive('circle') && !empty($options['circle_home_layout']['wrapper_width'])) {
                return $options['circle_home_layout']['wrapper_width'];
            }
            
            //圈子页面
            if(is_tax('circle_cat') && !empty($options['circle_layout']['wrapper_width'])) {
                return $options['circle_layout']['wrapper_width'];
            }
            
            //话题页面
            if(is_tax('topic') && !empty($options['topic_layout']['wrapper_width'])) {
                return $options['topic_layout']['wrapper_width'];
            }
            
            //视频页面
            if(is_singular('video') && !empty($options['qk_video_options']['wrapper_width'])) {
                return $options['qk_video_options']['wrapper_width'];
            }
            
            //剧集页面
            if(is_singular('episode') && !empty($options['qk_episode_options']['wrapper_width'])) {
                return $options['qk_episode_options']['wrapper_width'];
            }
            
            //文章页面
            if(is_singular('post')) {
                global $post;
                if(Single::get_single_post_settings($post->ID,'single_post_style') === 'post-style-video' && !empty($options['single_video_wrapper_width'])) {
                    return $options['single_video_wrapper_width'];
                }
                
                return $options['single_wrapper_width'];
            }
        }
        
        
        if($type == 'sidebar') {
            
            //圈子首页
            if(is_post_type_archive('circle') && !empty($options['circle_home_layout']['sidebar_width'])) {
                return $options['circle_home_layout']['sidebar_width'];
            }
            
            //圈子页面
            if(is_tax('circle_cat') && !empty($options['circle_layout']['sidebar_width'])) {
                return $options['circle_layout']['sidebar_width'];
            }
            
            //话题页面
            if(is_tax('topic') && !empty($options['topic_layout']['sidebar_width'])) {
                return $options['topic_layout']['sidebar_width'];
            }
            
            if(is_singular('video')) {
                if(!empty($options['qk_video_options']['sidebar_width'])) return $options['qk_video_options']['sidebar_width'];
            }
            
            if(is_singular('episode')) {
                if(!empty($options['qk_episode_options']['sidebar_width'])) return $options['qk_episode_options']['sidebar_width'];
            }
            
            if(is_singular('post')) {
                return $options['single_sidebar_width'];
            }
        }
        
        return $width;
    }
    
    /**
     * 加载前台的各个模块
     *
     */
    public function load_templates(){

        // //加载文章形式(公告)
        // $announcement = new PostType\Announcement();
        // $announcement->init();

        //加载顶部模块
        $header = new Header();
        $header->init();

        //加载首页模块
        $index = new Index();
        $index->init();

        //加载文章内页方法
        $single = new Single();
        $single->init();

        //加载存档页面
        $archive = new Archive();
        $archive->init();
        
        //圈子
        $circle = new Circle();
        $circle->init();

        // //专题页面
        // $collection = new Collection();
        // $collection->init();

        //加载Vue组件Template
        $vue_template = new VueTemplates();
        $vue_template->init();

        //加载footer模块
        $footer = new Footer();
        $footer->init();

        //加载小工具
        $widgets = new Widgets();
        $widgets->init();
    }
    
}