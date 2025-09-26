<?php namespace Qk\Modules\Common;
//模块组件

class Module{
    
    public function init(){ 
        
    }
    
    
    //获取模块数据
    public static function module_exist_info($type){
        
        //判断模块是否存在
        if(!($module_data = self::get_module_data(QK_MODULES_URI.$type.'/index.php'))) {
            return array('error'=> '缺少index.php 入口文件');
        }
        
        //判断是否包含模块名称
        if (!isset($module_data['ModuleName']) || !$module_data['ModuleName']) {
            return array('error'=> 'index.php文件不合法！');
        }
        
        //存在模块
        $default_headers = array(
            'ModuleName'  => '模块名称',
            'ModuleURI'   => 'https://127.0.0.1',
            'ModuleType'  => '第三方模块',
            'AuthorName'  => '未知',
            'AuthorURI'   => '',
            'Version'     => '1.0',
            'Description' => '暂无描述，为了安全请勿上传来路不明的模块',
            'Logo'        => 'http://www.qkua.com/wp-content/uploads/2022/08/preference.png',//get_template_directory_uri().'/images/preference.png'
        );
        
        $module_info = array_merge($default_headers, $module_data);
        
        if(file_exists(QK_MODULES_URI.$type.'/logo.jpg')) {
            $module_info['Logo'] = content_url('/module/'.$type.'/logo.jpg');
        }
        
        return $module_info;
    }
    
    //获取模块 $folder 子文件夹 wp-admin\includes\plugin.php get_plugins
    public function get_modules($module_folder = ''){
        
        //获取缓存
        $cache_qk_modules = wp_cache_get('qk_modules');
        if (!$cache_qk_modules) {
            $cache_qk_modules = array();
        }
    
        if (isset($cache_qk_modules[$module_folder])) {
            return $cache_qk_modules[$module_folder];
        }
        
        $qk_modules  = array();
        
        $module_root = WP_CONTENT_DIR . '/module';//QK_MODULES_URI;
        if (!empty( $module_folder)) {
            $module_root .= '/'.$module_folder;
        }
        
        // Files in wp-content/module directory. 打开一个目录，读取它的内容
        $modules_dir  = @opendir( $module_root );
        $module_files = array(); //文件下所有PHP文件
        
        if($modules_dir){
            
            //打开一个目录，读取它的内容
            while (($file = readdir($modules_dir)) !== false) {
                
                //字符串截取
                if (substr($file,0,1) === '.') {
                    continue;
                }
                
                //函数检查指定的文件是否是目录。
                if (is_dir($module_root.'/'. $file)) {
                    
                    //打开子目录
                    $modules_subdir = @opendir($module_root . '/' . $file);
                    
                    if ( $modules_subdir ) {
                        while (($subfile = readdir($modules_subdir)) !== false ) {
                            
                            if (substr($subfile,0,1) === '.') {
                                continue;
                            }
                            
                            //返回字符串的一部分 判断是否是php文件
                            if (substr( $subfile, -4 ) === '.php') {
                                $module_files[] = "$file/$subfile";
                            }
                        }
                        
                        //关闭子目录
                        closedir($modules_subdir);
                    }
                } else {
                    if (substr( $subfile, -4 ) === '.php') {
                        $module_files[] = $file;
                    }
                }
            }
            //关闭子目录
            closedir( $modules_dir );
        }
        
        if (empty($module_files)) {
            return $qk_modules;
        }
        
        return $module_files;
        foreach ($module_files as $module_file){
            
            //检查指定的文件是否可读
            if(!is_readable("$module_root/$module_file")){
                continue;
            }
            
            // Do not apply markup/translate as it will be cached.
            $module_data = self::get_module_data("$module_root/$module_file");
            
            if(empty($module_data['ModuleName'])){
                continue;
            }
            
            $qk_modules[$module_file] = $module_data;
        }
        
        //设置缓存
        $cache_qk_modules[$module_folder] = $wp_plugins;
        //wp_cache_set( 'qk_modules', $cache_qk_modules, 'qk_modules' );
        
        return $qk_modules;
    }
    
    //检索有效启用模块文件数组。
    //来自\wp-includes\load.php wp_get_active_and_valid_plugins()
    public static function qk_get_active_and_valid_modules() {
        
        $modules = array();

        //获取启用的模块
        $active_modules = (array) get_option('qk_active_modules', array());//self::get_modules();//

        if (empty($active_modules)) {
            return $modules;
        }
        
        foreach ($active_modules as $module) {
            if (substr( $module, -4 ) === '.php' && strpos($module,'functions.php') && file_exists( QK_MODULES_URI. $module )) {
                $modules[] = QK_MODULES_URI . $module;
            }
        }
        
        return $modules;
    }
    
    //获取模块数据 参数模块文件
    public static function get_module_data($module_file){
        
        if(!file_exists($module_file)) {
            return array();
        }
        
        $default_headers = array(
            'ModuleName'  => 'Module Name',
            'ModuleURI'   => 'Module URI',
            'ModuleType'  => 'Module Type',
            'AuthorName'  => 'Author Name',
            'AuthorURI'   => 'Author URI',
            'Version'     => 'Version',
            'Description' => 'Description',
            'Logo'        => 'Logo',//get_template_directory_uri().'/images/preference.png'
        );
        
        //位于wp-includes\functions.php 获取文件头部信息
        $module_data = get_file_data($module_file,$default_headers);
        
        return array_filter($module_data);
    }
}