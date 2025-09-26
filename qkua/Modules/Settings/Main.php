<?php
namespace Qk\Modules\Settings;

/**后台设置**/

class Main{
    public function init(){

        //创建设置页面
        $this->main_options_page();

        //加载后台使用的CSS和JS文件
        add_action( 'admin_enqueue_scripts', array( $this, 'setup_admin_scripts' ),99999 ); //csf_enqueue
        
        //加载自定义图标
        add_filter( 'csf_field_icon_add_icons',function (){
            require QK_THEME_DIR.'/Library/remix-icon.php';
            return get_default_icons();
        });
        
        //加载设置项
        $this->load_settings();
        
        // WordPress上传 支持SVG文件类型
        add_action('upload_mimes', array( $this, 'add_file_types_to_uploads')); 
        
        //保存主题时候保存必要的wp设置
        add_action("csf_qk_main_options_saved", function (){
            /**
             * 刷新固定连接
             */
            flush_rewrite_rules();
        });
    }
    
     /**
     * 先创建设置页面
     *
     * @return void
     * @author
     * @version 1.0.0
     * @since 2023
     */
    public function main_options_page(){

        $prefix  = 'qk_main_options';
        
        //开始构建
        \CSF::createOptions($prefix, array(
            'menu_title'         => 'Qkua主题设置',
            'menu_slug'          => 'qk_main_options',
            'framework_title'    => '七夸（Qkua）主题',
            // 'footer_text'        => 'Qkua主题 V' . wp_get_theme()['Version'],
            // 'footer_credit'      => '<i class="fa fa-fw fa-heart-o" aria-hidden="true"></i> ',
            'theme'              => 'dark', //后台暗黑模式 dark light
            //配置
            'show_in_customizer' => true, //在wp-customize中也显示相同的选项
            'show_reset_section' => true, //标志显示框架的重置部分按钮。
            'show_reset_all'     => false, //显示框架重置按钮的标志。
            
        ));
        
        if ( class_exists('QK_CSF')) {
            //开始构建
            \QK_CSF::instance('qk_main_page',array(
                'menu_title'              => 'Qkua管理设置', //页面的title信息 和 菜单标题
                'menu_slug'               => 'qk_main_page', //别名
                'menu_capability' => false, //向用户显示此菜单所需的功能。
                'save_option' => false,
            ));
        }
        //add_action('admin_notices', array($this,'main_option_page_cb'));
        
    }
    
     public function check_cg(){
        preg_match("#^\d.\d#", PHP_VERSION, $p_v);

        $text = '';
        if($p_v[0] < '7.0'){
            $text = '<h2 class="red">请升级您的PHP，建议使用 PHP7.4</h2>';
        }
        
        if($p_v[0] != '7.4'){
            $text = '<h2 class="red">请升级您的PHP，建议使用 PHP7.4</h2>';
        }

        if($p_v[0] >= '8.0' && DIRECTORY_SEPARATOR == '\\'){
            $text = '<h2 class="red">Win系统暂不支持php8.0，请切换到php7.0至php7.4之间的版本</h2>';
        }

        if($p_v[0] >= '8.0'){
            $text = '<h2 class="red">当前版本暂未支持 php8.0，建议使用 php7.4 版本</h2> ';
        }

        $loader_name = PATH_SEPARATOR==':' ? 'loader'.str_replace('.','',$p_v[0]).'.so' : 'win_loader'.str_replace('.','',$p_v[0]).'.dll';

        $path = QK_THEME_DIR;

        $path =  PATH_SEPARATOR!=':' ? str_replace('/',QK_DS,$path) : $path;

        if(!$text){
            if(!extension_loaded('tonyenc')){
                $text = '<div class="notice notice-warning">
                <h2 style="color:#fd4c73;">请先安装扩展</h2>
                <p>'.__('未安装扩展，请按照下面的方法进行安装','qk').'</p>
                <p>'.sprintf(__('1、打开您的php.ini文件（%s），然后将%s复制到php.ini文件的最后一行保存','qk'),'<code>'.php_ini_loaded_file().'</code>','<code>extension='.$path.QK_DS.'Assets'.QK_DS.'admin'.QK_DS.'loader'.QK_DS.$loader_name.'</code>').'</p>
                <p>'.__('2、重启php','qk').'</p>
                <p>'.__('3、刷新本页后激活','qk').'</p>
                </div>';
            }
        }

        return $text;
    }
    
    /**
     * 设置页面首页，欢迎页面
     *
     * @return string
     * @author
     * @version 1.0.0
     * @since 2023
     */
    public function main_option_page_cb(){
        $status = apply_filters('qk_theme_check', 'check');

        $status = $status === true || $status === 'test' ? true : false;
        
        $id = apply_filters('qk_get_theme_id',1);
        $id = isset($id['id']) ? (int)$id['id'] : '';
        
//         echo '<div class="notice notice-warning">'.$text.'</div>';
        $text = $this->check_cg();
        
        if($text) {
            echo $text;
        }else if($status){
            echo '<div id="authorization_form" class="ajax-form" ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '">
            <p style="color: #4caf50; font-size: 16px; font-weight: 600;"> 恭喜您! 已完成授权</p>
            <input type="hidden" ajax-name="action" value="admin_delete_aut">
            <a id="authorization_submit" class="but c-red ajax-submit">撤销授权</a>
            <div class="ajax-notice"></div>
            </div>';
        }else{
            echo '
            <div id="authorization_form">
                <p style="color:#fd4c73;">激动人心的时候到了！即将开启建站之旅！</p>
                <div>
                    <input class="regular-text" type="text" value="" placeholder="请输入会员号">
                </div>
                <a id="authorization_submit" class="but c-blue ajax-submit curl-aut-submit">一键授权</a>
                <div class="ajax-notice"></div>
            </div>';
        }
    }
    
    /**
     * 将SVG文件类型添加到WordPress上传文件类型中
     *
     * @param array $file_types 当前已允许的文件类型
     * @return array $file_types 添加了SVG文件类型的新文件类型数组
     */
    function add_file_types_to_uploads($file_types){
        $new_filetypes = array();
        $new_filetypes['svg'] = 'image/svg+xml'; // 添加SVG文件类型
        $file_types = array_merge($file_types, $new_filetypes );
        return $file_types;
    }

    /**
     * 再加载后台的设置页面及设置项
     *
     * @return bool
     */
    public function load_settings(){
        
        if(apply_filters('qk_check_role',0)){
            
            //数据统计
            $echarts = new Echarts();
            $echarts->init();
            
            do_action('qk_setting_action');
        
            //常规设置
            $normal = new Normal();
            $normal->init();
            
            //模块设置
            $template = new Template();
            $template->init();
            
            //用户相关
            $users = new Users();
            $users->init();
            
            //社区圈子
            $circle = new Circle();
            $circle->init();
            
            //模块安装
            // $module = new Module();
            // $module->init();
            
            //系统相关
            $system = new System();
            $system->init();
            
            //备份设置
            $template = new Backup();
            $template->init();
            
            //Tax分类页面设置项
            $tax = new Taxonomies();
            $tax->init();
            
            //文章类型页面设置项
            $post = new Post();
            $post->init();
            
            //视频类型页面设置项
            $Video = new Video();
            $Video->init();
            
            //卡密管理页面设置项
            $card = new Card();
            $card->init();
            
            //消息管理设置
            $message = new Message();
            $message->init();
            
            //订单管理设置
            $orders = new Orders();
            $orders->init();
            
            //提现管理设置
            $withdrawal = new Withdrawal();
            $withdrawal->init();
            
            //举报管理设置
            $report = new Report();
            $report->init();
            
            //提现管理设置
            $verify = new Verify();
            $verify->init();
            
        }
        
        \CSF::createSection('qk_main_options', array(
            'title'  => '主题授权',
            'icon'   => 'fa fa-fw fa-gitlab',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => '<h3 style="color:#fd4c73;"><i class="fa fa-heart fa-fw"></i> 感谢您使用Qkua奇夸主题</h3>
                    <div><b>首次使用请按照下方提示操作</b></div>
                    <p>Qk主题是一款模块化，精致主题！创作不易，支持正版，从我做起！</p>
                    <div style="margin:10px 14px;"><li>Qkua奇夸主题官网：<a target="_bank" href="https://www.qkua.com">https://www.qkua.com</a></li>
                    <li>作者联系方式：<a href="http://wpa.qq.com/msgrd?v=3&amp;uin=3130153916&amp;site=qq&amp;menu=yes" target="_blank">QQ 3130153916</a></li>
                    </div>',
                ),
                array(
                    'type'     => 'callback', //回调
                    'function' => array($this,'main_option_page_cb')
                ),
            )
        ));
    }
    
    /**
    * 获取设置项
    *
    * @param string $where 设置项的组别，默认是某个组别设置项的类名
    * @param string $key 设置项的KEY
    *
    * @return string
    * @return int
    * @return array
    * @author
    * @version 1.0.0
    * @since 2023
    */
    public static function get_option($key = ''){

        global $_GLOBALS;

        if(isset($_GLOBALS['qk_main_options'])) {
            $settings = $_GLOBALS['qk_main_options'];
        } else {
            $settings = get_option('qk_main_options');
            $_GLOBALS['qk_main_options'] = $settings;
        }
        
        if($key == '') {
            return $settings;
        } else if(isset($settings[$key])){
            return $settings[$key];
        }
    
        return '';
    }
    
    /**
     * 加载后台使用的CSS和JS文件
     *
     * @return void
     * @author
     * @version 1.0.0
     * @since 2023
     */
    public function setup_admin_scripts(){ 
        
        wp_enqueue_script( 'qk-admin',QK_THEME_URI.'/Assets/admin/admin.js?v='.QK_VERSION, array(), QK_VERSION, true );
        wp_enqueue_style( 'qk-admin', QK_THEME_URI.'/Assets/admin/admin.css?v='.QK_VERSION, QK_VERSION, null);
        wp_enqueue_style( 'qk-fonts', QK_THEME_URI.'/Assets/fontend/fonts/remixicon.css?v='.QK_VERSION , array() , QK_VERSION , 'all');
        
        global $pagenow;
        
        if(in_array( $pagenow, array( 'post.php', 'post-new.php' ) )){
            $download_template = qk_get_option('single_post_download_template_group');
            $download_template = is_array($download_template) ? $download_template : array();
    
            wp_localize_script( 'qk-admin', 'qkdownloadtemplate',$download_template);
        }
    }
}
