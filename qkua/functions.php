<?php

/**
* 定义常量
*/

define('QK_DS',DIRECTORY_SEPARATOR);  // 斜杠 /
define('QK_HOME_URI',home_url()); //首页 http://www.qkua.com
define('QK_THEME_DIR', get_template_directory() ); ///www/wwwroot/www.qkua.com/wp-content/themes/qkua 主题文件目录
define('QK_VERSION', '1.2.7' ); //主题版本号
define('QK_THEME_URI', get_template_directory_uri() );
define('QK_MODULES_URI',WP_CONTENT_DIR . '/module/'); //模块安装上传路径 http://www.qkua.com

// define( 'CS_ACTIVE_FRAMEWORK', true ); // default true
// define( 'CS_ACTIVE_METABOX', false ); // default true
// define( 'CS_ACTIVE_TAXONOMY', false ); // default true
// define( 'CS_ACTIVE_SHORTCODE', false ); // default true
// define( 'CS_ACTIVE_CUSTOMIZE', false ); // default true

//使用Font Awesome 4
//add_filter('csf_fa4', '__return_true');

//初始化自动加载类
require 'loader.php';

//模块上传
add_action('wp_ajax_module_file_upload',function (){
   
    if(!current_user_can('manage_options')){
        print json_encode(array('status'=>401,'msg'=>'权限不足'));
        exit;
    }
    
    $res = Qk\Modules\Common\FileUpload::module_file_upload($_POST);
    if(!isset($res['error'])){
        print json_encode(array('status'=>200,'msg'=>$res));
        exit;
    }else{
        print json_encode(array('status'=>401,'msg'=>$res['error']));
        exit;
    }
    
});

//模块启用设置
add_action('wp_ajax_qk_module_option',function (){
   
    if(!current_user_can('manage_options')){
        print json_encode(array('status'=>401,'msg'=>'权限不足'));
        exit;
    }
    
    $res = Qk\Modules\Settings\Module::module_active_and_valid_settings($_POST);
    if(!isset($res['error'])){
        print json_encode(array('status'=>200,'msg'=>'成功'));
        exit;
    }else{
        print json_encode(array('status'=>401,'msg'=>$res['error']));
        exit;
    }
    
});

/**
 * 
* 主题启用后进行的操作
 */
if ( ! function_exists( 'qk_setup' ) ) :
    
function qk_setup() {

    $arg = array(
        'top-menu' => '顶部页眉菜单（不支持二级菜单）',
        'channel-menu' => '左侧菜单顶部',
        'channel-menu-bottom' => '左侧菜单底部（需要开启顶部菜单才能显示）',
    );

    //注册菜单
    register_nav_menus($arg);
    
    //支持友情链接
    add_theme_support( 'automatic-feed-links' );

    //支持title标签https://www.yudouyudou.com/WordPress/334.html
    add_theme_support( 'title-tag' );

    //支持缩略图
    add_theme_support( 'post-thumbnails' );

    // 启用 WordPress 主题自定义器中的选择性刷新小工具区块功能
    add_theme_support( 'customize-selective-refresh-widgets' );
    
    //开启文章格式
    add_theme_support( 'post-formats', array( 'image', 'status' ,'gallery', 'video') );
    
    //禁止转义某些符号
    add_filter( 'run_wptexturize', '__return_false', 9999);
    
    //开启友情连接
    add_filter('pre_option_link_manager_enabled','__return_true');
    
}

endif;
add_action( 'after_setup_theme', 'qk_setup' );

/**
* 注册侧边栏
 */
function qk_widgets_init() {

    register_sidebar( array(
        'name'          => __( '侧边栏', 'qk' ),
        'id'            => 'default-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。（显示在未自定义侧边栏的页面）', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => __( '普通文章内页', 'qk' ),
        'id'            => 'post-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。（显示在文章内页）', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar( array(
        'name'          => __( '视频内页', 'qk' ),
        'id'            => 'video-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。（显示在视频内页）', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar( array(
        'name'          => __( '底部', 'qk' ),
        'id'            => 'footer-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。', 'qk' ),
        'before_widget' => '<section class="widget %2$s footer-widget-item">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar( array(
        'name'          => __( '页面', 'qk' ),
        'id'            => 'page-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar( array(
        'name'          => __( '社区圈子首页页面', 'qk' ),
        'id'            => 'circle-home-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar( array(
        'name'          => __( '圈子页面', 'qk' ),
        'id'            => 'circle-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar( array(
        'name'          => __( '话题页面', 'qk' ),
        'id'            => 'topic-sidebar',
        'description'   => __( '请选择你的小工具，拖到此处。', 'qk' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    $index_settings = qk_get_option('qk_template_index');
    //print_r($index_settings);
    if(!empty($index_settings)){
        foreach ($index_settings as $k => $v) {
            if(isset($v['key']) && $v['key']) {
                register_sidebar( array(
                    'name'          => sprintf(__( '首页模块（%s）', 'qk' ),isset($v['title']) ? $v['title'] .'：'. $v['key'] : '#'.($k+1)),
                    'id'            => isset($v['key']) ? $v['key'] : $v,
                    'description'   => __( '请选择你的小工具，拖到此处。', 'qk' ),
                    'before_widget' => '<section id="%1$s" class="widget %2$s mg-b box qk-radius">',
                    'after_widget'  => '</section>',
                    'before_title'  => '<h2 class="widget-title">',
                    'after_title'   => '</h2>',
                ));
            }
        }
    }
}
add_action( 'widgets_init', 'qk_widgets_init' );

/**
 * 获取设置项
 *
 * @param string $key 设置项KEY
 *
 * @return void
 * @author 青青草原上
 * @version 1.0.0
 * @since 2023
 */
function qk_get_option($key = ''){
    return Qk\Modules\Settings\Main::get_option($key);
}

//自定义用户主页页面数组
function qk_custom_user_arg(){
    return apply_filters('qk_custom_user_arg',array(
        'post'=>__('文章','qk'),
        'comments'=>__('回复','qk'),
        'following'=>__('关注','qk'),
        'followers'=>__('粉丝','qk'),
        'collections'=>__('收藏','qk'),
        'myinv'=>__('邀请码','qk'),
        'orders'=>__('我的订单','qk'),
        'settings'=>__('我的设置','qk'),
        'index'=>__('基本信息','qk')
    ));
}

//自定义个人中心页面links数组
function qk_custom_account_links_arg(){
    return apply_filters('qk_custom_account_links_arg',array(
        'secure'=>array('name' => '账号安全','icon' => 'ri-settings-6-line'),
        //'privacy'=>array('name' => '隐私设置','icon' => 'ri-settings-line'),
        'settings'=>array('name' => '资料设置','icon' => 'ri-user-settings-line'),
    ));
}

//自定义个人中心页面quick数组
function qk_custom_account_quick_arg(){
    return apply_filters('qk_custom_account_quick_arg',array(
        //'index'=>array('name' => '个人信息','icon' => 'ri-user-line'),
        'growth'=>array('name' => '我的等级','icon' => 'ri-pulse-line'),
        'post'=>array('name' => '投稿管理','icon' => 'ri-draft-line'),
        'order'=>array('name' => '我的订单','icon' => 'ri-file-list-3-line'),
        qk_get_custom_page_url('vip') => array('name' => '会员中心','icon' => 'ri-vip-crown-2-line'),
        'task'=>array('name' => '任务中心','icon' => 'ri-task-line'),
        //'verify'=>array('name' => '认证中心','icon' => 'ri-shield-user-line'),
    ));
}

//获取全部社交登录全部类型
function get_oauth_types() {
    return apply_filters('qk_get_oauth_types',array(
        'qq' => array(
            'name' => 'QQ',
            'icon' => 'ri-qq-fill',
            'type' => 'qq'
        ),
        'wx' => array(
            'name' => '微信',
            'icon' => 'ri-wechat-fill',
            'type' => 'weixin'
        ),
        'sina' => array(
            'name' => '微博',
            'icon' => 'ri-weibo-fill',
            'type' => 'weibo'
        ),
        'alipay' => array(
            'name' => '支付宝',
            'icon' => 'ri-alipay-fill',
            'type' => 'alipay'
        ),
        'baidu' => array(
            'name' => '百度',
            'icon' => 'ri-baidu-fill',
            'type' => 'baidu'
        ),
        'github' => array(
            'name' => 'Github',
            'icon' => 'ri-github-fill',
            'type' => 'github'
        ),
        // 'gitee' => array(
        //     'name' => '码云',
        //     'icon' => 'ri-gitee-fill',
        //     'type' => 'gitee'
        // ),
        'dingtalk' => array(
            'name' => '钉钉',
            'icon' => 'ri-dingding-fill',
            'type' => 'dingtalk'
        ),
        // 'huawei' => array(
        //     'name' => '华为',
        //     'icon' => 'ri-huawei-fill',
        //     'type' => 'huawei'
        // ),
        'google' => array(
            'name' => '谷歌',
            'icon' => 'ri-google-fill',
            'type' => 'google'
        ),
        'microsoft' => array(
            'name' => '微软',
            'icon' => 'ri-windows-fill',
            'type' => 'microsoft'
        ),
        'facebook' => array(
            'name' => 'facebook',
            'icon' => 'ri-facebook-circle-fill',
            'type' => 'facebook'
        ),
        'twitter' => array(
            'name' => '推特',
            'icon' => 'ri-twitter-fill',
            'type' => 'twitter'
        ),
    ));
}

/**
 * 根据类型获取类型名称
 *
 * @param string $type 要获取名称的类型
 * @return string|null 返回类型的名称，如果类型无效则返回 null
 */
function qk_get_type_name($type){

    if(!$type) return;

    $arg = apply_filters('qk_get_type_name', array(
        'post'=>'文章', 
        'page'=>'页面',
        'shop'=>'商品',
        'user'=>'用户',
        'post_tag'=>'标签',
        'category'=>'分类',
        'video'=>'视频',
        'topic'=>'话题',
        'circle'=>'瞬间',
        'circle_cat'=>'圈子社区',
    ));

    if(isset($arg[$type])) return $arg[$type];

    return;
}

/**
 * 获取 post_status 的描述
 *
 * @param string $status post_status 的值
 * @return string post_status 的描述
 */
function qk_get_post_status_name($status) {
    $post_status = array(
        'publish' => '已发布',
        'draft' => '草稿',
        'pending' => '待审核',
        'private' => '私密',
        'trash' => '回收站',
        'auto-draft' => '自动草稿',
        'inherit' => '继承',
        'future' => '未来发布'
    );

    if (array_key_exists($status, $post_status)) {
        return $post_status[$status];
    }
    
    return;
}

/**
 * 文章类型
 */
function qk_get_post_types(){

    $types = apply_filters('qk_get_post_types', array(
        'post'=>'文章', 
        //'page'=>'页面',
        'shop'=>'商品',
        'video'=>'课程',
        'circle'=>'帖子',
    ));
    
    return $types;
}

/**
 * 获取搜索类型
 */
function qk_get_search_type(){
    
    $arg = apply_filters('qk_get_search_type', array(
        //'all' => '全部',
        'post'=>'文章',
        'video'=>'视频',
        'circle'=>'帖子',
        // 'circle_cat'=>'圈子',
        // 'topic'=>'话题',
        // 'user'=>'用户',
        // 'post_tag'=>'标签',
        // 'category'=>'分类',
    ));
    
    
    
    return $arg;
}


/**
 * 初始化主题功能
 */
function qk_theme_init() {
    
    remove_image_size('post-thumbnail'); // 禁用通过 set_post_thumbnail_size() 添加的图片尺寸
    remove_image_size('another-size');   // 禁用任何其他添加的图片尺寸

    //load_theme_textdomain( 'qk', QK_THEME_DIR . '/languages' ); // 加载主题语言包
}

add_action( 'init', 'qk_theme_init' ); 


// 注册激活钩子 embed功能 移除嵌入式内容的重写规则。
register_activation_hook( __FILE__, array('\Qk\Modules\Common\Optimize','disable_embeds_remove_rewrite_rules'));

// 注册停用钩子 embed功能 刷新重写规则。
register_deactivation_hook( __FILE__, array('\Qk\Modules\Common\Optimize','disable_embeds_flush_rewrite_rules'));


/**
 * 自定义函数，用于将时间戳转换为“多久之前”的格式
 *
 * @param int $ptime 时间戳
 * @param bool $return 是否返回结果，默认为 false
 * 
 * @return string 时间差字符串，如“3分钟前”，如果 $return 为 true，则返回字符串，否则直接输出
 */
function qk_time_ago($ptime,$return = false) {
    // 将时间戳转换为时间差字符串
    return \Qk\Modules\Common\Post::time_ago($ptime,$return);
}

/**
 * 根据图片URL获取图片ID
 *
 * @param string $image_url 图片URL
 * 
 * @return int 图片ID
 */
function qk_get_image_id($image_url) { //attachment_url_to_postid( $image_url )
    global $wpdb;
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url )); 
    return $attachment[0]; 
}

/**
 * 获取缩略图
 *
 * @param array $arg 缩略图参数：url->图片地址,type->裁剪方式,width->裁剪宽度,height->裁剪高度,gif->是否显示动图
 * @return string 裁剪后的图片地址
 */
function qk_get_thumb($arg){
    return \Qk\Modules\Common\FileUpload::thumb($arg);
}

/**
 * 获取img标签html
 *
 * @param array $arg 参数：class->类名,alt->属性,src->图片地址
 * @param boolean $lazy 是否开启图片懒加载
 * 
 * @return string img标签html
 */
function qk_get_img($arg, $lazy = true){
    //$arg = apply_filters('qk_get_thumb_action', $arg);
    
    // 获取图片class
    $class = isset($arg['class']) && $arg['class'] ? ' class="'.implode(" ",$arg['class']):'';
    
    // 获取图片alt
    $alt = isset($arg['alt']) && $arg['alt'] ? ' alt="'.$arg['alt'].'"':'';
    
    //图片自定义属性
    $attribute = isset($arg['attribute']) && $arg['attribute'] ? ' '.$arg['attribute']:'';
    
    // 如果图片地址存在
    if(isset($arg['src']) && $arg['src']){
        
        // 获取图片懒加载属性
        $lazyload = qk_lazyload($arg['src'],$lazy);
        
        // 如果class属性不为空
        if(!empty($class)) {
            $class .= $lazyload;
        }else{
            $class .= ' class="'.$lazyload;
        }
        
        // 获取完整的图片标签
        $arg = '<img'.$class.$alt.$attribute.'>';
    }
    
    // 如果是数组，则返回空字符串
    if(is_array($arg)) return '';
    
    // 返回图片标签
    return $arg;
}

/**
 * 获取用户头像标签
 *
 * @param array $arg 参数：src->头像地址,alt->属性,pendant->头像挂件地址
 * 
 * @return string 用户头像标签
 */
function qk_get_avatar($arg){
    // 头像挂件
    $pendant = isset($arg['pendant']) && $arg['pendant'] ? '<img src="'.$arg['pendant'].'" class="avatar-pendant" alt="用户头像框挂件">':'';
    
    // 徽章
    $badge = isset($arg['badge']) && $arg['badge'] ? '<img src="'.$arg['badge'].'" class="avatar-badge" alt="用户头像徽章">':'';
    
    // 获取图片alt
    $alt = isset($arg['alt']) && $arg['alt'] ? ' alt="'.$arg['alt'].'"':'';
    
    // 如果头像地址存在
    if(isset($arg['src']) && $arg['src']){
        // 获取用户头像标签
        $arg = '
        <div class="user-avatar">
            <img src="'.$arg['src'].'" class="avatar-face w-h" '.$alt.'>
            '.$pendant.$badge.'
        </div>';
    } else {
        // 如果没有头像地址，则返回空字符串
        return '';
    }
    
    // 如果是数组，则返回空字符串
    if(is_array($arg)) return '';
    
    // 返回用户头像标签
    return $arg;
}

/**
 * 获取图片懒加载属性
 *
 * @param string $src 图片地址
 * @param boolean $lazy 是否开启图片懒加载
 * 
 * @return string 图片懒加载属性
 */
function qk_lazyload($src, $lazy = true){
    // 获取是否开启图片懒加载选项
    $open = qk_get_option('qk_image_lazyload');
    
    // 如果开启图片懒加载选项并且需要懒加载
    if($open && $lazy){
        // 获取默认的懒加载图片
        $default_img = qk_get_option('lazyload_default_img');
        
        // 返回图片懒加载属性
        return ' lazyload" data-src="'.$src.'" src="'.$default_img.'"';
    }

    // 如果不需要懒加载，则返回原始的图片属性
    return '" src="'.$src.'"';
}

/**
 * 获取页面宽度
 *
 * @param boolean $show_widget 是否含小工具，true为含小工具，false为不含小工具
 * 
 * @return int 页面宽度
 */
function qk_get_page_width($show_widget,$widget_width = 0,$page=''){
    
    if($page == 'circle'){
        
    }else{
        // 获取页面宽度
        $page_width = (int)qk_get_option('wrapper_width');
        $sidebar_width = qk_get_option('sidebar_width');
    }

    if($show_widget){
        // 获取小工具宽度
        $width = $widget_width ? $widget_width : $sidebar_width;
        return (int)$page_width - (int)$width;
        
    } else {
        // 如果不含小工具，则返回页面宽度
        return $page_width;
    }

}

/**
 * 返回文章正文字符串中的第一张图片
 *
 * @param string $content 文章正文字符串
 * @param int|string $i 选择返回第几张图片，可选值为数字或字符串'all'，默认为0，即返回第一张图片
 *
 * @return string|false 返回图片的URL地址，如果未找到图片则返回false
 */
function qk_get_first_img($content,$i = 0) {
    
    // 使用正则表达式匹配文章正文字符串中的所有图片标签
    preg_match_all('~<img[^>]*src\s?=\s?([\'"])((?:(?!\1).)*)[^>]*>~i', $content, $match,PREG_PATTERN_ORDER);

    // 如果$i是数字，则返回对应索引的图片URL地址，否则返回第一张图片的URL地址
    if(is_numeric($i)){
        return isset($match[2][$i]) ? esc_url($match[2][$i]) : false;
    } elseif($i == 'all') {
        return $match[2]; // 返回所有图片的URL地址数组
    } else {
        return isset($match[2][0]) ? esc_url($match[2][0]) : false; // 返回第一张图片的URL地址
    }
}

/**
 * 获取默认图片的URL地址
 *
 * @return string 默认图片的URL地址
 */
function qk_get_default_img(){
    // 获取主题选项中设置的默认图片地址
    $default_imgs = qk_get_option('qk_default_imgs');

    // 如果为空，则返回默认的图片地址
    if(empty($default_imgs)){
        return QK_THEME_URI.'/Assets/fontend/images/default-img.jpg';
    }

    // 如果设置了默认图片，则将其转化为数组，并随机获取其中的一个元素，即为默认图片的附件ID
    $arr = explode(',', $default_imgs);

    // 获取附件URL
    return wp_get_attachment_url($arr[array_rand($arr, 1)]);
}

/**
 * 修改文章摘要的显示内容
 *
 * @param string $text 原始的文章摘要内容
 *
 * @return string 修改后的文章摘要内容
 */
function qk_change_excerpt( $text){    
    // 判断传入的参数$text是否为字符串类型，如果不是则直接返回
    if(is_string($text)){
        // 在文章摘要中查找第一个左方括号[的位置，如果没有找到则返回原始摘要内容
        $pos = strpos( $text, '[');
        if ($pos === false)
        {
            return $text;
        }

        // 截取左方括号之前的部分作为新的摘要内容，并使用rtrim函数去除末尾的空格
        return rtrim (substr($text, 0, $pos) );
    }
    return $text;
}
add_filter('get_the_excerpt', 'qk_change_excerpt');

/**
 * 获取描述
 *
 * @param int $post_id 文章ID
 * @param int $size 截取长度
 * @param string $content 需要截取的内容
 *
 * @return string 截取以后的字符串
 */
function qk_get_desc($post_id,$size,$content = ''){

    // 如果$content不为空，则使用strip_shortcodes函数去除文章中的所有短代码
    if($content){
        $content = strip_shortcodes($content);
    }else{
        // 如果$content为空，则使用get_post_field函数获取文章的摘要内容，如果摘要内容为空，则使用apply_filters函数获取文章正文的摘要内容
        $content = get_post_field('post_excerpt',$post_id);
        $content = $content ? $content : apply_filters( 'get_the_excerpt',strip_shortcodes(get_post_field('post_content',$post_id)));
    }

    // 使用wp_trim_words函数截取摘要内容，保留指定长度的字符数
    $content = wp_trim_words($content,$size);

    // 使用wp_strip_all_tags函数去除摘要内容中的所有HTML标签
    return str_replace(array('{{','}}'),'',wp_strip_all_tags($content));
}

/**
 * 获取空数据提示 HTML 结构
 *
 * @param string $text 提示信息文本
 * @param string $image 图片文件名
 * @return string 返回 HTML 结构字符串
 */
function qk_get_empty($text,$image){
    return '
    <div class="empty qk-radius box">
        <img src="'.QK_THEME_URI.'/Assets/fontend/images/'.$image.'" class="empty-img"> 
        <p class="empty-text">' . $text . '</p>
    </div>';
}

/**
 * 将数字格式化为易于阅读的格式，例如将1000格式化为1k，1000000格式化为1m
 *
 * @param int $num 待格式化的数字
 *
 * @return string 格式化后的字符串
 *
 * @version 1.0.0
 * @since 2023
 */
function qk_number_format($num) {
    // 如果$num为空，则将其转化为0
    $num = $num === '' ? 0 : $num;

    // 如果$num大于1000，则进行格式化操作
    if($num>1000) {
        $x = round($num);
        $x_number_format = number_format($x);
        $x_array = explode(',', $x_number_format);
        $x_parts = array('k', 'm', 'b', 't');
        $x_count_parts = count($x_array) - 1;
        $x_display = $x;
        $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
        $x_display .= $x_parts[$x_count_parts - 1];

        return $x_display;
    }

    // 如果$num小于等于1000，则直接返回$num
    return $num;
}

/**
 * 将十六进制颜色值转化为RGB颜色值，并添加透明度
 *
 * @param string $hex 十六进制颜色值
 *
 * @return string RGBA格式的颜色值
 *
 * @version 1.0.0
 * @since 2023
 */
function qk_hex2rgb($hex) {
    // 去除$hex中的#字符
    $hex = str_replace("#", "", $hex);

    // 如果$hex的长度为3，则每个字符重复一次，例如#abc转化为rgb(170, 187, 204)
    if(strlen($hex) == 3) {
       $r = hexdec(substr($hex,0,1).substr($hex,0,1));
       $g = hexdec(substr($hex,1,1).substr($hex,1,1));
       $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
       // 如果$hex的长度为6，则将其拆分为红、绿、蓝三个部分，并将其转化为10进制数值
       $r = hexdec(substr($hex,0,2));
       $g = hexdec(substr($hex,2,2));
       $b = hexdec(substr($hex,4,2));
    }

    // 将RGB数值和透明度0.1拼接为rgba格式的字符串，并返回
    return 'rgba('.$r.', '.$g.', '.$b.', var(--opacity,0.1))';
}

//媒体库里的资源谁能发文章都能看到，这个不太好。
//wordpress 不同的人看到不同的媒体库
// add_filter( 'ajax_query_attachments_args', 'qk_show_current_user_attachments' );
// function qk_show_current_user_attachments( $query ) {
//     $user_id = get_current_user_id();
//     if ( $user_id ) {
//         $query['author'] = $user_id;
//     }
//     return $query;
// }

//删除钩子
function qk_remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
    global $wp_filter;

    // Take only filters on right hook name and priority
    if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
        return false;
    }
    // Loop on filters registered
    foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
        // Test if filter is an array ! (always for class/method)
        if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
            // Test if object is a class and method is equal to param !
            if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && $filter_array['function'][1] == $method_name ) {
                // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                } else {
                    unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                }
            }
        }
    }
    return false;
}

/**
 * 设置cookie
 *
 * @param string $key cookie的键名
 * @param mixed $val cookie的值
 * @param int $time cookie的有效时间，默认为1天
 * 
 * @return bool 是否成功设置cookie
 */
function qk_setcookie($key,$val,$time = 86400) {
    $secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
    return setcookie( $key, maybe_serialize($val), time() + $time, COOKIEPATH, COOKIE_DOMAIN ,$secure);
}

/**
 * 获取cookie
 *
 * @param string $key cookie的键名
 * 
 * @return mixed cookie的值，如果不存在则返回空字符串
 */
function qk_getcookie($key) {
    $resout = isset( $_COOKIE[$key] ) ? $_COOKIE[$key] : '';
    return maybe_unserialize(wp_unslash($resout));
}

/**
 * 删除cookie
 *
 * @param string $key cookie的键名
 * 
 * @return bool 是否成功删除cookie
 */
function qk_deletecookie($key) {
    return setcookie( $key, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
}

/**
 * JWT认证token生成前的过滤器函数
 *
 * @param array $data token中的数据
 * @param WP_User|WP_Error $user 当前用户对象或错误对象
 * 
 * @return array $data 经过过滤后的token数据
 */
function qk_jwt_auth_token($data, $user){
    if (is_array($user)){
        wp_die(__('密码错误','qk'));
    }else{
        return apply_filters('qk_jwt_auth_token', $data, $user);
    }
}

add_filter( 'jwt_auth_token_before_dispatch', 'qk_jwt_auth_token', 10, 2);

/**
 * 过滤器函数，用于设置cookie的过期时间
 *
 * @param int $expiration cookie的过期时间
 * @param int $user_id 用户ID
 * @param bool $remember 是否记住登录状态
 * 
 * @return int $expiration 经过过滤后的cookie过期时间
 * 
 */
// function qk_cookie_expiration($expiration, $user_id = 0, $remember = true) {
//     $allow_cookie = qk_get_option('normal_login','allow_cookie');
//     if((string)$allow_cookie === '1'){
//         $login_keep = (int)qk_get_option('normal_login','login_keep');
//         if ($login_keep) {
//             return ($login_keep * DAY_IN_SECONDS) - (12 * HOUR_IN_SECONDS);
//         } else {
//             return $expiration;
//         }

//     }

//     return $expiration;
// }
// add_filter('auth_cookie_expiration', 'qk_cookie_expiration', 9999, 3);

/**
 * 获取当前用户的IP地址
 * 
 */
function qk_get_user_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * 获取自定义页面的URL
 *
 * @param string $type 页面类型
 * @return string 页面的URL
 */
function qk_get_custom_page_url($type){
    $pages = qk_custom_page_arg();

    if(isset($pages[$type])){
        $page = $pages[$type];
        unset($pages);
        if(get_option('permalink_structure')){
            return QK_HOME_URI.QK_DS.$page['key'];
        }else{
            return QK_HOME_URI.'?qk_page='.$page['key'];
        }
    }
}

/**
 * 获取个人中心页面的URL
 *
 * @param string $type 页面类型
 * 
 * @return string 返回用户账户页面的URL
 */
function qk_get_account_url($type){

    if(strpos($type,'http') !== false) return $type;

    // 获取用户自定义的账户页面的URL
    $slug = qk_get_option('account_rewrite_slug');
    $slug = $slug ? trim($slug) : 'account';

    // 如果启用了固定链接
    if (get_option('permalink_structure')) {
        
        // 如果有指定页面类型，则返回对应类型的URL
        if(!empty($type) && $type !== 'index'){
            return home_url($slug . '/' . $type);
        }
        
        // 否则返回默认的账户页面的URL
        return home_url($slug);
    }

    // 如果没有启用固定链接，则返回带有查询参数的URL
    return add_query_arg('qk_account_page', ($type ? $type : 'index'), home_url());
}

/**
 * 防止在一定时间内重复操作
 *
 * @param int $key 用户ID，默认为当前用户ID
 * @return bool 返回检查结果，若已检查过则返回false，否则返回true
 */
function qk_check_repo($key = 0){

    if(!$key){
        $key = get_current_user_id();
    }

    // 从缓存中获取检查结果
    $res = wp_cache_get('qk_rp_'.$key);

    // 如果已经检查过，则返回false
    if($res) return false;

    // 将检查结果存入缓存中，并设置过期时间为2秒
    wp_cache_set('qk_rp_'.$key,1,'',2);

    // 返回true表示未检查过
    return true;
}

/**
 * 生成ajax分页加载的HTML代码
 *
 * @param array $page_data 分页数据，pages包括总页数和paged当前页面
 * @param string $navtype 分页类型
 * @param string $type 加载类型，可选值为'page'或'auto'
 * @param string $return 子组件向父组件传值接收
 * 
 * @return string 返回生成的HTML代码
 */
function qk_ajax_pagenav( $page_data = array( 'pages'=>0, 'paged'=>0 ), $navtype, $type ,$return = ''){
    
    //$url = get_permalink();
    
    return '<div class="qk-pagenav '.$navtype.'-nav">
        <page-nav ref="'.$navtype.'PageNav" paged="'.$page_data['paged'].'" pages="'.$page_data['pages'].'"  navtype="'.$navtype.'" type="'.$type.'" :selector="selector" :api="api" :param="param"'.($return ? ' @change='.$return : '').'></page-nav>
    </div>';
    
}

function qk_settings_error($type='updated',$message=''){
    $type = $type=='updated' ? 'updated' : 'error';
    if(empty($message)) $message = $type=='updated' ?  '设置已保存。' : '保存失败，请重试。';
    add_settings_error(
        'qk_settings_message',
        esc_attr( 'qk_settings_updated' ),
        $message,
        $type
    );
    settings_errors( 'qk_settings_message' );
}

//字符串表示的时间转换为 Unix 时间戳
if(!function_exists('wp_strtotime')){
    function wp_strtotime($str) {
        // 如果 $str 为空，则返回 0
        if (!$str) return 0;
    
        // 获取时区字符串和 GMT 偏移量
        $tz_string = get_option('timezone_string');
        $tz_offset = get_option('gmt_offset', 0);
    
        // 如果时区字符串不为空，则使用时区字符串
        if (!empty($tz_string)) {
            $timezone = $tz_string;
    
        // 如果 GMT 偏移量为 0，则使用 UTC 时区
        } elseif ($tz_offset == 0) {
            $timezone = 'UTC';
    
        // 否则使用 GMT 偏移量作为时区
        } else {
            $timezone = $tz_offset;
    
            // 如果 GMT 偏移量不是以 "+"、"-" 或 "U" 开头，则在前面添加 "+"
            if (substr($tz_offset, 0, 1) != "-" && substr($tz_offset, 0, 1) != "+" && substr($tz_offset, 0, 1) != "U") {
                $timezone = "+" . $tz_offset;
            }
        }
    
        // 创建 DateTime 对象，并将时区设置为指定的时区
        $datetime = new DateTime($str, new DateTimeZone($timezone));
    
        // 返回时间戳
        return $datetime->format('U');
    }
}

/**
 * 删除 WordPress 默认添加的类名和用户类名
 *
 * @param array $classes 默认的类名数组
 * @return array 修改后的类名数组
 */
function remove_default_wp_classes( $classes ) {
    // 删除 WordPress 默认添加的类名
    $classes = array_diff( $classes, array(
        'alignnone',
        'alignleft',
        'aligncenter',
        'alignright',
        'wp-caption',
        'wp-caption-text',
        'gallery',
        'size-medium',
        'size-large',
        'size-full',
        'sticky',
        'logged-in',
        'blog',
        'post-template-default',
        'single-format-standard',
        'author-admin'
    ) );
    
    // 删除用户类名
    $search_terms = array('tag-', 'postid-','author-','category-','-template-default');

    $classes = array_diff($classes, array_filter($classes, function ($class) use ($search_terms) {
        foreach ($search_terms as $term) {
            if (strpos($class, $term) !== false) {
                return true;
            }
        }
        return false;
    }));
    
    //黑暗主题
    $theme_mode = qk_getcookie('theme_mode');
    $default_theme = qk_get_option('theme_mode');
    
    if($theme_mode){
        $classes[] = $theme_mode;
    }else if($default_theme === 'dark-theme'){
        $classes[] = $default_theme ;
    }

    return $classes;
}

add_filter( 'body_class', 'remove_default_wp_classes', 10, 1 );

/**
 * 在数组的指定位置插入另一个数组
 *
 * @param array $array 原始数组
 * @param int $position 插入位置
 * @param array $insert_array 要插入的数组
 * @return void
 */
function array_insert(&$array, $position, $insert_array) {
    $first_array = array_splice($array, 0, $position);
    $array = array_merge($first_array, $insert_array, $array);
}

//获取字符串长度
function qkGetStrLen(string $str){
    $mbLen = mb_strlen($str);
    $len = strlen($str);
    $subLen = $len - $mbLen;
    if ($subLen > 0) {
        $zhCharsLen = $subLen / 2;
        $len = $zhCharsLen + ($mbLen - $zhCharsLen);
    }
    return (int)$len;
}


function qkp($var,$p = 1){
    if(current_user_can('administrator')){
        echo '<pre>';
        
        if(!$p) {
            var_dump($var);
        }else{
            print_r($var);
        }
        echo '</pre>';
    }
}

// 在init钩子上执行代码
// function execute_code_on_init() {
//     update_term_weights();
// }
// add_action('init', 'execute_code_on_init');

function custom_search_rewrite_rule() {
    add_rewrite_rule('^search/?$', 'index.php?s=', 'top');
}
add_action('init', 'custom_search_rewrite_rule',10);
// function custom_search_title($title) {
       // print_r($title)
//     if (is_search() &&  && !get_query_var('s')) {
//         // 如果是自定义搜索页面，设置自定义标题
//         $title = '自定义搜索页面标题';
//     }
//     return $title;
// }
// add_filter('pre_get_document_title', 'custom_search_title');


function custom_search_title($title) {
    if (is_search() && !get_query_var('s')) {
        // 如果是自定义搜索页面，设置自定义描述
        $title['title'] = '发现';
    }
    return $title;
}
add_filter('document_title_parts', 'custom_search_title');

<?php
add_filter('steam_scraper_can_fetch', function($default){ return is_user_logged_in(); });
