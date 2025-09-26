<?php
namespace Qk;

use Qk\Modules\Settings\Main as SettingsLoader;
use Qk\Modules\Templates\Main as TemplatesLoader;
use Qk\Modules\Common\Main as CommonLoader;
use Qk\Modules\Common\Module as hhh;
if ( ! class_exists( 'Qk', false ) ) {
    class Qk{
        public function __construct(){

            spl_autoload_register('self::autoload');

            $this->load_library();

            $this->load_modules();
            
        }

        /**
         * 加载外部依赖
         *
         * @return void

         * @version 1.0.0
         * @since 2023
         */
        public function load_library(){

            $is_admin = is_admin() || $GLOBALS['pagenow'] === 'wp-login.php';
           
            try {
                $ext = new \ReflectionExtension('tonyenc');
                $ver = $ext->getVersion();

                if($ver < '1.0.0'){
                    if(!$is_admin){
                        wp_die('<h2>'.__('系统维护中.....','QK').'</h2><p>'.__('如果您是管理员，请登陆后台操作','QK').'</p>');
                    }
                }else{
                    try {
                        preg_match("#^\d.\d#", PHP_VERSION, $p_v);
    
                        require_once QK_THEME_DIR .QK_DS.'Modules'.QK_DS.'Common'.QK_DS.'Private'.QK_DS.'private'.$p_v[0].'.php';
                        require_once QK_THEME_DIR .QK_DS.'Modules'.QK_DS.'Common'.QK_DS.'Private'.QK_DS.'filter'.$p_v[0].'.php';
                    }catch (\Throwable $th) {
                        
                        wp_die('<h2>'.__('请重启一下php','QK').'</h2><p>'.__('显示这个页面说明扩展未能正确加载，请重启一下您的PHP','QK').'</p>');
                        
                    }
                }
            } catch (\Throwable $th) {
                if(!$is_admin){
                    wp_die('<h2>'.__('系统维护中.....','QK').'</h2><p>'.__('如果您是管理员，请登陆后台操作','QK').'</p>');
                }
            }
            
            
            //Jwt_Auth 鉴权
            if($is_admin && (!defined('AUTH_KEY') || strlen(AUTH_KEY) < 64)){
                add_filter( 'admin_notices', function (){
                    echo '<div class="notice notice-error"><p>wordpress AUTH_KEY 未有设置</p></div>';
                });
            }

            if(!class_exists('Jwt_Auth')){

                if(!defined('JWT_AUTH_SECRET_KEY')){
                    define('JWT_AUTH_SECRET_KEY', strrev(AUTH_KEY));
                }
    
                if(!defined('JWT_AUTH_CORS_ENABLE')){
                    define('JWT_AUTH_CORS_ENABLE', true);
                }
    
                require_once QK_THEME_DIR .QK_DS.'Library'.QK_DS.'jwt'.QK_DS.'jwt-auth.php';
                
            }else{
                if($is_admin){
                    add_filter( 'admin_notices', function (){
                        echo '<div class="notice notice-error"><p>Qkua主题不兼容 JWT（JWT Authentication for WP-API） 插件，请到插件页面删除插件</p></div>';
                    });
                }
            }
            
            //if($is_admin){
                
                //加载 https://pucqx.cn/4519.html
                require_once QK_THEME_DIR.QK_DS.'Library'.QK_DS.'codestar-framework'.QK_DS.'codestar-framework.php';
            //}
            
            //加载图片裁剪库
            require QK_THEME_DIR.'/Library/Grafika/Grafika.php';
            
            /**
             * 加载WeChatDeveloper
             * 
             * @version 1.0.3
             * @since 2023/9/3
             */
            require_once QK_THEME_DIR.QK_DS.'Library'.QK_DS.'WeChatDeveloper'.QK_DS.'include.php';
            
        }

        /**
         * 加载模块
         *
         * @return void

         * @version 1.0.0
         * @since 2023
         */
        public function load_modules(){
            
            // 加载 模块拓展
            foreach (\Qk\Modules\Common\Module::qk_get_active_and_valid_modules() as $plugin) {
                include_once $plugin;
            }
            
            //加载设置项
            if(is_admin()){
                $settings = new SettingsLoader();
                $settings->init();
            }

            //加载公共类
            $common = new CommonLoader();
            $common->init();

            //加载模板
            $templates = new TemplatesLoader();
            $templates->init();
        }

        /**
         * 自动加载命名空间
         *
         * @return void

         * @version 1.0.0
         * @since 2023
         */
        public static function autoload($class){

            // //主题模块
            if (strpos($class, 'Qk\\') !== false) {
                $class = str_replace('Qk\\','',$class);
                require_once QK_THEME_DIR.QK_DS.str_replace('\\', QK_DS, $class).'.php';
            }
            
            
            //图片裁剪库
            if(preg_match("/^Grafika\\\/i", $class)){
                $filename = QK_THEME_DIR.QK_DS.'Library'.QK_DS.str_replace('\\', QK_DS, $class).'.php';
                require_once $filename;
            }
        }
    }

    new Qk();
    
}