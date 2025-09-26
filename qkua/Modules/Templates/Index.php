<?php
namespace Qk\Modules\Templates;
//首页模块
use Qk\Modules\Templates\Modules\Sliders;

class Index{
    public function init(){
        add_action('qk_index',array($this,'modules_loader'),10);
    }

    public function modules_loader(){
        
        //首页模块
        $index_settings = qk_get_option('qk_template_index');
        if(!$index_settings){
            echo '<div class="none-index">
                '.sprintf(__('请先前往后台对首页布局进行设置：%s','qk'),'<a style="color:red;border-bottom:1px solid red" href="'.admin_url('/admin.php?page=qk_main_options').'">首页布局设置</a>').'
            </div>';
            return ;
        }
        
        $i = 0;
        
        //是否是移动端
        $is_mobile = wp_is_mobile();
        
        foreach ($index_settings as $k => $v) {
            $i++;
            if(isset($v['module_type']) && $v['module_type']){
                
                //登录显示
                if((int)$v['login_show'] === 1 && !is_user_logged_in()) continue;
                
                $mobile_show = isset($v['module_mobile_show']) ? (int)$v['module_mobile_show'] : 0;
                //不显示（仅用作短代码调用）
                if($mobile_show === 3) continue;
                //仅桌面可见
                if($mobile_show === 1 && $is_mobile) continue;
                //仅移动端可见
                if($mobile_show === 2 && !$is_mobile) continue;
                
                //命名空间
                $namespace = 'Qk\Modules\Templates\Modules\\'.ucfirst($v['module_type']);
                
                $modules =  new $namespace;
                
                //是否开启了小工具
                $widget_show = isset($v['widget_show']) && (int)$v['widget_show'] === 1  ? true : false;
                
                //获取不包含小工具页面宽度
                $v['width'] = !empty($v['slider_width']) ? $v['slider_width'] : qk_get_page_width($widget_show,$v['widget_width']);
                
                $v['is_mobile'] = $is_mobile;
                
                $html = $modules->init($v,$i);
                
                echo !empty($v['slider_width']) ? '<style>
                    .home-item-'.$k.' .wrapper {
                        --wrapper-width: '.$v['slider_width'].'px;
                    }
                </style>':'';
                
                echo '<div id="home-item-'.$v['key'].'" class="home-item home-item-'.$k.' module-'.$v['module_type'].'">
                    <div class="wrapper">';
                
                echo '<div class="home-item-left content-area">'
                        .$html.
                    '</div>';
                    
                if($widget_show) {
                    echo '<div class="home-item-rigth widget-area"'.($v['widget_width'] ? 'style="--sidebar-width:'.$v['widget_width'].'px;"' : '').'>';
                            dynamic_sidebar(isset($v['key']) ? $v['key'] : 'index_widget_'.$k);
                    echo '</div>';
                }
                
                echo '</div></div>';
            }
        }
    }
}
