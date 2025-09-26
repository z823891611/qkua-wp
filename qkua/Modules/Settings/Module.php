<?php
namespace Qk\Modules\Settings;

use Qk\Modules\Common\Module as CommonModule;
use Qk\Modules\Common\FileUpload;

//模块安装设置
class Module{

    //设置主KEY
    public static $prefix = 'qk_main_options';

    public function init(){ 
        
        $this->module_options_page();
    }
    
    /**
     * 模块拓展
     *
     * @return void

     * @version 1.0.0
     * @since 2023
     */
    public function module_options_page(){
        
        \CSF::createSection(self::$prefix, array(
            'id'    => 'qk_module_options',
            'title' => '模块拓展',
            'icon'  => 'fa fa-fw fa-bullseye',
        ));
        
        //加载自定义模块设置
        $this->module_settings();
        
        //模块安装
        \CSF::createSection(self::$prefix, array(
            'parent'     => 'qk_module_options',
            'title'  => '模块安装',
            'icon'   => 'fa fa-fw fa-copy',
            'fields' => array(
                array(
                   'type'    => 'content',
                   'content' => '<div class="ajax-notice"></div>'
                ),
                array(
                   //'id'            => 'qk_pc_module_template',
                   'type'          => 'tabbed',
                   'title'         => '电脑端模块',
                   'tabs'          => array(
                        array(
                          'title'     => '首页',
                          'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('home')
                                ),
                            )
                        ),
                        array(
                           'title'     => '头部',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('header')
                                ),
                            )
                        ),
                        array(
                           'title'     => '底部',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('footer')
                                ),
                            )
                        ),
                        array(
                           'title'     => '文章',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('post')
                                ),
                            )
                        ),
                        array(
                           'title'     => '页面',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('single')
                                ),
                            )
                        ),
                        array(
                           'title'     => '小工具',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('widget')
                                ),
                            )
                        ),
                    )
                ),
                array(
                   //'id'            => 'qk_pc_module_template',
                   'type'          => 'tabbed',
                   'title'         => '公共模块',
                   'tabs'          => array(
                        array(
                          'title'     => '页面模板',
                          'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('public/page')
                                ),
                            )
                        ),
                        array(
                           'title'     => '小部件',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('public/gadget')
                                ),
                            )
                        ),
                        array(
                           'title'     => '函数文件',
                           'fields'    => array(
                                array(
                                   'type'    => 'content',
                                   'content' => $this->module_settings_content('public/function')
                                ),
                            )
                        ),
                    )
                )
            )
        ));

    }
    
    public function module_settings_content($type){
        
        //默认模块配置
        $default_module_info = array(
            'ModuleName'  => '默认模块',
            'ModuleURI'   => 'https://127.0.0.1',
            'ModuleType'  => '官方模块',
            'AuthorName'  => '青青草原上',
            'AuthorURI'   => '',
            'Version'     => '1.0',
            'Description' => '这是主题程序默认自带模块',
            'Logo'        => 'http://www.qkua.com/wp-content/uploads/2022/08/preference.png'//get_template_directory_uri().'/images/preference.png'
        );
    
        $module_info = CommonModule::module_exist_info($type,$default_module_info);
        
        if(isset($module_info['error'])) {
            $module_info = $default_module_info;
        }
        
        $option = get_option('qk_active_modules');
        
        $active = '<div class="active" style=" background-color: #0085ba; color: #fff; padding: 6px 8px; border-radius: 2px; cursor: pointer; margin: 0 5px; " onclick="qk_module_option(\''.$type.'\',\'active\',this)">
                        <i class="fa fa-plug"></i> 启用
                        </div>';
        $remove = '<div class="remove" style=" color: #fff; padding: 6px 8px; border-radius: 2px; cursor: pointer; background-color: #e14d43; margin: 0 5px; " onclick="qk_module_option(\''.$type.'\',\'remove\',this)">
                        <i class="fa fa-trash"></i> 卸载
                    </div>';
                        
        $close = '<div class="close" style=" background-color: #9E9E9E; color: #fff; padding: 6px 8px; border-radius: 2px; cursor: pointer; margin: 0 5px; " onclick="qk_module_option(\''.$type.'\',\'close\',this)">
                    <i class="fa fa-close"></i> 关闭
                </div>';
        $update = '<label>
                <div class="qk-upload" style=" background-color: #24aa42; color: #fff; padding: 6px 8px; border-radius: 2px; cursor: pointer; ">
                    <i class="fa fa-cloud-upload"></i> '.(isset($option[$type]) && $option[$type] ? '更新': '上传').'
                </div>
                <input accept=".zip" class="qk-upload-file" type="file" module_type="'.$type.'" name="file" style="display: none;">
            </label>';
        
        $btn = '<div class="btn" style=" position: absolute; right: 0; display: flex; ">';
        if(isset($option[$type]) && !$option[$type]) {
            $btn .= $active . $remove;
        }else if(isset($option[$type]) && $option[$type]) {
            $btn .= $update .$close. $remove;
        }else {
            $btn .= $update;
        }
        
        $btn .= '</div>';
        return '
        <div style=" margin-top: 15px; position: relative; ">
            '.$btn.'
            <div class="content" style=" display: flex; ">
                <div class="cover" style=" margin-right: 15px; ">
                    <img src="'.$module_info['Logo'].'" style=" width: 100px; height: 100px; object-fit: cover; border-radius: 10px; ">
                </div>
                <div class="info">
                    <div class="name" style=" font-size: 20px; margin-bottom: 15px; line-height: 26px; ">'.$module_info['ModuleName'].'</div>
                    <div class="author" style=" margin-bottom: 15px; color: #999;">
                        <span style=" margin-right: 15px;">作者：'.$module_info['AuthorName'].'</span>
                        <span>版本：'.$module_info['Version'].'</span>
                    </div>
                    <div class="bug">
                        <span style=" margin-right: 15px; color: #999; "><i class="fa fa-check-square-o"></i> '.$module_info['ModuleType'].'</span>
                        <a href="'.$module_info['ModuleURI'].'" target="_blank" style=" color: #2196F3; ">
                            <i class="fa fa-edit"></i> 问题与建议反馈
                        </a>
                    </div>
                </div>
            </div>
            <div class="desc" style=" color: #333333; background: #f7f7f7; padding: 15px; border-radius: 3px; margin-top: 15px; line-height: 22px; ">'.$module_info['Description'].'</div>
        </div>';
    }
    
    //自定义模块钩子
    public function module_settings(){
        $settings = apply_filters('qk_module_settings',array());
        
        if($settings){
            foreach ($settings as $item) {
                if(is_array($item) && isset($item['title']) && $item['title']){
                    \CSF::createSection(self::$prefix, array_merge(array(
                        'parent'  => 'qk_module_options',
                    ), $item));
                }
            }
        }
    }
    
    //启用模块
    public function module_active_and_valid_settings($request){
        $user_id = get_current_user_id();
        
        //模块类型
        if(!user_can($user_id, 'manage_options')) return array('error'=>__('只能管理员操作','qk'));
        
        //模块类型
        $module_type_list = apply_filters('module_active_and_valid_settings',array('active','close','remove'));
        
        if(!isset($request['type'])) return array('error'=>__('请设置一个type','qk'));

        if(!in_array($request['callback'],$module_type_list)) return array('error'=>__('不支持这个callback','qk'));
        
        $callback = $request['callback'];
        
        self::$callback($request['type']);
        
    }
    
    //启用模块
    public function active($type) {
        $option = get_option('qk_active_modules');
        $option[$type] = $type.'/functions.php';
        
        update_option('qk_active_modules',$option);
    }
    
    //关闭/禁用模块
    public function close($type) {
        $option = get_option('qk_active_modules');
        $option[$type] = '';
        
        update_option('qk_active_modules',$option);
    }
    
    //删除模块
    public function remove($type) {
        $option = get_option('qk_active_modules');
        unset($option[$type]);
        update_option('qk_active_modules',$option);
        
        //清空目录
        FileUpload::del_directory_file(QK_MODULES_URI.$type . '/');
    }
}