<?php
namespace Qk\Modules\Settings;
//后台模块设置

class Template{

    //设置主KEY
    public static $prefix = 'qk_main_options';

    public function init(){ 
        // add_filter('qk_temp_post_type',function ($temp_type){
        //     return array(
        //             array(
        //             'id'          => 'module_type_1',
        //             'type'        => 'image_select',
        //             'title'       => '选择一个调用内容',
        //             'options'     => array(
        //                 'sliders' => QK_THEME_URI.'/Assets/admin/images/swiper.png', //幻灯轮播图
        //                 'html'    => QK_THEME_URI.'/Assets/admin/images/navbtn.png', //自定义
        //                 'posts'   => QK_THEME_URI.'/Assets/admin/images/post.png', //文章
        //                 'search'  => QK_THEME_URI.'/Assets/admin/images/search.png', //搜索
        //             ),
        //             'class'       => 'module_type',
        //             'default'     => 'sliders',
                    
        //         ),
        //         array(
        //             'id'        => 'slider_list_1',
        //             'type'      => 'textarea',
        //             'title'     => '幻灯内容',
        //             'desc'      => sprintf(__('支持所有文章类型（文章，活动，商品等），每组占一行，排序与此设置相同。图片可以在%s上传或选择。
        //                 %s
        //                 支持的格式如下：
        //                 %s','qk'),
        //                 '<a target="__blank" href="'.admin_url('/upload.php').'">媒体中心</a>','<br>','
        //                 <br>文章ID+幻灯图片地址：<code>123<span class="red">|</span>https://xxx.com/wp-content/uploads/xxx.jpg</code><br>
        //                 文章ID+文章默认的缩略图：<code>3434<span class="red">|</span>0</code><br>
        //                 网址连接+幻灯图片地址+标题（适合外链到其他网站）：<code>https://www.xxx.com/123.html<span class="red">|</span>https://xxx.com/wp-content/uploads/xxx.jpg<span class="red">|</span>标题</code><br>
        //             '),
        //             'dependency' => array( 'module_type', '==', 'sliders' ),
        //         )    
        //     );
        // },1);
        
        $this->template_options_page();
        
        
    }
    
    /**
     * 后台模块设置
     *
     * @return void

     * @version 1.0.0
     * @since 2023
     */
    public function template_options_page(){
        
        \CSF::createSection(self::$prefix, array(
            'id'    => 'qk_template_options',
            'title' => '模块设置',
            'icon'  => 'fa fa-chart-pie',
        ));

        //常规设置
        $this->normal_settings();
        
        //顶部设置
        $this->top_settings();

        //首页设置
        $this->index_settings();
        
        //文章模块
        $this->single_settings();
        
        //底部模块
        $this->footer_settings();
        
        //文字评论模块
        $this->comments_settings();
        
        //分类模块设置
        $this->category_settings();
        
        //视频模块设置
        $this->video_settings();
        
        //发现模块
        //$this->search_settings();
    }
    
    //综合常规设置
    public function normal_settings(){
        \CSF::createSection(self::$prefix, array(
            'parent'      => 'qk_template_options',
            'title'       => '综合',
            'icon'        => 'fab fa-instalod',
            'fields'      => array(
                array(
                    'id'         => 'theme_mode',
                    'type'       => 'radio',
                    'title'      => '默认主题',
                    'desc'       => '主题最高优先级来自用户选择，也就是浏览器缓存，只有当用户未设置主题的时候此选项才有效',
                    'options'    => array(
                        'white-theme' => '日间亮色主题',
                        'dark-theme' => '夜间深色主题',
                    ),
                    'default'    => 'white-theme'
                ),
                array(
                    'id'       => 'wrapper_width',
                    'type'     => 'spinner',
                    'title'    => '全局布局宽度',
                    'subtitle' => '页面布局的最大宽度',
                    'desc'     => __('页面宽度已经经过精心的调整，非特殊需求请勿调整，宽度过大会造成显示不协调', 'qk'),
                    'max'      => 2560,
                    'min'      => 0,
                    'step'     => 50,
                    'unit'     => 'px',
                    'default'  => 1200,
                ),
                // array(
                //     'id'       => 'show_sidebar',
                //     'type'     => 'switcher',
                //     'title'    => '全局显示侧边栏',
                //     'default'  => true,
                //     'class'    => 'compact',
                // ),
                array(
                    'id'       => 'sidebar_width',
                    'type'     => 'spinner',
                    'title'    => ' ',
                    'subtitle' => '全局小工具的宽度',
                    'desc'     => __('这里是全局小工具宽度，可在每个分类和文章下单独设置小工具宽度', 'qk'),
                    'max'      => 1000,
                    'min'      => 0,
                    'step'     => 10,
                    'unit'     => 'px',
                    'default'  => 300,
                    //'dependency' => array('show_sidebar', '!=', '', '', 'visible'),
                ),
                array(
                    'id'       => 'theme_color',
                    'type'     => 'color',
                    'title'    => '网站主色调',
                    'desc'     => __('显示在按钮、链接等需要突出显示的地方。', 'qk'),
                    'default'  => "#fd2760",
                ),
                array(
                    'id'       => 'bg_color',
                    'type'     => 'color',
                    'title'    => '网站背景颜色',
                    'desc'     => __('网站背景颜色', 'qk'),
                    'default'  => "#f8f8f8",
                ),
                array(
                    'id'       => 'radius',
                    'type'     => 'spinner',
                    'title'    => '圆角弧度',
                    'desc'     => __('全站生效的圆角弧度', 'qk'),
                    'max'      => 100,
                    'min'      => 0,
                    'step'     => 1,
                    'unit'     => 'px',
                    'default'  => 6,
                ),
                array(
                    'id'       => 'btn_radius',
                    'type'     => 'spinner',
                    'title'    => '按钮圆角弧度',
                    'desc'     => __('按钮的圆角弧度', 'qk'),
                    'max'      => 100,
                    'min'      => 0,
                    'step'     => 1,
                    'unit'     => 'px',
                    'default'  => 20,
                ),
                array(
                    'id'       => 'qk_gap',
                    'type'     => 'spinner',
                    'title'    => '文章卡片间隔',
                    'max'      => 50,
                    'min'      => 0,
                    'step'     => 1,
                    'unit'     => 'px',
                    'default'  => 16,
                ),
            )
        ));
    }
    
    //顶部模块
    public function top_settings(){
        //页面顶部
        \CSF::createSection(self::$prefix, array(
            'parent'      => 'qk_template_options',
            'title'       => '导航模块',
            'icon'        => 'fa  fa-arrow-circle-up',
            'fields'      => array(
                array(
                    'type'    => 'heading',
                    'content' => '顶部菜单',
                ),
                array(
                    'id'       => 'top_menu_bg_color',
                    'type'     => 'color',
                    'title'    => '顶部菜单背景颜色（有横幅自动透明色）',
                    'default'  => "#fff",
                ),
                array(
                    'id'       => 'top_menu_text_color',
                    'type'     => 'color',
                    'title'    => '顶部菜单文字颜色（有横幅自动白色）',
                    'default'  => "#121212",
                ),
                //顶部菜单的宽度
                array(
                    'id'        => 'top_menu_width',
                    'type'      => 'spinner',
                    'title'     => '顶部菜单的宽度',
                    'unit'      => 'px',
                    //'desc'      => '如果不填，则高度由系统自动调整。',
                    'max'       => 2560,
                    'default'   => qk_get_option('wrapper_width'),//做个记号后期动态设置
                ),
                //顶部菜单的高度
                array(
                    'id'        => 'top_menu_height',
                    'type'      => 'spinner',
                    'title'     => '顶部菜单的高度',
                    'unit'      => 'px',
                    //'desc'      => '如果不填，则高度由系统自动调整。',
                    'max'       => 200,
                    'default'   => 64,//做个记号后期动态设置
                ),
                //顶部菜单浮动
                array(
                    'id'        => 'top_menu_fixed',
                    'type'      => 'switcher',
                    'title'     => '顶部菜单跟随工具条浮动',
                    'default'   => 1,
                ),
                array(
                    'id'        => 'top_menu_search_show',
                    'type'      => 'switcher',
                    'title'     => '顶部菜单搜索显示',
                    'default'   => 1,
                ),
                array(
                    'id'        => 'top_menu_check_in_show',
                    'type'      => 'switcher',
                    'title'     => '顶部菜单签到按钮显示',
                    'default'   => 1,
                ),
                array(
                    'id'        => 'top_menu_theme_switch_show',
                    'type'      => 'switcher',
                    'title'     => '顶部菜单主题切换按钮显示',
                    'default'   => 1,
                ),
                array(
                    'id'        => 'top_menu_publish_show',
                    'type'      => 'switcher',
                    'title'     => '顶部菜单发布按钮显示',
                    'default'   => 1,
                ),
                array(
                    'id'        => 'top_menu_publish_links',
                    'type'      => 'group',
                    'title'     => '点击发布按钮要显示的自定义单链接菜单',
                    'button_title' => '新增连接菜单',
                    'fields'    => array(
                        array(
                            'id'    => 'title',
                            'type'  => 'text',
                            'title' => '链接文本',
                        ),
                        array(
                            'id'    => 'icon',
                            'type'  => 'upload',
                            'title' => '图片图标',
                            'preview' => true,
                            'library' => 'image',
                            'desc'    => '图片尺寸45 x 45 最为合适',
                        ),
                        array(
                          'id'    => 'link',
                          'type'  => 'text',
                          'title' => '链接',
                        ),
                    ),
                    'default'   => array(
                        array(
                            'title'     => '发布文章',
                            'icon'    => '',
                            'link' => qk_get_custom_page_url('write'),
                        ),
                        array(
                            'title'     => '发布动态',
                            'icon'    => '',
                            'link' => qk_get_custom_page_url('moment'),
                        ),
                        array(
                            'title'     => '发布图片',
                            'icon'    => '',
                            'link' => '',
                        ),
                        array(
                            'title'     => '发布视频',
                            'icon'    => '',
                            'link' => '',
                        ),
                    ),
                    'dependency' => array('top_menu_publish_show', '!=', 0, '', 'visible'),
                ),
                array(
                    'id'        => 'top_menu_user_links',
                    'type'      => 'group',
                    'title'     => '点击头像要显示的自定义单链接菜单',
                    'button_title' => '新增连接菜单',
                    'fields'    => array(
                        array(
                            'id'    => 'title',
                            'type'  => 'text',
                            'title' => '链接文本',
                        ),
                        array(
                            'id'    => 'icon',
                            'type'  => 'icon',
                            'title' => '图标',
                        ),
                        array(
                          'id'    => 'link',
                          'type'  => 'text',
                          'title' => '链接',
                        ),
                    ),
                    'default'   => array(
                        array(
                            'title'     => '个人中心',
                            'icon'    => 'ri-user-line',
                            'link' => '/account',
                        ),
                        array(
                            'title'     => '投稿管理',
                            'icon'    => 'ri-draft-line',
                            'link' => '/account/post',
                        ),
                        array(
                            'title'     => '账号设置',
                            'icon'    => 'ri-settings-line',
                            'link' => '/account/settings',
                        ),
                    ),
                ),
                array(
                    'type'    => 'heading',
                    'content' => '侧边栏菜单',
                ),
                array(
                    'id'    => 'header_show_channel',
                    'type'  => 'switcher',
                    'title' => '是否只在网站首页显示侧边栏菜单',
                    'default'    => 1
                ),
                array(
                    'id'    => 'sidebar_menu_more_open',
                    'type'  => 'switcher',
                    'title' => '开启侧边栏底部更多按钮',
                    'default'    => 0
                ),
                array(
                    'id'        => 'sidebar_menu_more',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '按钮名称',
                            'default'    => '更多'
                        ),
                        array(
                            'id'        => 'links',
                            'type'      => 'group',
                            'title'     => '自定义单链接菜单',
                            'button_title' => '新增连接菜单',
                            'fields'    => array(
                                array(
                                    'id'    => 'title',
                                    'type'  => 'text',
                                    'title' => '链接文本',
                                ),
                                array(
                                  'id'    => 'link',
                                  'type'  => 'text',
                                  'title' => '链接',
                                ),
                            ),
                            'default'   => array(
                                array(
                                    'title'     => '关于Qkua主题',
                                    'link' => '',
                                ),
                                array(
                                    'title'     => '隐私、协议',
                                    'link' => '',
                                ),
                            ),
                        ),
                    ),
                    'default'        => array(
                        'opt-text'     => 'Text default value',
                        'opt-color'    => '#ffbc00',
                        'opt-switcher' => true,
                    ),
                ),
//////////////////////////////////////////////////////////////////////////////////////////// //////////////// 横幅
                array(
                    'type'    => 'heading',
                    'content' => '横幅',
                ),
                array(
                    'id'       => 'header_show_banner',
                    'type'     => 'switcher',
                    'title'    => '启用横幅',
                    'default'  => false,
                ),
                array(
                    'id'       => 'header_home_show_banner',
                    'type'     => 'switcher',
                    'title'    => '是否只在网站首页显示横幅',
                    'default'  => 1,
                    'dependency' => array('header_show_banner', '!=', '', '', 'visible'),
                ),
                array(
                    'id'    => 'header_banner_img',
                    'type'  => 'upload',
                    'title' => '自定义 banner 横幅静态图片地址',
                    'preview' => true,
                    'library' => 'image',
                    'dependency' => array('header_show_banner', '!=', '', '', 'visible'),
                ),
            )
        ));
    }
    
    //首页模块
    public function index_settings(){
        
        \CSF::createSection(self::$prefix, array(
            'parent'      => 'qk_template_options',
            'title'       => '首页模块',
            'icon'        => 'fa  fa-home',
            'description' => '',
            'fields'      => array(
                array(
                    'id'     => 'qk_template_index',
                    'type'   => 'group', //组
                    'title'  => '首页模块设置',
                    'accordion_title_auto' => true, //收听标题的第一个输入文本值。
                    'accordion_title_number' => true,
                    'fields' => array(
////////////////////////////////////////////////////////////////////////////////////////////////////////// 模块标题
                        array(
                            'id'      => 'title',
                            'type'    => 'text',
                            'title'   =>  sprintf(__('模块标题%s','qk'),'<span class="red">（必填）</span>'),
                            'desc'    => '给这个模块起个名字，某些模块下会显示这个标题',
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 模块key
                        array(
                            'id'      => 'key',
                            'type'    => 'text',
                            'title'   =>  sprintf(__('模块key%s','qk'),'<span class="red">（必填）</span>'),
                            'desc'    => sprintf(__('给这个模块起一个%s唯一的标识，纯小写字母，不要有空格或特殊字符%s，一般情况下不需要随意改动，这个key将和它对应的小工具挂钩，模块顺序变了以后不影响小工具的显示，如果改动这个值，它对应的小工具需要重新设置','qk'),'<b class="red">','</b>'),
                            'default' => 'qk'.round(100,999)
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 模块描述
                        array(
                            'id'      => 'desc',
                            'type'    => 'text',
                            'title'   => '模块描述',
                            'desc'    => '模块标题下会显示这个描述'
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 导航
                        array(
                            'id'         => 'nav_cat',
                            'title'      => '模块导航菜单分类',
                            'type'       => 'select',
                            'chosen'     => true,
                            'multiple'   => true,
                            'placeholder' => '请选择导航菜单分类',
                            'options'     => 'categories',
                            'query_args'  => array(
                                'taxonomy'  => array('category','video_cat')
                            ),
                            'desc'       => '支持分类、视频分类选择导航分类后需要下下方开启勾选导航才能显示',
                            'dependency' => array( 'module_type', 'not-any', 'sliders,html' ),
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 查看全部按钮
                        array(
                            'id'         => 'module_btn_text',
                            'title'      => '模块查看全部按钮自定义名称',
                            'type'       => 'text',
                            'default'    => '查看全部',
                            'dependency' => array( 'module_type', 'not-any', 'sliders,html' ),
                        ),
                        array(
                            'id'         => 'module_btn_url',
                            'title'      => '模块查看全部按钮自定义跳转链接',
                            'type'       => 'text',
                            'dependency' => array( 'module_type', 'not-any', 'sliders,html' ),
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 模块Meta
                        array(
                            'id'         => 'module_meta',
                            'type'       => 'checkbox',
                            'title'      => '模块显示选择',
                            'inline'     => true,
                            'options'    => array(
                                'title'  => '模块标题',
                                'desc'   => '模块描述',
                                'nav'  => '模块导航（仅文章模块生效）',
                                'change'  => '换一换（仅文章模块生效）',
                                'more'  => '查看全部',
                                'load'  => '加载更多（仅文章模块生效）',
                            ),
                            'dependency' => array( 'module_type', 'not-any', 'sliders,html' ),
                        ),
                        array(
                            'id'      => 'widget_show',
                            'type'    => 'switcher',
                            'title'   => '开启此模块小工具',
                            'desc'       => sprintf(__('如果设置开启，保存之后请去%s中对此模块小工具进行设置','qk'),'<a target="__blank" href="'.admin_url('/widgets.php').'">小工具设置</a>'),
                            'default' => 0
                        ),
                        array(
                            'id'    => 'widget_width',
                            'type'  => 'spinner',
                            'title' => '模块小工具宽度',
                            'min'     => 0,
                            'unit'    => 'px',
                            'desc'       => '自定义小工具宽度，为 0 则使用默认全局小工具宽度',
                            'default' => 0,
                            'dependency' => array( 'widget_show', '==', '1' ),
                        ),
                        array(
                            'id'      => 'widget_fixed',
                            'type'    => 'switcher',
                            'title'   => '模块小工具浮动',
                            'desc'       => '开启后小工具跟随浏览器滚动，此设置才会生效并只在PC端生效',
                            'default' => 0,
                            'dependency' => array( 'widget_show', '==', '1' ),
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 可见性
                        array(
                            'id'      => 'module_mobile_show',
                            'type'    => 'select',
                            'title'   => '模块可见性',
                            'options' => array(
                                0     => '桌面和移动端都显示', 
                                1     => '仅桌面可见',
                                2     => '仅移动端可见',
                                3     => '不显示（仅用作短代码调用）',
                            ),
                        ),
                        array(
                            'id'      => 'login_show',
                            'type'    => 'switcher',
                            'title'   => '是否登录后显示此模块',
                            'default' => 0
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 选择一个调用内容
                        array(
                            'id'          => 'module_type',
                            'type'        => 'button_set',
                            'title'       => '选择一个调用内容',
                            'options'     => array(
                                'sliders' => '幻灯', //幻灯轮播图
                                'html'    => '自定义html', //自定义
                                'posts'   => '文章', //文章
                                //'videos'   => '视频', //视频
                                //'search'  => '搜索', //搜索
                                'users'   => '用户', //用户
                            ),
                            //'class'       => 'module_type',
                            'default'     => 'sliders',
                            
                        ),
                        
                        array(
                            'id'         => 'slider_type',
                            'type'       => 'button_set',
                            'title'      => '选择调用内容方式',
                            'options'    => array(
                                'slider_list'  => '自定义调用',
                                'slider_posts' => '调用文章',
                            ),
                            'default'    => 'slider_list',
                            'class'      => 'slider_type',
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 幻灯形式
                        array(
                            'id'        => 'slider_list',
                            'type'      => 'textarea',
                            'title'     => '幻灯内容',
                            'desc'      => sprintf(__('支持所有文章类型（文章，活动，商品等），每组占一行，排序与此设置相同。图片可以在%s上传或选择。
                                %s
                                支持的格式如下：
                                %s','qk'),
                                '<a target="__blank" href="'.admin_url('/upload.php').'">媒体中心</a>','<br>','
                                <br>文章ID+幻灯图片地址：<code>123<span class="red">|</span>https://xxx.com/wp-content/uploads/xxx.jpg</code><br>
                                文章ID+文章默认的缩略图：<code>3434<span class="red">|</span>0</code><br>
                                网址连接+幻灯图片地址+标题（适合外链到其他网站）：<code>https://www.xxx.com/123.html<span class="red">|</span>https://xxx.com/wp-content/uploads/xxx.jpg<span class="red">|</span>标题</code><br>
                            '),
                            'dependency' => array(
                                array( 'module_type', '==', 'sliders' ),
                                array( 'slider_type',   '==', 'slider_list' ),
                            ),
                        ),
                        //排序方式
                        array(
                            'id'         => 'slider_post_order',
                            'type'       => 'select',
                            'title'      => '排序方式',
                            'options'    => array(
                                'new'      => '最新文章',
                                'modified' => '修改时间',
                                'random'   => '随机文章',
                                'sticky'   => '置顶文章',
                                'views'    => '浏览最多文章',
                                'comments' => '评论最多文章'
                            ),
                            'default'     => 'new',
                            'dependency' => array(
                                array( 'module_type', '==', 'sliders' ),
                                array( 'slider_type',   '==', 'slider_posts' )
                            ),
                        ),
                        //文章分类
                        array(
                            'id'         => 'slider_post_cat',
                            'title'      => '调用分类',
                            'type'       => 'select',
                            'placeholder' => '选择分类',
                            'chosen'     => true,
                            'multiple'   => true,
                            'options'    => 'category',
                            'desc'       => '选择此幻灯模块要显示的文章分类',
                            'placeholder' => '请选择幻灯调用分类',
                            'dependency' => array(
                                array( 'module_type', '==', 'sliders' ),
                                array( 'slider_type', '==', 'slider_posts' )
                            ),
                        ),
                        //幻灯宽度
                        array(
                            'id'        => 'slider_post_count',
                            'type'      => 'spinner',
                            'title'     => '文章数量',
                            'unit'      => '个',
                            'default'   => 10, //做个记号后期动态设置
                            'max'       => 100, //做个记号后期动态设置
                            'dependency' => array(
                                array( 'module_type', '==', 'sliders' ),
                                array( 'slider_type',   '==', 'slider_posts' )
                            ),
                        ),
                        //幻灯宽度
                        array(
                            'id'        => 'slider_width',
                            'type'      => 'spinner',
                            'title'     => '幻灯宽度',
                            'unit'      => 'px',
                            'default'   => '', //做个记号后期动态设置
                            'desc'      => '如果不设置，则与页面同宽',
                            'max'       => 1000, //做个记号后期动态设置
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯的高度
                        array(
                            'id'        => 'slider_height',
                            'type'      => 'spinner',
                            'title'     => '幻灯的高度',
                            'unit'      => 'px',
                            'default'   => '565',//做个记号后期动态设置
                            'desc'      => '如果不填，则高度由系统自动调整。',
                            'max'       => 1000,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯上下间距
                        array(
                            'id'        => 'slider_row_gap',
                            'type'      => 'spinner',
                            'title'     => '幻灯之间的<span class="red">上下</span>间距',
                            'unit'      => 'px',
                            'default'   => 20,
                            'desc'      => '设置幻灯<span class="red">上下行</span>之间的间隙大小，请直接填写数字（只在PC端生效）',
                            'max'       => 100,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯左右间距
                        array(
                            'id'        => 'slider_column_gap',
                            'type'      => 'spinner',
                            'title'     => '幻灯之间的<span class="red">左右</span>间距',
                            'unit'      => 'px',
                            'default'   => 12,
                            'desc'      => '设置幻灯<span class="red">左右列</span>之间的间隙大小，请直接填写数字（只在PC端生效）',
                            'max'       => 100,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯列数
                        array(
                            'id'        => 'slider_grid_column',
                            'type'      => 'spinner',
                            'title'     => '幻灯<span class="green">列数</span>',
                            'unit'      => '列',
                            'default'   => 5,
                            'desc'      => '设置幻灯 <span class="green">上下一共几列</span> 请直接填写数字（只在PC端生效）',
                            'max'       => 20,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯占位开始列数
                        array(
                            'id'        => 'slider_grid_column_start',
                            'type'      => 'spinner',
                            'title'     => '幻灯占位<span class="green">开始列数</span>',
                            'unit'      => '列',
                            'default'   => 1,
                            'desc'      => '设置幻灯从<span class="green">第几列开始</span>占位 左右一共占位列（只在PC端生效）',
                            'max'       => 21,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯占位结束列数
                        array(
                            'id'        => 'slider_grid_column_end',
                            'type'      => 'spinner',
                            'title'     => '幻灯占位<span class="green">结束列数</span>',
                            'unit'      => '列',
                            'default'   => 3,
                            'desc'      => '设置幻灯从<span class="green">第几列结束</span>占位（如果想要在最后一列结束需要 设置的幻灯列数 + 1） 左右一共占位列（只在PC端生效）',
                            'max'       => 21,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯行数
                        array(
                            'id'        => 'slider_grid_row',
                            'type'      => 'spinner',
                            'title'     => '幻灯<span class="red">行数</span>',
                            'unit'      => '行',
                            'default'   => 2,
                            'desc'      => '设置幻灯 <span class="red">左右一共几行</span> 请直接填写数字（只在PC端生效）',
                            'max'       => 20,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯占位开始行数
                        array(
                            'id'         => 'slider_grid_row_start',
                            'type'       => 'spinner',
                            'title'      => '幻灯占位<span class="red">开始行数</span>',
                            'unit'       => '行',
                            'default'    => 1,
                            'desc'       => '设置幻灯从<span class="red">第几行开始</span>占位（只在PC端生效）',
                            'max'        => 21,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //幻灯占位结束行数
                        array(
                            'id'         => 'slider_grid_row_end',
                            'type'       => 'spinner',
                            'title'      => '幻灯占位<span class="red">结束行数</span>',
                            'unit'       => '行',
                            'default'    => 3,
                            'desc'       => '设置幻灯从<span class="red">第几行结束</span>占位（如果想要在最后一列结束需要 设置的幻灯行数 + 1） 上下一共占位行（只在PC端生效）',
                            'max'        => 21,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //是否显示幻灯标题
                        array(
                            'id'         => 'slider_show_title',
                            'type'       => 'switcher',
                            'title'      => '是否显示幻灯标题',
                            'default'    => 1,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //图片主题色阴影
                        array(
                            'id'         => 'slider_show_mask',
                            'type'       => 'switcher',
                            'title'      => '是否显示幻灯标题下的图片主题色阴影',
                            'default'    => 1,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
                        //是否新窗口打开
                        array(
                            'id'         => 'slider_new_window',
                            'type'       => 'switcher',
                            'title'      => '是否新窗口打开',
                            'default'    => 0,
                            'dependency' => array( 'module_type', '==', 'sliders' ),
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 文章形式
                        array(
                            'id'          => '_post_type',
                            'type'        => 'radio',
                            'title'       => '选择调用的文章类型',
                            'options'     => array(
                                'post'   => '文章', //文章
                                'video'   => '视频', //视频
                            ),
                            'default'     => 'post',
                            'inline'      => true,
                            'dependency'  => array( 'module_type', '==', 'posts' ),
                        ),
                        array(
                            'id'          => 'post_type', //文章卡片样式
                            'type'        => 'image_select',
                            'title'       => '文章列表风格样式',
                            'options'     => array(
                                'post-1'  => QK_THEME_URI.'/Assets/admin/images/post-1.png', //网格
                                'post-2'  => QK_THEME_URI.'/Assets/admin/images/post-2.png', //列表
                                'post-3'  => QK_THEME_URI.'/Assets/admin/images/post-3.png', //文章
                                // 'post-4'  => QK_THEME_URI.'/Assets/admin/images/search.png', //搜索
                            ),
                            'class'       => 'module_type',
                            'default'     => 'post-1',
                            'dependency'  => array( 'module_type', '==', 'posts' ),
                        ),
                        //开启瀑布流显示
                        array(
                            'id'      => 'waterfall_show',
                            'type'    => 'switcher',
                            'title'   => '开启瀑布流显示',
                            'desc'    => '注意开启瀑布流，远程图片因为获取不到宽度和高度，所以瀑布流不会正常显示，需要本地网站上传的图片封面才可以',
                            'default' => 0,
                            'dependency' => array( 
                                array( 'module_type', 'any', 'posts,users' ),
                                array( 'post_type', 'any', 'post-1,post-3' )
                            )
                        ),
                        //排序方式
                        array(
                            'id'         => 'post_order',
                            'type'       => 'select',
                            'title'      => '文章排序方式',
                            'options'    => array(
                                'new'      => '最新文章',
                                'modified' => '修改时间',
                                'random'   => '随机文章',
                                'sticky'   => '置顶文章',
                                'views'    => '浏览最多文章',
                                'comments' => '评论最多文章'
                            ),
                            'default'     => 'new',
                            'dependency'   => array( 'module_type', 'any', 'posts,users' ),
                        ),
                        //文章分类
                        array(
                            'id'         => 'post_cat',
                            'title'      => '调用分类',
                            'type'       => 'select',
                            'chosen'     => true,
                            'multiple'   => true,
                            'placeholder' => '请选择文章调用分类',
                            'options'    => 'category',
                            'desc'       => '选择此模块要显示的文章分类',
                            'dependency' => array( 
                                array( 'module_type', 'any', 'posts' ),
                                array( '_post_type', 'any', 'post' )
                            )
                        ),
                        array(
                            'id'         => 'video_cat',
                            'title'      => '调用分类',
                            'type'       => 'select',
                            'chosen'     => true,
                            'multiple'   => true,
                            'placeholder' => '请选择文章调用分类',
                            'options'     => 'categories',
                            'query_args'  => array(
                                'taxonomy'  => array('video_cat')
                            ),
                            'desc'       => '选择此模块要显示的文章分类',
                            'dependency' => array( 
                                array( 'module_type', 'any', 'posts' ),
                                array( '_post_type', 'any', 'video' )
                            )
                        ),
                        array(
                            'id'         => 'post_row_count',
                            'type'       => 'spinner',
                            'title'      => '每列显示文章数量',
                            'unit'       => '个',
                            'max'        => 20,
                            'default'    => 5,//做个记号后期动态设置
                            'dependency' => array( 
                                array( 'module_type', 'any', 'posts,users' ),
                            )
                        ),
                        array(
                            'id'         => 'post_count',
                            'type'       => 'spinner',
                            'title'      => '显示文章总数',
                            'unit'       => '个',
                            'max'        => 100,
                            'default'    => 10,//做个记号后期动态设置
                            'dependency' => array( 
                                array( 'module_type', 'any', 'posts,users' ),
                            )
                        ),
                        array(
                            'id'         => 'mobile_post_count',
                            'type'       => 'spinner',
                            'title'      => '移动端显示文章总数',
                            'unit'       => '个',
                            'max'        => 100,
                            'default'    => 4,//做个记号后期动态设置
                            'dependency' => array( 
                                array( 'module_type', 'any', 'posts' ),
                            )
                        ),
                        //缩略图比例
                        array(
                            'id'         => 'post_thumb_ratio',
                            'type'       => 'text',
                            'title'      => '缩略图比例',
                            'default'    => '1/1.725',//做个记号后期动态设置
                            'desc'       => '缩略图高度自适应的情况下不生效，请填写宽和高的比例，比如4/3，1/0.618。',
                            'dependency' => array( 
                                array( 'module_type', '==', 'posts' ),
                                array( 'waterfall_show','==', '0' )
                            )
                        ),
                        //文章meta选择
                        array(
                            'id'         => 'post_meta',
                            'type'       => 'checkbox',
                            'title'      => '文章meta显示选择',
                            'inline'     => true,
                            'options'    => array(
                                'user'   => '作者',
                                'date'   => '时间',
                                'like'   => '喜欢数量',
                                'comment'=> '评论数量',
                                'views'  => '浏览量',
                                'cats'   => '分类',
                                'desc'   => '描述'
                            ),
                            'dependency'        => array( 'module_type', '==', 'posts' )
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 用户形式
                        array(
                            'id'          => 'user_type', //文章卡片样式
                            'type'        => 'image_select',
                            'title'       => '用户列表风格样式',
                            'options'     => array(
                                'user-1'  => QK_THEME_URI.'/Assets/admin/images/user-1.png', //网格
                                'user-2'  => QK_THEME_URI.'/Assets/admin/images/user-2.png', //网格
                            ),
                            'class'       => 'module_type',
                            'default'     => 'user-1',
                            'dependency'  => array( 'module_type', '==', 'users' ),
                        ),
                        //排序方式
                        array(
                            'id'         => 'user_order',
                            'type'       => 'select',
                            'title'      => '排序方式',
                            'options'    => array(
                                'new'      => '最新注册',
                                'fans'     => '用户粉丝数',
                                'lv'       => '用户等级经验',
                                'vip'      => '用户VIP等级',
                                'money'    => '用户余额',
                                'credit'   => '用户积分',
                                'post'     => '用户文章数',
                                'post1'     => '用户文章总浏览量（暂无）',
                                'post2'     => '用户文章总收藏量（暂无）',
                                'post3'     => '用户文章总喜欢量（暂无）',
                            ),
                            'default'     => 'new',
                            'dependency'   => array( 'module_type', '==', 'users' ),
                        ),
                        array(
                            'id'         => 'user_row_count',
                            'type'       => 'spinner',
                            'title'      => '每列显示用户数量',
                            'unit'       => '个',
                            'max'        => 20,
                            'default'    => 5,//做个记号后期动态设置
                            'dependency' => array( 'module_type', '==', 'users' )
                        ),
                        array(
                            'id'         => 'user_count',
                            'type'       => 'spinner',
                            'title'      => '显示用户总数',
                            'unit'       => '个',
                            'max'        => 100,
                            'default'    => 10,//做个记号后期动态设置
                            'dependency' => array( 'module_type', '==', 'users' )
                        ),
                        // //缩略图比例
                        // array(
                        //     'id'         => 'post_thumb_ratio',
                        //     'type'       => 'text',
                        //     'title'      => '缩略图比例',
                        //     'default'    => '1/1.725',//做个记号后期动态设置
                        //     'desc'       => '缩略图高度自适应的情况下不生效，请填写宽和高的比例，比如4/3，1/0.618。',
                        //     'dependency' => array( 
                        //         array( 'module_type', '==', 'posts' ),
                        //         array( 'waterfall_show',   '==', '0' )
                        //     )
                        // ),
                        //文章meta选择
                        array(
                            'id'         => 'user_meta',
                            'type'       => 'checkbox',
                            'title'      => '用户meta显示选择',
                            'inline'     => true,
                            'options'    => array(
                                'user'   => '作者',
                                'date'   => '时间',
                                'like'   => '喜欢数量',
                                'comment'=> '评论数量',
                                'views'  => '浏览量',
                                'cats'   => '分类',
                            ),
                            'dependency'        => array( 'module_type', '==', 'users' )
                        ),
////////////////////////////////////////////////////////////////////////////////////////////////////////// 自定义HTML
                        array(
                            'id'        => 'html',
                            'title'     => '自定义html',
                            'subtitle'  => '自定义模块支持html和php，如果写php代码，请不要忘记 <code>&lt;?php</code> 和 <code>?&gt;</code> 符号。',
                            'default'   => '',
                            'settings'  => array(
                                'theme' => 'dracula',
                            ),
                            'sanitize'  => false,
                            'type'      => 'code_editor',
                            'dependency'  => array( 'module_type', '==', 'html' )
                        ),
                    )
                )
            ),
        ));
    }
    
    //文章模块
    public function single_settings(){
        
        //文章类型模块钩子
        $temp_type = apply_filters('qk_temp_post_type',array());
        
        $fields = array(
            array(
                'id'          => 'single_post_style', //文件模板样式
                'type'        => 'image_select',
                'title'       => '默认文章风格样式',
                'options'     => array(
                    //'sliders' => QK_THEME_URI.'/Assets/admin/images/swiper.png', //幻灯轮播图
                ),
                'class'       => 'module_type',
                //'default'     => 'sliders',
                'desc'   => '优先显示文章中的此项设置，如果文章中未设置，则此处设置生效',
            ),
            array(
                'id'       => 'single_wrapper_width',
                'type'     => 'spinner',
                'title'    => '文章页面布局宽度',
                'subtitle' => '页面布局的最大宽度',
                'desc'     => __('页面宽度已经经过精心的调整，非特殊需求请勿调整，宽度过大会造成显示不协调', 'qk'),
                'max'      => 2560,
                'min'      => 0,
                'step'     => 50,
                'unit'     => 'px',
                'default'  => 1200,
            ),
            array(
                'id'      => 'single_sidebar_open',
                'type'    => 'switcher',
                'title'   => '开启文章页侧边栏小工具',
                'default' => true,
                'label'   => '优先显示文章中的此项设置，如果文章中未设置，则此处设置生效',
            ),
            array(
                'id'       => 'single_sidebar_width',
                'type'     => 'spinner',
                'title'    => '文章页面小工具的宽度',
                'desc'     => __('这里是全局小工具宽度，可在每个分类和文章下单独设置小工具宽度', 'qk'),
                'max'      => 1000,
                'min'      => 0,
                'step'     => 10,
                'unit'     => 'px',
                'default'  => 300,
                'dependency' => array('single_sidebar_open', '!=', '', '', 'visible'),
            ),
            array(
                'id'       => 'single_video_wrapper_width',
                'type'     => 'spinner',
                'title'    => '视频样式文章页面布局宽度',
                'subtitle' => '页面布局的最大宽度',
                'desc'     => __('页面宽度已经经过精心的调整，非特殊需求请勿调整，宽度过大会造成显示不协调', 'qk'),
                'max'      => 2560,
                'min'      => 0,
                'step'     => 50,
                'unit'     => 'px',
                'default'  => 1200,
            ),
            array(
                'id'          => 'single_sidebar_layout',
                'type'        => 'image_select',
                'title'       => '文章页侧边栏布局',
                'options'     => array(
                    'left'    => QK_THEME_URI.'/Assets/admin/images/sidebar-left.png',
                    'right'   => QK_THEME_URI.'/Assets/admin/images/sidebar-right.png',
                ),
                // 'class'       => 'module_type',
                'default'     => 'right',
                'dependency' => array('single_sidebar_open', '!=', '', '', 'visible'),
            ),
            array(
                'id'      => 'single_breadcrumb_open',
                'title'   => '开启文章页面包屑导航',
                'type'    => 'switcher',
                'desc'    => '首页 / 分类1 / 分类2 / ... / 正文',
                'default' => true,
            ),
            array(
                'id'      => 'single_highlightjs_open',
                'type'    => 'switcher',
                'title'   => '开启文章中代码高亮功能',
                'default' => true,
                'label'   => '全局开关，关闭后前台投稿编辑器插入代码按钮随之关闭',
            ),
            array(
                'id'       => 'highlightjs_theme',
                'type'     => 'select',
                'title'    => ' ',
                'subtitle' => '代码高亮主题样式',
                'default'  => 'default',
                'options'  => array(
                    'default'  => '默认：Default',
                    'mac' => '苹果：Mac',
                    'twilight'  => '暮光：Twilight',
                    'tomorrow_night' => '明暗：TomorrowNight',
                    'sunlight' => '日光：Sunlight',
                    'dusk' => '黄昏：Dusk',
                    'funky' => '时髦：Funky',
                ),
                'dependency' => array('single_highlightjs_open', '==', true),
            ),
            array(
                'id'      => 'single_tags_open',
                'type'    => 'switcher',
                'title'   => '开启文章中标签显示',
                'default' => true,
                'label'   => '优先显示文章中的此项设置，如果文章中未设置，则此处设置生效',
            ),
            array(
                'id'      => 'single_next_open',
                'type'    => 'switcher',
                'title'   => '开启文章中下一篇和下一篇功能',
                'default' => true,
            ),
            array(
                'id'      => 'single_related_open',
                'type'    => 'switcher',
                'title'   => '开启文章相关推荐功能',
                'default' => true,
            ),
            array(
                'id'         => 'single_related_title',
                'title'      => ' ',
                'subtitle'   => '板块标题',
                'type'       => 'text',
                'default'    => '相关推荐',
                'dependency' => array('single_related_open', '!=', '', '', 'visible'),
            ),
            array(
                'id'         => 'single_related_count',
                'type'       => 'spinner',
                'title'      => ' ',
                'subtitle'   => '显示文章数量',
                'default'    => 6,
                'max'        => 12,
                'min'        => 4,
                'step'       => 2,
                'unit'       => '篇',
                'dependency' => array('single_related_open', '!=', '', '', 'visible'),
            ),
            array(
                'type'    => 'heading',
                'content' => '广告位',
            ),
            array(
                'id'        => 'single_top_ads',
                'title'     => '文章顶部广告位代码',
                'subtitle'  => '自定义广告代码一般用a标签包裹',
                'default'   => '',
                'settings'  => array(
                    'theme' => 'dracula',
                ),
                'sanitize'  => false,
                'type'      => 'code_editor',
            ),
            array(
                'id'        => 'single_bottom_ads',
                'title'     => '文章低部广告位代码',
                'subtitle'  => '自定义广告代码一般用a标签包裹',
                'default'   => '',
                'settings'  => array(
                    'theme' => 'dracula',
                ),
                'sanitize'  => false,
                'type'      => 'code_editor',
            ),
            array(
                'type'    => 'heading',
                'content' => '下载模板预设',
            ),
            array(
                'id'        => 'single_post_download_template_group',
                'type'      => 'group',
                'title'     => '添加下载模版预设',
                'button_title'     => '添加下载模版',
                'accordion_title_number'     => true,
                'fields'    => array(
                    array(
                        'id'        => 'name',
                        'type'      => 'text',
                        'title'     => '模板名称',
                        'desc'=> '给这个模板起个名字',
                    ),
                    array(
                        'id'        => 'title',
                        'type'      => 'text',
                        'title'     => '资源名称',
                        'desc'=> '如果不设置，需要显示的地方默认将获取文章标题当作资源名称',
                    ),
                    array(
                        'id'    => 'thumb',
                        'type'  => 'upload',
                        'title' => '资源缩略图',
                        'preview' => true,
                        'library' => 'image',
                    ),
                    array(
                        'id'        => 'rights',
                        'type'      => 'textarea',
                        'title'     => '下载权限',
                        'desc'=> sprintf('
                        如果权限重合则优先使用vip > lv > all。如果每日免费下载次数用完后，则继续使用对应的权限%s 
                        格式为%s，
                        比如%s
                        《权限参数》%s
                        无限制及免费：free%s
                        密码下载: password 如使用密码下载请先进行 <a href="'.admin_url('/admin.php?page=qk_main_options#tab=常规设置/密码验证').'" target="_blank">常规设置/密码验证设置</a>（可以配套使用公众号，用来引流使用）%s
                        评论下载：comment%s
                        登录下载：login%s
                        付费下载：money=10%s
                        积分下载：credit=30%s
                        《特殊权限》%s
                        所有人免费：all|free（或者credit=10这种格式）%s
                        普通用户组免费：lv|free（或者credit=10这种格式）%s
                        VIP用户免费：vip|free（或者credit=10这种格式|如果vip开启了免费下载可以不用设置）',
                        '<br>',
                        '<code>等级|权限</code>',
                        '<br><code>vip1|free</code><br><code>vip2|money=1</code><br><code>vip3|password</code><br><code>lv2|comment</code><br><code>lv3|login</code><br><code>lv4|money=10</code><br><code>lv4|credit=30</code><br><code>not_login|money=30</code>(未登录用户付费价格，未登录用户无法支付积分，如果上面关闭了未登录用户购买功能，此种设置不会生效)<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>',
                        '<br>'),
                    ),
                    array(
                        'id'        => 'download_group',
                        'type'      => 'group',
                        'title'     => '下载资源',
                        'button_title' => '增加下载资源',
                        'accordion_title_number' => true,
                        'fields'    => array(
                            array(
                                'id'    => 'name',
                                'type'  => 'text',
                                'title' => '下载名称',
                                'desc'  => '比如<code>直链下载</code>，<code>百度网盘</code>，<code>微云</code>，<code>微软网盘</code>，<code>阿里云盘</code>等。',
                                'default' => '下载',
                            ),
                            array(
                                'title'       => '下载地址',
                                'id'          => 'url',
                                'placeholder' => '上传文件或输入下载地址',
                                'preview'     => true,
                                'type'        => 'upload',
                            ),
                            array(
                                'id'    => 'tq',
                                'type'  => 'text',
                                'title' => '提取码',
                            ),
                            array(
                                'id'    => 'jy',
                                'type'  => 'text',
                                'title' => '解压密码',
                            ),
                        ),
                    ),
                    array(
                        'id'        => 'attrs',
                        'type'      => 'textarea',
                        'title'     => '资源属性',
                        'desc'=> sprintf('格式为%s，每组占一行。%s比如：%s','<code>属性名|属性值</code>','<br>','<br><code>文件格式|zip</code><br><code>文件大小|100MB</code>'),
                    ),
                    array(
                        'id'    => 'demo',
                        'type'  => 'text',
                        'title' => '演示地址',
                        'desc'=> 'http(s)://...形式的网址',
                    ),
                ),
                
            ),
        );
        
        \CSF::createSection(self::$prefix, array(
            'parent'      => 'qk_template_options',
            'title'       => '文章模块',
            'icon'        => 'fa  fa-file-alt',
            'fields'      => array_merge($fields, $temp_type)
        ));
        
        
    }
    
    //底部模块
    public function footer_settings(){
        \CSF::createSection(self::$prefix, array(
            'parent'    => 'qk_template_options',
            'title'     => '底部模块',
            'icon'      => 'fa  fa-arrow-circle-down',
            'fields'    => array(
                array(
                    'id'          => 'footer_link_cat',
                    'type'        => 'checkbox',
                    'title'       => __('底部显示的友情连接分类'),
                    'options'     => 'categories',
                    'inline'      => true,
                    'query_args'  => array(
                        'taxonomy'    => 'link_category',
                    ),
                    'empty_message'    => sprintf(__('没有连接分类，请前往%s添加'),'<a target="__blank" href="'.admin_url('/edit-tags.php?taxonomy=link_category').'">链接分类</a>'),
                ),
                array(
                    'id'           => 'footer_image',
                    'type'         => 'group',
                    'title'        => '页脚图片',
                    'max'          => 4,
                    'button_title' => '添加图片',
                    'placeholder'  => '显示在底部的图片内容',
                    'default'      => array(
                        array(
                            'image' => QK_THEME_URI.'/Assets/fontend/images/contact/qrcode.png',
                            'text'  => 'QQ交流群',
                        ),
                        array(
                            'image' => QK_THEME_URI.'/Assets/fontend/images/contact/qrcode.png',
                            'text'  => '官方微信客服',
                        ),
                    ),
                    'fields'       => array(
                        array(
                            'id'    => 'text',
                            'title' => '显示文字',
                            'type'  => 'text',
                        ),
                        array(
                            'id'      => 'image',
                            'type' => 'upload',
                            'title'   => '显示图片',
                            'library' => 'image', 
                        ),
                    ),
                ),
                array(
                    'id'      => 'footer_beian',
                    'type'    => 'text',
                    'title'   => '备案号',
                ),
                array(
                    'id'      => 'footer_gongan',
                    'type'    => 'text',
                    'title'   => '公安备案号',
                ),
                array(
                    'id'        => 'footer_mobile_tabbar',
                    'type'      => 'group',
                    'title'     => '移动端底部Tab导航',
                    'button_title' => '新增栏目按钮',
                    'subtitle' => '建议添加5个选项比较适中',
                    'max' => 8,
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '栏目名称',
                        ),
                        array(
                            'id'         => 'type',
                            'title'      => '栏目类型',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'custom'   => '自定义',
                                'message'   => '消息',
                                'public' => '发布'
                            ),
                            'default' => 'custom'
                        ),
                        
                        array(
                            'id'    => 'link',
                            'type'  => 'text',
                            'title' => '自定义跳转连接',
                            'dependency' => array(
                                array('event', '==', ''),
                                //array('type', '==', 'custom'),
                            ),
                        ),
                        array(
                            'id'    => 'event',
                            'type'  => 'text',
                            'title' => '自定义点击事件',
                            'dependency' => array(
                                array('link', '==', ''),
                                array('type', '==', 'custom'),
                            ),
                        ),
                        array(
                            'id'    => 'icon',
                            'type'  => 'icon',
                            'title' => '自定义图标',
                            'dependency' => array('icon_html|icon_html_current', '==|==', ''),
                        ),
                        array(
                            'id'    => 'icon_current',
                            'type'  => 'icon',
                            'title' => '自定义选中时状态图标',
                            'dependency' => array('icon_html|icon_html_current', '==|==', ''),
                        ),
                        array(
                            'id'    => 'icon_html',
                            'type'  => 'textarea',
                            'title' => '自定义html图标',
                            'desc'  => '自定义图标html代码，注意代码规范',
                            'dependency' => array('icon', '==', ''),
                        ),
                        array(
                            'id'    => 'icon_html_current',
                            'type'  => 'textarea',
                            'title' => '自定义选中时状态显示html图标',
                            'desc'  => '如果时当前页面则显示该代码',
                            'dependency' => array('icon', '==', ''),
                        ),
                    ),
                    'default' => array(
                        array(
                            'name' => '首页',
                            'type' => 'custom',
                            'link' => '/',
                            'icon' => 'ri-home-smile-line',
                            'icon_current' => 'ri-home-4-fill',
                        ),
                        array(
                            'name' => '社区',
                            'type' => 'custom',
                            'link' => '/circle',
                            'icon' => 'ri-messenger-line',
                            'icon_current' => 'ri-messenger-fill',
                        ),
                        array(
                            'name' => '发布',
                            'type' => 'public',
                            'icon' => 'ri-add-fill',
                        ),
                        array(
                            'name' => '消息',
                            'type' => 'message',
                            'link' => '/message',
                            'icon' => 'ri-notification-3-line',
                            'icon_current' => 'ri-notification-3-fill',
                        ),
                        array(
                            'name' => '我的',
                            'type' => 'custom',
                            'link' => '/account',
                            'icon' => 'ri-user-5-line',
                            'icon_current' => 'ri-user-3-fill',
                        ),
                    )
                ),
            )
        )); 
    }
    
    //文字评论模块
    public function comments_settings(){
        //米友社
        // $file_contents = file_get_contents('https://bbs-api-static.miyoushe.com/misc/api/emoticon_set?gids=2');
         
        // $file_contents = json_decode($file_contents,true);
        //print_r($file_contents['data']['list']);
        //bilibili
        // $file_contents = file_get_contents('https://api.bilibili.com/x/emote/user/panel/web?business=reply');
        // $file_contents = json_decode($file_contents,true);
        // $emote = [];
        
        // foreach ($file_contents['data']['packages'][0]['emote'] as $value) {
        //     $emote[] = array(
        //         'name' => str_replace(array('[',']'), '', '哔哩_'.$value['text'] ),
        //         'icon' => $value['url']
        //     );
        // }
        
        \CSF::createSection(self::$prefix, array(
            'parent'    => 'qk_template_options',
            'title'     => '评论模块',
            'icon'      => 'fa  fa-comments',
            'fields'    => array(
                array(
                    'type'       => 'submessage',
                    'style'      => 'warning',
                    'content'    => '<div style="text-align:cent er;"><i class="fa  fa-info-circle "></i> WordPress默认关闭评论翻页功能，如需启用请按此进行设置：<br/>1.进入<a href="' . admin_url('options-discussion.php') . '">WP讨论设置</a>，勾选<code>分页显示评论</code><br/>2.设置默认显示<code>最前</code>一页<br/>3.设置在每个页面顶部显示<code>旧的</code>评论<br/>4.根据需要设置每一页显示数量<br/>5.根据需要设置评论嵌套（推荐开启并设置为3层）</div>',
                    'dependency' => array('comment_close', '==', '', '', 'visible')
                ),
                array(
                    'id'      => 'comment_close',
                    'title'   => '关闭文章评论功能',
                    'type'    => 'switcher',
                    'desc'    => '部分网站无需交互，或需备案审核，可在此关闭所有文章的评论功能。同时每一篇文章可单独关闭评论功能',
                    'default' => false,
                ),
                array(
                    'id'         => 'comment_pagination_type',
                    'title'      => '评论分页类型',
                    'type'       => 'radio',
                    'inline'     => true,
                    'options'    => array(
                        'auto'   => 'AJAX追加列表翻页',
                        'page'   => '数字翻页按钮',
                    ),
                    'default'    => 'page',
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
                // array(
                //     'id'         => 'comment_ajax_auto',
                //     'title'      => ' ',
                //     'type'       => 'switcher',
                //     'subtitle'   => 'AJAX翻页自动加载',
                //     'class'      => 'compact',
                //     'label'      => '页面滚动到列表尽头时，自动加载下一页，关闭为手动点击加载更多',
                //     'default'    => true,
                //     'dependency' => array('comment_pagination_type', '==', 'auto'),
                // ),
                // array(
                //     'id'         => 'comment_ajax_auto_max',
                //     'type'       => 'spinner',
                //     'title'      => ' ',
                //     'subtitle'   => '自动加载页数',
                //     'desc'       => 'AJAX翻页自动加载最多加载几页（为0则不限制，直到加载全部评论）',
                //     'class'      => 'compact',
                //     'default'    => 3,
                //     'max'        => 10,
                //     'min'        => 0,
                //     'step'       => 1,
                //     'unit'       => '页',
                //     'dependency' => array('comment_pagination_type|comment_ajax_auto', '==|!=', 'auto|'),
                // ),
                array(
                    'id'         => 'comment_use_image',
                    'title'      => '是否允许添加图片',
                    'type'       => 'switcher',
                    'default'    => true,
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_use_smiles',
                    'title'      => '是否允许添加表情',
                    'type'       => 'switcher',
                    'help'       => '为了防止恶意评论，建议在后台-设置-讨论：开启"用户必须登录后才能发表评论"',
                    'default'    => true,
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
                array(
                    'id'     => 'comment_smilies_arg',
                    'type'   => 'group',
                    'title'  => ' ',
                    'subtitle'   => '表情组',
                    'class'      => 'compact',
                    'button_title' => '新增表情组',
                    'fields' => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '表情组名'
                        ),
                        array(
                            'id'         => 'size',
                            'type'       => 'radio',
                            'title'      => '表情大小',
                            'options'    => array(
                                'small' => '小（24 x 24）px',
                                'normal' => '中（50 x 50）px',
                                'large' => '大（80 x 80）px',
                            ),
                            'default'    => 'normal'
                        ),
                        array(
                            'id'     => 'list',
                            'type'   => 'group',
                            'title'  => '表情',
                            'button_title' => '新增表情',
                            'fields' => array(
                                array(
                                    'id'    => 'name',
                                    'type'  => 'text',
                                    'title' => sprintf(__('表情名称%s','qk'),'<span class="red">（必填唯一）</span>')
                                ),
                                array(
                                    'id'    => 'icon',
                                    'type'  => 'upload',
                                    'title' => '表情地址',
                                    'preview' => true,
                                    'library' => 'image',
                                ),
                            ),
                            'default'  => array(
                                array(
                                    'name' => '',
                                ),
                            ),
                            // 'default'   =>$emote,//$file_contents['data']['list']
                        ),
                    ),
                    'default'   => array(
                        array(
                            'name' => '',
                        ),
                    ),
                    'dependency' => array('comment_close|comment_use_smiles', '==|!=', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_show_order',
                    'title'      => '是否开启评论排序',
                    'type'       => 'switcher',
                    'default'    => true,
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_show_orderby_author',
                    'title'      => ' ',
                    'subtitle'   => '开启左侧只看作者',
                    'type'       => 'switcher',
                    'class'      => 'compact',
                    'default'    => true,
                    'dependency' => array('comment_close|comment_show_order', '==|!=', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_orderby',
                    'type'       => 'sorter',
                    'title'      => ' ',
                    'subtitle'   => '右侧排序',
                    'default'    => array(
                        'enabled'    => array(
                            'desc'   => '最新',
                            'qk_comment_like'  => '热门',
                        ),
                        'disabled'     => array(
                            'asc'  => '最早',
                            
                        ),
                   ),
                   'dependency' => array('comment_close|comment_show_order', '==|!=', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_ip_location_show',
                    'title'      => '显示评论IP属地',
                    'type'       => 'switcher',
                    'default'    => true,
                    'desc'       => '开启此功能后，仅对新评论内容显示，已评论的内容不会显示<br>由于需要使用网络接口通过用户IP地址获取地理位置，不能保证所有地址都能显示<br>如果您的服务器使用了代理功能，则无法正确的获取用户IP地址，则也无法正常显示<br><a href="'.admin_url('/admin.php?page=qk_main_options#tab=常规设置/ip归属地').'" target="_blank">常规设置/ip归属地</a>设置使用其他接口',
                ),
                array(
                    'id'         => 'comment_title',
                    'type'       => 'text',
                    'title'      => '自定义文案',
                    'subtitle'   => '自定义评论标题',
                    'default'    => '评论',
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_submit_text',
                    'type'       => 'text',
                    'title'      => ' ',
                    'subtitle'   => '自定义评论提交按钮文案',
                    'class'      => 'compact',
                    'default'    => '评论',
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
                array(
                    'id'         => 'comment_placeholder',
                    'type'       => 'text',
                    'title'      => ' ',
                    'subtitle'   => '自定义评论框占位符文案',
                    'class'      => 'compact',
                    'default'    => '只是一直在等你而已，才不是想被评论呢～',
                    'dependency' => array('comment_close', '==', '', '', 'visible'),
                ),
            )
        ));
    }
    
    //分类模块
    public function category_settings(){
        
        \CSF::createSection(self::$prefix, array(
            'parent'    => 'qk_template_options',
            'title'     => '分类标签模块',
            'icon'      => 'fas fa-folder-open',
            'fields'    => array(
                array(
                    'id'         => 'qk_tax_group',
                    'type'       => 'accordion',
                    'title'      => '默认布局设置（暂未使用）',
                    'subtitle'   => '设置后分布局选会优先使用此处设置，并自动生效，不必再去分类中一个一个设置',
                    'accordions' => array(
                        array(
                            'title'  => '布局风格样式设置',
                            'fields' => array(
                                array(
                                    'id'          => 'post_type', //文章卡片样式
                                    'type'        => 'image_select',
                                    'title'       => '文章列表风格样式',
                                    'options'     => array(
                                        'post-1'  => QK_THEME_URI.'/Assets/admin/images/post-1.png', //网格
                                        'post-2'  => QK_THEME_URI.'/Assets/admin/images/post-2.png', //列表
                                        'post-3'  => QK_THEME_URI.'/Assets/admin/images/post-3.png', //文章
                                        // 'post-4'  => QK_THEME_URI.'/Assets/admin/images/search.png', //搜索
                                    ),
                                    'class'       => 'module_type',
                                    'default'     => 'post-1',
                                ),
                                //开启瀑布流显示
                                array(
                                    'id'      => 'waterfall_show',
                                    'type'    => 'switcher',
                                    'title'   => '开启瀑布流显示',
                                    'desc'    => '注意开启瀑布流，远程图片因为获取不到宽度和高度，所以瀑布流不会正常显示，需要本地网站上传的图片封面才可以',
                                    'default' => 0,
                                    'dependency' => array( 
                                        array( 'post_type', 'any', 'post-1,post-3' )
                                    )
                                ),
                                //排序方式
                                array(
                                    'id'         => 'post_order',
                                    'type'       => 'select',
                                    'title'      => '排序方式',
                                    'options'    => array(
                                        'new'      => '最新文章',
                                        'modified' => '修改时间',
                                        'random'   => '随机文章',
                                        'sticky'   => '置顶文章',
                                        'views'    => '浏览最多文章',
                                        'comments' => '评论最多文章'
                                    ),
                                    'default'     => 'new',
                                ),
                                array(
                                    'id'         => 'post_row_count',
                                    'type'       => 'spinner',
                                    'title'      => '每列显示数量',
                                    'unit'       => '个',
                                    'max'        => 20,
                                    'default'    => 5,//做个记号后期动态设置
                                ),
                                array(
                                    'id'         => 'post_count',
                                    'type'       => 'spinner',
                                    'title'      => '显示总数',
                                    'unit'       => '个',
                                    'max'        => 100,
                                    'default'    => 10,//做个记号后期动态设置
                                ),
                                //缩略图比例
                                array(
                                    'id'         => 'post_thumb_ratio',
                                    'type'       => 'text',
                                    'title'      => '缩略图比例',
                                    'default'    => '1/1.725',//做个记号后期动态设置
                                    'desc'       => '缩略图高度自适应的情况下不生效，请填写宽和高的比例，比如4/3，1/0.618。',
                                    'dependency' => array( 
                                        array( 'post_type', 'any', 'post-2' )
                                    )
                                ),
                                //文章meta选择
                                array(
                                    'id'         => 'post_meta',
                                    'type'       => 'checkbox',
                                    'title'      => '文章meta显示选择',
                                    'inline'     => true,
                                    'options'    => array(
                                        'title'  => '模块标题',
                                        'desc'   => '模块描述',
                                        'links'  => '导航',
                                        'user'   => '作者',
                                        'date'   => '时间',
                                        'like'   => '喜欢数量',
                                        'comment'=> '评论数量',
                                        'views'  => '浏览量',
                                        'cats'   => '分类',
                                        'desc'   => '摘要',
                                    ),
                                ),
                            )
                        )
                    )
                ),
                array(
                    'id'        => 'tax_fliter_group',
                    'type'      => 'group',
                    'title'     => '全局分类筛选设置',
                    'subtitle'      => '批量设置后分类筛选会优先使用此处设置，并自动生效，不必再去分类中一个一个设置',
                    'fields'    => array(
                        array(
                            'id'    => 'title',
                            'type'  => 'text',
                            'title' => sprintf('筛选设置标题%s','<span class="red">（必填）</span>'),
                            'desc'  => '给当前这个筛选设置起个名字',
                        ),
                        array(
                            'id'      => 'filter_open',
                            'type'    => 'switcher',
                            'title'   => '开启筛选功能',
                            'default' => 0,
                        ),
                        array(
                            'id'        => 'fliter_group',
                            'type'      => 'group',
                            'title'     => '筛选组',
                            'fields'    => array(
                                array(
                                    'id'    => 'title',
                                    'type'  => 'text',
                                    'title' => sprintf('筛选名称%s','<span class="red">（必填）</span>'),
                                    'desc'  => '给当前这个筛选起个名字',
                                ),
                                array(
                                    'id'          => 'type',
                                    'type'        => 'button_set',
                                    'title'       => '筛选类型',
                                    'options'     => array(
                                        'cats' => '分类',
                                        'tags'   => '标签', 
                                        'metas'   => sprintf('自定义字段%s','<span class="red">（高级）</span>'),
                                        'orderbys'   => '排序',
                                    ),
                                    'default'     => 'cats',
                                ),
                                array(
                                    'id'         => 'cats',
                                    'title'      => '筛选的分类',
                                    'type'       => 'select',
                                    'placeholder' => '选择分类',
                                    'chosen'     => true,
                                    'multiple'   => true,
                                    'sortable'   => true,
                                    'options'     => 'categories',
                                    'query_args'  => array(
                                        'taxonomy'  => array('category','video_cat')
                                    ),
                                    'desc'       => '请选择要筛选的文章分类，可以拖动排序',
                                    'dependency'        => array( 'type', '==', 'cats' )
                                ),
                                array(
                                    'id'         => 'tags',
                                    'title'      => '筛选的标签',
                                    'type'       => 'select',
                                    'placeholder' => '选择标签',
                                    'chosen'     => true,
                                    'multiple'   => true,
                                    'sortable'   => true,
                                    'options'    => 'tag',
                                    'desc'       => '请选择要筛选的文章标签，可以拖动排序',
                                    'dependency'        => array( 'type', '==', 'tags' )
                                ),
                                array(
                                    'type'    => 'submessage',
                                    'style'   => 'warning',
                                    'content' => '<p>通过此功能可实现更加复杂、精细化的内容筛选</p>
                                                  <p>使用自定义字段筛选可以根据自定义字段的值来筛选和过滤文章或页面。例如，如果你在文章中添加了一个自定义字段“作者”，你可以使用自定义字段筛选来只显示特定作者的文章。</p>
                                                  <p>例如影视网站︰[类型]有[剧情/喜剧悬疑/惊悚/犯罪]，[地区]有[大陆/美国/日韩/港台/印度]，[年份]有[2022/2021(2020/10年代/OO年代]等</p>
                                                  <p>注意事项:添加类型key时候，只能使用英文加下划线，不能有空格，且尽量复杂一点，避免与其他mate_key重复</p>
',
                                    'dependency'        => array( 'type', '==', 'metas' )
                                ),
                                array(
                                    'id'    => 'meta_key',
                                    'type'  => 'text',
                                    'title' => '筛选的字段类型 meta_key',
                                    'dependency'        => array( 'type', '==', 'metas' )
                                ),
                                array(
                                    'id'     => 'metas',
                                    'type'   => 'repeater',
                                    'title'  => '筛选的字段值 meta_value',
                                    'fields' => array(
                                        array(
                                            'id'    => 'meta_value',
                                            'type'  => 'text',
                                            'title' => 'meta_value 值'
                                        ),
                                        array(
                                            'id'    => 'meta_name',
                                            'type'  => 'text',
                                            'title' => '显示名称'
                                        ),
                                    ),
                                    'default'   => array(
                                        array(
                                            'meta_value' => '',
                                            'meta_name' => '',
                                        ),
                                    ),
                                    'dependency'        => array( 'type', '==', 'metas' )
                                ),
                                array(
                                    'id'          => 'orderbys',
                                    'type'        => 'select',
                                    'title'       => '排序选择',
                                    'chosen'     => true,
                                    'multiple'   => true,
                                    'sortable'   => true,
                                    'options'     => array(
                                        'new'  => '最新',
                                        'random'  => '随机',
                                        'views'  => '浏览',
                                        'like'  => '喜欢',
                                        'comments'  => '评论',
                                        'modified'  => '更新',
                                    ),
                                    'dependency'    => array( 'type', '==', 'orderbys' )
                                ),
                            )
                        ),
                    ),
                    // 'default'   => array(
                    //     array(
                    //         'title'     => '默认筛选',
                    //     ),
                    // ),
                ),
            )
        ));
    }
    
    //视频模块
    public function video_settings(){
        
        \CSF::createSection(self::$prefix, array(
            'parent'    => 'qk_template_options',
            'title'     => '视频模块',
            'icon'      => 'fas fa-play-circle',
            'fields'    => array(
                array(
                    'id'        => 'qk_video_options',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'type'    => 'subheading',
                            'content' => '视频页面设置',
                        ),
                        array(
                            'id'       => 'wrapper_width',
                            'type'     => 'spinner',
                            'title'    => '视频页面布局宽度',
                            'subtitle' => '页面布局的最大宽度',
                            'desc'     => __('页面宽度已经经过精心的调整，非特殊需求请勿调整，宽度过大会造成显示不协调', 'qk'),
                            'max'      => 2560,
                            'min'      => 0,
                            'step'     => 50,
                            'unit'     => 'px',
                            'default'  => 1200,
                        ),
                        array(
                            'id'      => 'sidebar_open',
                            'type'    => 'switcher',
                            'title'   => '开启视频页侧边栏小工具',
                            'default' => true,
                            'label'   => '优先显示文章中的此项设置，如果文章中未设置，则此处设置生效',
                        ),
                        array(
                            'id'       => 'sidebar_width',
                            'type'     => 'spinner',
                            'title'    => '视频页面小工具的宽度',
                            'desc'     => __('这里是全局小工具宽度，可在每个分类和文章下单独设置小工具宽度', 'qk'),
                            'max'      => 1000,
                            'min'      => 0,
                            'step'     => 10,
                            'unit'     => 'px',
                            'default'  => 300,
                            'dependency' => array('sidebar_open', '!=', '', '', 'visible'),
                        ),
                    ),
                ),
                array(
                    'id'        => 'qk_episode_options',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'type'    => 'subheading',
                            'content' => '剧集页面设置',
                        ),
                        array(
                            'id'       => 'wrapper_width',
                            'type'     => 'spinner',
                            'title'    => '播放页面布局宽度',
                            'subtitle' => '页面布局的最大宽度',
                            'desc'     => __('页面宽度已经经过精心的调整，非特殊需求请勿调整，宽度过大会造成显示不协调', 'qk'),
                            'max'      => 2560,
                            'min'      => 0,
                            'step'     => 50,
                            'unit'     => 'px',
                            'default'  => 1200,
                        ),
                        array(
                            'id'      => 'sidebar_open',
                            'type'    => 'switcher',
                            'title'   => '开启播放页侧边栏小工具',
                            'default' => true,
                            'label'   => '优先显示文章中的此项设置，如果文章中未设置，则此处设置生效',
                        ),
                        array(
                            'id'       => 'sidebar_width',
                            'type'     => 'spinner',
                            'title'    => '播放页面小工具的宽度',
                            'desc'     => __('这里是全局小工具宽度，可在每个分类和文章下单独设置小工具宽度', 'qk'),
                            'max'      => 1000,
                            'min'      => 0,
                            'step'     => 10,
                            'unit'     => 'px',
                            'default'  => 300,
                            'dependency' => array('sidebar_open', '!=', '', '', 'visible'),
                        ),
                    ),
                ),
            )
        ));
    }
    
    //发现模块
    public function search_settings(){
        \CSF::createSection(self::$prefix, array(
            'parent'      => 'qk_template_options',
            'title'       => '发现模块',
            'icon'        => 'fa  fa-home',
            'description' => '',
            'fields'      => array(
                array(
                    'id'          => 'allow_search_types',
                    'type'        => 'select',
                    'title'       => '允许搜索的类型',
                    'placeholder' => '选择搜索的类型',
                    'chosen'     => true,
                    'multiple'   => true,
                    'sortable'   => true,
                    'options'     => qk_get_search_type(),
                    'default'     => array_keys(qk_get_search_type()),
                    'desc'       => '请选择允许的搜索类型，可以拖动排序',
                ),
                array(
                    'id'     => 'search_index',
                    'type'   => 'group', //组
                    'title'  => '发现页面首页',
                    'accordion_title_auto' => true, //收听标题的第一个输入文本值。
                    'accordion_title_number' => true,
                    'fields' => array(
                        array(
                            'id'      => 'title',
                            'type'    => 'text',
                            'title'   =>  sprintf(__('模块标题%s','qk'),'<span class="red">（必填）</span>'),
                            'desc'    => '给这个模块起个名字，某些模块下会显示这个标题',
                        ),
                        array(
                            'id'         => 'module_type',
                            'title'      => '模块类型',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => qk_get_search_type(),
                            'default'    => 'post',
                        ),
                        array(
                            'id'          => 'post__in',
                            'type'        => 'select',
                            'title'       => '推荐文章',
                            'placeholder' => '选择推荐文章',
                            'chosen'      => true,
                            'ajax'        => true,
                            'multiple'    => true,
                            'sortable'    => true,
                            'options'     => 'posts',
                            'query_args'  => array(
                                'post_type'  => array('post')
                            ),
                            'settings'   => array(
                                'min_length' => 1
                            ),
                            'dependency'        => array( 'module_type', 'any', 'post' )
                        ),
                        array(
                            'id'          => 'video__in',
                            'type'        => 'select',
                            'title'       => '推荐课程',
                            'placeholder' => '选择推荐课程',
                            'chosen'      => true,
                            'ajax'        => true,
                            'multiple'    => true,
                            'sortable'    => true,
                            'options'     => 'posts',
                            'query_args'  => array(
                                'post_type'  => array('video')
                            ),
                            'settings'   => array(
                                'min_length' => 1
                            ),
                            'dependency'        => array( 'module_type', 'any', 'video' )
                        ),
                         array(
                            'id'          => 'circle__in',
                            'type'        => 'select',
                            'title'       => '推荐圈子帖子',
                            'placeholder' => '选择圈子帖子',
                            'chosen'      => true,
                            'ajax'        => true,
                            'multiple'    => true,
                            'sortable'    => true,
                            'options'     => 'posts',
                            'query_args'  => array(
                                'post_type'  => array('circle')
                            ),
                            'settings'   => array(
                                'min_length' => 1
                            ),
                            'dependency'        => array( 'module_type', 'any', 'circle' )
                        ),
                        array(
                            'id'          => 'user__in',
                            'type'        => 'select',
                            'title'       => '推荐用户',
                            'placeholder' => '选择推荐用户',
                            'chosen'      => true,
                            'ajax'        => true,
                            'multiple'    => true,
                            'sortable'    => true,
                            'options'     => 'users',
                            'settings'   => array(
                                'min_length' => 1
                            ),
                            'dependency'        => array( 'module_type', '==', 'user' )
                        ),
                        array(
                            'id'         => 'categorys',
                            'title'      => '推荐分类',
                            'type'       => 'select',
                            'placeholder' => '选择分类',
                            'chosen'     => true,
                            'multiple'   => true,
                            'sortable'   => true,
                            'options'     => 'categories',
                            'dependency'        => array( 'module_type', '==', 'category' )
                        ),
                        array(
                            'id'         => 'post_tags',
                            'title'      => '推荐分类',
                            'type'       => 'select',
                            'placeholder' => '选择分类',
                            'chosen'     => true,
                            'multiple'   => true,
                            'sortable'   => true,
                            'options'     => 'tags',
                            'dependency'        => array( 'module_type', '==', 'post_tag' )
                        ),
                        array(
                            'id'         => 'topics',
                            'title'      => '推荐话题',
                            'type'       => 'select',
                            'placeholder' => '选择分类',
                            'chosen'     => true,
                            'multiple'   => true,
                            'sortable'   => true,
                            'options'     => 'categories',
                            'query_args'  => array(
                                'taxonomy'  => array('topic')
                            ),
                            'dependency'        => array( 'module_type', '==', 'topic' )
                        ),
                        array(
                            'id'         => 'circle_cats',
                            'title'      => '推荐圈子',
                            'type'       => 'select',
                            'placeholder' => '选择分类',
                            'chosen'     => true,
                            'multiple'   => true,
                            'sortable'   => true,
                            'options'     => 'categories',
                            'query_args'  => array(
                                'taxonomy'  => array('circle_cat')
                            ),
                            'dependency'        => array( 'module_type', '==', 'circle_cat' )
                        ),
                    )
                )
            )
        ));
    }
}