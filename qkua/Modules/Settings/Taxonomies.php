<?php namespace Qk\Modules\Settings;
use Qk\Modules\Common\Circle;
use Qk\Modules\Common\User;
use Qk\Modules\Common\CircleRelate;
/**
 * 分类设置
 * 
 * */
class Taxonomies{
    
    //设置主KEY
    public static $prefix = 'qk_tax';

    public function init(){
         // Create taxonomy options
        \CSF::createTaxonomyOptions( self::$prefix, array(
            'taxonomy'  => array( 'category', 'post_tag', 'video_cat','circle_cat','topic'),
            'data_type' => 'unserialize', // 序列化. `serialize` or `unserialize` 单个 id获取值
        ));
        
        add_action( 'csf_qk_tax_circle_saved', array($this,'save_circle_action'), 10, 2 );
        
        //注册分类设置
        $this->register_taxonomy_metabox();
        
        $this->register_taxonomy_circle_metabox();
        
        //表格
        add_action('admin_init', array($this,'custom_taxonomy_columns'));
    }
    
    
    // 添加分类ID列
    function custom_taxonomy_column($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb']; // 复选框
        unset($columns['cb']);
        $new_columns['id'] = 'ID';
        return array_merge($new_columns, $columns);
    }
    
    // 显示分类ID
    function custom_taxonomy_custom_content($value, $column_name, $tax_id) {
        if ($column_name === 'id') {
            return $tax_id;
        }
        
    }
    
    // 封装循环部分为函数
    function custom_taxonomy_columns() {
        $excluded_taxonomies = array('nav_menu', 'post_format', 'wp_theme', 'wp_template_part_area','link_category'); // 要排除的分类和标签类型
        $all_taxonomies = get_taxonomies(); // 获取所有已注册的分类和标签类型
        
        $taxonomies = array_diff($all_taxonomies, $excluded_taxonomies); // 排除不需要的分类和标签类型
        foreach ($taxonomies as $taxonomy) {
            add_filter('manage_edit-' . $taxonomy . '_columns', array($this, 'custom_taxonomy_column'));
            add_action('manage_' . $taxonomy . '_custom_column', array($this, 'custom_taxonomy_custom_content'), 10, 3);
        }
    }

    public function register_taxonomy_metabox(){
        //serialize
        // $meta = get_term_meta( 11, 'qk_tax', true );
        
        //unserialize
        //print_r(get_term_meta( 11, 'seo_title', true ));
         //Create a section
         
        $taxonomy = $_GET['taxonomy'];
        
        $args = array(
            'title' => 'qk主题分类设置',
            'fields' => array(
                array(
                    'id'    => 'seo_title',
                    'type'  => 'text',
                    'title' => 'SEO标题',
                ),
                array(
                    'id'    => 'seo_keywords',
                    'type'  => 'text',
                    'title' => 'SEO关键词',
                ),
                array(
                    'id'    => 'qk_tax_img',
                    'type'  => 'upload',
                    'title' => '特色图',
                    'preview' => true,
                    'library' => 'image',
                ),
                array(
                    'id'    => 'qk_tax_cover',
                    'type'  => 'upload',
                    'title' => '特色背景图',
                    'preview' => true,
                    'library' => 'image',
                ),
                array(
                    'id'          => 'qk_tax_sticky_posts',
                    'type'        => 'select',
                    'title'       => '分类置顶文章',
                    'placeholder' => '搜索选择置顶文章',
                    'chosen'      => true,
                    'ajax'        => true,
                    'multiple'    => true,
                    'sortable'    => true,
                    'options'     => 'posts',
                    'query_args'  => array(
                        'post_type'  => array('post','video','circle')
                    ),
                    'settings'   => array(
                        'min_length' => 1
                    )
                ),
                array(
                    'id'         => 'qk_tax_group',
                    'type'       => 'accordion',
                    'title'      => '',
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
                                        //array( 'post_type', 'any', 'post-2' ),
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
                                ),
                            )
                        )
                    )
                ),
                array(
                    'id'         => 'qk_filter',
                    'type'       => 'accordion',
                    'title'      => '',
                    'accordions' => array(
                        array(
                            'title'  => '分类筛选设置',
                            'fields' => array(
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
                                            'title'       => '选择一个调用内容',
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
                                    ),
                                ),
                            ),
                        )
                    )
                ),
                array(
                    'id'    => 'qk_show_sidebar',
                    'type'  => 'switcher',
                    'title' => '是否显示侧边栏',
                    'default' => false,
                    'desc'    => '如果需要显示侧边栏，请前往外观->小工具里面，设置一下第一个侧边栏小工具，否则仍然不显示',
                ),
                array(
                    'id'         => 'qk_tax_pagination_type',
                    'title'      => '分页加载类型',
                    'type'       => 'radio',
                    'inline'     => true,
                    'options'    => array(
                        'auto'   => 'AJAX下拉无限加载',
                        'page'   => 'AJAX数字分页加载',
                    ),
                    'default'    => 'page',
                ),
            )
        );
        
        if($taxonomy == 'circle_cat' || $taxonomy == 'topic') {
            unset($args['fields'][8]);
            unset($args['fields'][6]);
            unset($args['fields'][5]);
        }
        
        \CSF::createSection( self::$prefix, $args);

    }
    
    public function register_taxonomy_circle_metabox(){
        //serialize
        // $meta = get_term_meta( 11, 'qk_tax', true );
        
        //unserialize
        //print_r(get_term_meta( 11, 'seo_title', true ));
         //Create a section
         
        \CSF::createTaxonomyOptions('qk_tax_circle', array(
            'taxonomy'  => array('circle_cat'),
            'data_type' => 'unserialize', // 序列化. `serialize` or `unserialize` 单个 id获取值
        ));
        
        $circle_cats = Circle::get_circle_cats();
        $cats = array();
        
        if(!isset($circle_cats['error'])) {
            foreach ($circle_cats as $k => $v) {
                $cats[$v['name']] = $v['name'];
            }
        }
        
        $roles = User::get_user_roles();

        $roles_options = array();
        
        foreach ($roles as $key => $value) {
            $roles_options[$key] = $value['name'];
        }
        
        $moment_roles = array(
            'admin' => '圈子创建者',
            'staff' => '圈子版主'
        );
        
        foreach ($roles as $key => $value) {
            $moment_roles[$key] = $value['name'];
        }
        
        $default_roles = array_keys($moment_roles);
        
        //圈子名字
        $circle_name = qk_get_option('circle_name');
         
        \CSF::createSection( 'qk_tax_circle', array(
            'title' => sprintf('qk主题%s设置',$circle_name), 
            'fields' => array(
                array(
                    'id'    => 'qk_circle_official',
                    'type'  => 'switcher',
                    'title'  => '官方圈认证',
                    'desc'  => '是否是官方的圈子，开启后会显示官方圈子标签',
                    'default' => false
                ),
                array(
                    'id'    => 'qk_circle_cat',
                    'type'  => 'radio',
                    'title'  => sprintf('选择%s分类',$circle_name), 
                    'options' => $cats,
                    'desc'  => '如果不选择分类，筛选中不会显示',
                    'inline' => true
                ),
                array(
                    'id'          => 'qk_circle_admin',
                    'type'        => 'select',
                    'title'       => '创建者',
                    'subtitle'    => '及圈子管理员、圈主',
                    'options'     => 'user',
                    'placeholder' => '输入用户ID、昵称、邮箱以搜索用户',
                    'ajax'        => true,
                    'chosen'      => true,
                    'default'     => 1,
                    'settings'    => array(
                        'min_length' => 1,
                        'width' => '50%',
                    )
                ),
                
                array(
                    'id'          => 'qk_circle_staff',
                    'type'        => 'select',
                    'title'       => '圈子版主',
                    'subtitle'    => '及版主',
                    'options'     => 'user',
                    'placeholder' => '输入用户ID、昵称、邮箱以搜索用户',
                    'ajax'        => true,
                    'chosen'      => true,
                    'multiple'    => true,
                    'sortable'    => true,
                    'settings'    => array(
                        'min_length' => 1,
                    )
                ),
                array(
                    'id'         => 'qk_circle_privacy',
                    'type'       => 'radio',
                    'title'      => sprintf('%s隐私',$circle_name),
                    'inline'     => true,
                    'options'    => array(
                        'public'   => sprintf('%s内帖子公开显示',$circle_name),
                        'private'  => sprintf('%s内帖子只对圈友开放',$circle_name),
                    ),
                    'default'    => 'public',
                ),
                array(
                    'id'         => 'qk_circle_type',
                    'type'       => 'radio',
                    'title'      => sprintf('%s类型',$circle_name),
                    'inline'     => true,
                    'options'    => array(
                        'free'   => '免费',
                        'money'  => '付费',
                        'credit' => '积分',
                        'roles'  => '专属',
                        'password'  => '密码',
                    ),
                    'default'    => 'free',
                ),
                array(
                    'id'       => 'qk_circle_password',
                    'type'       => 'text',
                    'title'    => '入圈密码',
                    'desc'       => '密码长度自定义',
                    'default'  => '1234',
                    'dependency' => array( 
                        array( 'qk_circle_type', '==', 'password' ),
                    )
                ),
                array(
                    'id'         => 'qk_circle_roles',
                    'type'       => 'checkbox',
                    'title'      => '请选择允许加入圈子的用户组',
                    'inline'     => true,
                    'options'    => $roles_options,
                    'desc'       => sprintf('如果您修改了此权限，请前往%s%s设置%s中重置以下该%s的数据。%s如果没有用户组，请前往%s用户设置%s进行设置','<a href="'.admin_url('/admin.php?page=b2_circle_data').'" target="_blank">',$circle_name,'</a>',$circle_name,'<br>','<a href="'.admin_url().'/admin.php?page=b2_normal_user" target="_blank">','</a>'),
                    'dependency'   => array(
                        array( 'qk_circle_type', '==', 'roles' )
                    ),
                ),
                array(
                    'id'        => 'qk_circle_pay_group',
                    'type'      => 'group',
                    'title'     => '圈子付费及积分支付',
                    'button_title' => '新增付费入圈时效',
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => sprintf(__('名称%s','qk'),'<span class="red">（必填）</span>'),
                            'desc' => sprintf('入圈支付时显示，比如 %s 等等','<code>月付</code>、<code>季付</code>、<code>年付</code>、<code>永久有效</code>')
                        ),
                        array(
                            'id'         => 'price',
                            'type'       => 'number',
                            'title'      => '购买价格',
                            'default'    => '',
                        ),
                        array(
                            'id'      => 'time',
                            'type'    => 'spinner',
                            'title'   => '有效期',
                            'desc'    => '加入圈子有效期限。填<code>0</code>则为永久有效',
                            'min'     => 0,
                            'step'    => 1,
                            'default' => '',
                            'unit'    => '天',
                        ),
                        array(
                            'id'          => 'discount',
                            'type'        => 'spinner',
                            'title'       => '会员入圈折扣比例（暂未实现）',
                            'min'         => 0,
                            'max'         => 100,
                            'step'        => 1,
                            'unit'        => '%',
                            'default'     => 100,
                        ),
                    ),
                    'default'   => array(
                        array(
                            'name' => '永久有效',
                            'time'     => 0,
                            'price'    => 1,
                            'discount'     => 100,
                        )
                    ),
                    'dependency' => array( 
                        array( 'qk_circle_type', 'any', 'money,credit' ),
                    )
                ),
                array(
                    'id'        => 'qk_circle_tags',
                    'type'      => 'group',
                    'title'     => '圈子帖子板块（标签）',
                    'button_title' => '新增板块',
                    'desc' => '增加后不要随意改动，用户在当前圈子发布帖子的时候需要选择该帖子所属板块，方便帖子筛选管理。',
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '板块名称（必填）',
                        ),
                        
                    ),
                    'default'   => array(
                        array(
                            'name'     => '综合',
                        )
                    ),
                ),
                array(
                    'id'        => 'qk_circle_recommends',
                    'type'      => 'group',
                    'title'     => '圈子推荐栏连接',
                    'button_title' => '新增推荐',
                    'desc' => '用作圈子信息下方显示',
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '推荐名称（必填）',
                        ),
                        array(
                            'id'    => 'link',
                            'type'  => 'text',
                            'title' => '连接地址（必填）',
                        ),
                        array(
                            'id'    => 'icon',
                            'type'  => 'upload',
                            'title' => '图片',
                            'preview' => true,
                            'library' => 'image',
                        ),
                    ),
                    'default'   => array(
                        array(
                            'name'     => '圈子版规',
                        )
                    ),
                ),
                array(
                    'id'          => 'qk_circle_info_show',
                    'type'        => 'select',
                    'title'       => '圈子顶部信息显示',
                    'options'     => array(
                        0         => '关闭',
                        'global'  => '使用全局设置',
                        'pc'      => 'pc端',
                        'mobile'  => '移动端',
                        'all'     => 'pc端和移动端都显示'
                    ),
                    'default'     => 'global',
                    'desc'        => '圈子顶部信息，如果关闭PC端显示，可以在侧边栏添加【圈子信息】小工具'
                ),
                array(
                    'id'          => 'qk_circle_input_show',
                    'type'        => 'select',
                    'title'       => '圈子帖子发布框显示',
                    'options'     => array(
                        0         => '关闭',
                        'global'  => '使用全局设置',
                        'pc'      => 'pc端',
                        'mobile'  => '移动端',
                        'all'     => 'pc端和移动端都显示'
                    ),
                    'default'     => 'global',
                ),
                array(
                    'id'      => 'qk_circle_join_post_open',
                    'type'    => 'select',
                    'title'   => '加入圈子才能发帖',
                    'options'     => array(
                        0  => '关闭',
                        1  => '开启',
                        'global'  => '使用全局设置',
                        
                    ),
                    'default'     => 'global',
                ),
                array(
                    'id'          => 'qk_circle_post_open',
                    'type'        => 'select',
                    'title'       => '是否允许用户发帖(发帖功能)',
                    'subtitle'    => '您可以在这里单独给圈子编辑器功能设置。',
                    //'placeholder' => 'Select an option',
                    'options'     => array(
                        0  => '关闭',
                        'global'  => '使用全局设置',
                        1  => '自定义该功能',
                    ),
                    'default'     => 'global',
                    'desc'        => '如果使用全局设置，这里请选择全局设置，并且在主题设置->圈子社区->发帖功能设置（全局）中编辑全局权限'
                ),
                array(
                    'id'        => 'qk_circle_post',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'      => 'min_word_limit',
                            'type'    => 'spinner',
                            'title'   => '最小帖子内容字数限制',
                            'desc'    => '内容最小字数限制',
                            'min'     => 1,
                            'step'    => 10,
                            'unit'    => '个',
                            'default' => 5,
                        ),
                        array(
                            'id'      => 'max_word_limit',
                            'type'    => 'spinner',
                            'title'   => '最大帖子内容字数限制',
                            'desc'    => '内容最大字数限制',
                            'min'     => 1,
                            'step'    => 10,
                            'unit'    => '个',
                            'default' => 500,
                        ),
                        array(
                            'id'      => 'image_count',
                            'type'    => 'spinner',
                            'title'   => '最多上传图片多少张',
                            'desc'    => sprintf('需要给当前用户允许上传图片的权限%s','<a target="_blank" href="'.admin_url('/admin.php?page=qk_main_options#tab=%e5%b8%b8%e8%a7%84%e8%ae%be%e7%bd%ae/%e5%aa%92%e4%bd%93%e5%8f%8a%e6%9d%83%e9%99%90%ef%bc%88%e5%85%a8%e5%b1%80%ef%bc%89').'">媒体设置</a>'),
                            'min'     => 0,
                            'step'    => 1,
                            'default' => 9,
                            'unit'    => '个',
                        ),
                        array(
                            'id'      => 'video_count',
                            'type'    => 'spinner',
                            'title'   => '最多上传视频多少个',
                            'desc'    => sprintf('需要给当前用户允许上传视频的权限%s','<a target="_blank" href="'.admin_url('/admin.php?page=qk_main_options#tab=%e5%b8%b8%e8%a7%84%e8%ae%be%e7%bd%ae/%e5%aa%92%e4%bd%93%e5%8f%8a%e6%9d%83%e9%99%90%ef%bc%88%e5%85%a8%e5%b1%80%ef%bc%89').'">媒体设置</a>'),
                            'min'     => 0,
                            'step'    => 1,
                            'default' => 1,
                            'unit'    => '个',
                        ),
                        array(
                            'id'      => 'file_count',
                            'type'    => 'spinner',
                            'title'   => '最多上传文件多少个',
                            'desc'    => sprintf('需要给当前用户允许上传文件的权限%s','<a target="_blank" href="'.admin_url('/admin.php?page=qk_main_options#tab=%e5%b8%b8%e8%a7%84%e8%ae%be%e7%bd%ae/%e5%aa%92%e4%bd%93%e5%8f%8a%e6%9d%83%e9%99%90%ef%bc%88%e5%85%a8%e5%b1%80%ef%bc%89').'">媒体设置</a>'),
                            'min'     => 0,
                            'step'    => 1,
                            'default' => 1,
                            'unit'    => '个',
                        ),
                    ),
                    'dependency' => array( 'qk_circle_post_open', '==', '1' ),
                ),
                array(
                    'id'        => 'qk_circle_editor_toolbar',
                    'type'      => 'group',
                    'title'     => '工具栏按钮',
                    'subtitle' => '这只是配置工具栏按钮显示与隐藏排序等',
                    'button_title' => '新增工具按钮',
                    'desc' => '请不要重复添加相同的工具',
                    'max' => 8,
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '工具按钮名称',
                        ),
                        array(
                            'id'    => 'name_show',
                            'type'  => 'switcher',
                            'title' => '显示按钮名称',
                            'desc'  => '意思就是不显示文字，只显示图标',
                            'default' => true,
                        ),
                        array(
                            'id'    => 'icon',
                            'type'  => 'text',
                            'title' => '工具按钮图标',
                        ),
                        array(
                            'id'         => 'tool',
                            'title'      => '工具按钮类型',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'circle_cat'   => '圈子',
                                'topic'   => '话题',
                                'emoji'   => '表情',
                                'image'   => '图片',
                                'video'   => '视频',
                                'file'   => '文件',
                                'vote'   => '投票',
                                'recommend'   => '种草',
                                'privacy'   => '阅读权限',
                                //'publish' => '发布',     
                            ),
                        ),
                    ),
                    'default'   => array(
                        array(
                            'name'     => '圈子',
                            'name_show' => true,
                            'icon' => 'ri-donut-chart-line',
                            'tool' => 'circle_cat',
                        ),
                        array(
                            'name'     => '话题',
                            'name_show' => true,
                            'icon' => 'ri-hashtag',
                            'tool' => 'topic',
                        ),
                        array(
                            'name'     => '表情',
                            'name_show' => true,
                            'icon' => 'ri-emotion-line',
                            'tool' => 'emoji',
                        ),
                        array(
                            'name'     => '图片',
                            'name_show' => true,
                            'icon' => 'ri-gallery-line',
                            'tool' => 'image',
                        ),
                        array(
                            'name'     => '视频',
                            'name_show' => true,
                            'icon' => 'ri-video-line',
                            'tool' => 'video',
                        ),
                        array(
                            'name'     => '投票',
                            'name_show' => true,
                            'icon' => 'ri-chat-poll-line',
                            'tool' => 'vote',
                        ),
                        array(
                            'name'     => '种草',
                            'name_show' => true,
                            'icon' => 'ri-seedling-line',
                            'tool' => 'recommend',
                        ),
                        array(
                            'name'     => '公开',
                            'name_show' => true,
                            'icon' => 'ri-earth-line',
                            'tool' => 'privacy',
                        ),
                    ),
                    'dependency' => array( 'qk_circle_post_open', '==', '1' ),
                ),
                array(
                    'id'          => 'qk_circle_moment_role_open',
                    'type'        => 'select',
                    'title'       => '发帖权限',
                    'subtitle'    => '您可以在这里单独给某个帖子设置发布阅读权限等。',
                    //'placeholder' => 'Select an option',
                    'options'     => array(
                        'global'  => '使用全局设置',
                        1  => '自定义该权限',
                    ),
                    'default'     => 'global',
                    'desc'        => '如果使用全局设置，这里请选择全局设置，并且在主题设置->圈子社区->圈子权限（全局）中编辑全局权限'
                ),
                array(
                    'id'        => 'qk_circle_moment',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'         => 'insert',
                            'type'       => 'checkbox',
                            'title'      => '允许发帖',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'insert_public',
                            'type'       => 'checkbox',
                            'title'      => '允许发帖无需审核',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        )
                    ),
                    'dependency' => array( 'qk_circle_moment_role_open', '==', '1' ),
                ),
                array(
                    'id'        => 'qk_circle_moment_type_role',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'type'    => 'subheading',
                            'content' => '发帖功能权限',
                        ),
                        array(
                            'id'         => 'vote',
                            'type'       => 'checkbox',
                            'title'      => '允许发布投票',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'ask',
                            'type'       => 'checkbox',
                            'title'      => '允许发布提问',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'card',
                            'type'       => 'checkbox',
                            'title'      => '允许发布文章卡片',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'recommend',
                            'type'       => 'checkbox',
                            'title'      => '允许发布种草',
                            'inline'     => true,
                            'options'    => $moment_roles,
                        ),
                    ),
                    'dependency' => array( 'qk_circle_moment_role_open', '==', '1' ),
                ),
                array(
                    'id'        => 'qk_circle_moment_role',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'type'    => 'subheading',
                            'content' => '发帖隐私能力设置权限',
                        ),
                        array(
                            'id'         => 'login',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限登录可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'money',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限付费可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'credit',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限积分支付可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'comment',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限评论可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'password',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限密码可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'fans',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限粉丝可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                        array(
                            'id'         => 'roles',
                            'type'       => 'checkbox',
                            'title'      => '允许设置权限组可见',
                            'inline'     => true,
                            'options'    => $moment_roles,
                            'default'    => $default_roles,
                        ),
                    ),
                    'dependency' => array( 'qk_circle_moment_role_open', '==', '1' ),
                ),
                array(
                    'id'        => 'qk_circle_moment_manage_role',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'type'    => 'heading',
                            'content' => '帖子管理权限',
                        ),
                        array(
                            'id'         => 'edit',
                            'type'       => 'checkbox',
                            'title'      => '允许编辑自己管理下的帖子',
                            'inline'     => true,
                            'options'    => array(
                                'admin' => '圈子创建者',
                                'staff' => '圈子版主'
                            ),
                            'default'    => array(
                                'admin',
                                'staff'
                            ),
                        ),
                        array(
                            'id'         => 'delete',
                            'type'       => 'checkbox',
                            'title'      => '允许删除自己管理圈子下的帖子',
                            'inline'     => true,
                            'options'    => array(
                                'admin' => '圈子创建者',
                                'staff' => '圈子版主'
                            ),
                            'default'    => array(
                                'admin',
                                'staff'
                            ),
                        ),
                        array(
                            'id'         => 'best',
                            'type'       => 'checkbox',
                            'title'      => '允许加精自己管理圈子下的帖子',
                            'inline'     => true,
                            'options'    => array(
                                'admin' => '圈子创建者',
                                'staff' => '圈子版主'
                            ),
                            'default'    => array(
                                'admin',
                                'staff'
                            ),
                        ),
                        array(
                            'id'         => 'sticky',
                            'type'       => 'checkbox',
                            'title'      => '允许置顶自己管理圈子下的帖子',
                            'inline'     => true,
                            'options'    => array(
                                'admin' => '圈子创建者',
                                'staff' => '圈子版主'
                            ),
                            'default'    => array(
                                'admin',
                                'staff'
                            ),
                        ),
                        array(
                            'id'         => 'public',
                            'type'       => 'checkbox',
                            'title'      => '允许审核自己管理圈子下的帖子',
                            'inline'     => true,
                            'options'    => array(
                                'admin' => '圈子创建者',
                                'staff' => '圈子版主'
                            ),
                            'default'    => array(
                                'admin',
                                'staff'
                            ),
                        ),
                    ),
                    'dependency' => array( 'qk_circle_moment_role_open', '==', '1' ),
                ),
            )
        ));
        
         \CSF::createSection( 'qk_tax_circle', array(
            'title' => '圈子TAB选卡栏设置', 
            'fields' => array(
                array(
                    'id'          => 'qk_circle_tabbar_open',
                    'type'        => 'select',
                    'title'       => '圈子TAB选卡栏',
                    'subtitle'    => '您可以在这里单独给圈子设置选项栏目。',
                    'options'     => array(
                        'global'  => '使用全局设置',
                        1  => '自定义该选项栏',
                    ),
                    'default'     => 'global',
                    'desc'        => '如果使用全局设置，这里请选择全局设置，并且在主题设置->圈子社区->圈子圈子板块编辑全局权限'
                ),
                array(
                    'id'        => 'qk_circle_tabbar',
                    'type'      => 'group',
                    'title'     => '自定义筛选工具栏',
                    'subtitle'     => '根据这个工具可以实现无数种可能',
                    'button_title' => '新增栏目按钮',
                    'fields'    => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => 'Tab栏目名称',
                        ),
                        array(
                            'id'    => 'icon',
                            'type'  => 'icon',
                            'title' => 'Tab栏目图标',
                            'icon'  => '只会在作为左边导航时显示',
                        ),
                        array(
                            'id'         => 'tab_type',
                            'title'      => 'Tab栏目类型',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'all'   => '综合',
                            ),
                        ),
                        array(
                            'id'         => 'author__in',
                            'title'      => '筛选用户',
                            'subtitle'   => '筛选用户，一般情况下填写官方的成员id，也就是说筛选官方的帖子',
                            'type'       => 'select',
                            'placeholder' => '搜索用户',
                            'ajax'        => true,
                            'chosen'      => true, //开启这个框架报错
                            'multiple'    => true,
                            'sortable'    => true,
                            'options'     => 'users',
                        ),
                        array(
                            'id'         => 'topic',
                            'title'      => '筛选话题',
                            'subtitle'   => '在不选择话题则默认筛选全部，支持多选',
                            'type'       => 'select',
                            'placeholder' => '选择话题',
                            'ajax'        => true,
                             'chosen'      => true,
                            'multiple'    => true,
                            'sortable'    => true,
                            'options'     => 'categories',
                            'query_args'  => array(
                                'taxonomy'  => array('topic')
                            ),
                        ),
                        array(
                            'type'    => 'subheading',
                            'content' => '更加细化的筛选',
                        ),
                        array(
                            'id'      => 'best',
                            'type'    => 'radio',
                            'title'   => '精华',
                            'options'    => array(
                                '1'   => '开启',
                            ),
                        ),
                        array(
                            'id'         => 'file',
                            'title'      => '帖子文件类型',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'image'   => '图片',
                                'video'   => '视频',
                                'file'   => '文件',
                                'card '   => '文章卡',
                            ),
                        ),
                        array(
                            'id'         => 'type',
                            'title'      => '帖子类型(还未实现)',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'vote'   => '投票',
                                'ask'   => '问答',
                            ),
                        ),
                        array(
                            'id'         => 'orderby',
                            'title'      => '默认排序',
                            'type'       => 'select',
                            'inline'     => true,
                            'options'    => array(
                                'date'   => '默认时间',
                                'modified'   => '修改时间',
                                'weight'   => '权重',
                                'views'   => '浏览量',
                                'like'   => '点赞数量',
                                'comments'   => '评论数量',
                                'comment_date'   => '回复时间',
                                'random '   => '随机',
                            ),
                        ),
                        array(
                            'type'    => 'subheading',
                            'content' => '列表',
                        ),
                        array(
                            'id'         => 'list_style_type',
                            'title'      => '帖子列表风格样式',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'list-1'   => '常规',
                                'list-2'   => '简约',
                                'list-3'   => '瀑布流',
                            ),
                            'default' => 'list-1',
                        ),
                        array(
                            'id'         => 'video_play_type',
                            'title'      => '帖子列表视频播放方式',
                            'type'       => 'radio',
                            'inline'     => true,
                            'options'    => array(
                                'none'   => '不播放',
                                'click'   => '点击播放',
                                'scroll'   => '滚动播放',
                                'mouseover'   => '鼠标移入播放',
                            ),
                            'default' => 'click',
                            'desc'=>'注意：如果列表风格选择的是瀑布流，则视频滚动播放不会生效',
                            'dependency' => array(
                                array('list_style_type', '!=', 'list-2')
                            ),
                        ),
                    ),
                    'dependency' => array( 'qk_circle_tabbar_open', '==', '1' ),
                ),
                array(
                    'id'       => 'qk_circle_tabbar_index',
                    'type'     => 'spinner',
                    'title'    => '默认显示第几个栏目',
                    'subtitle' => '根据上面设置的工具栏目，选择合适的显示',
                    'max'      => 10,
                    'min'      => 0,
                    'step'     => 1,
                    'unit'     => '个',
                    'default'  => 0,
                    'desc'     => '从0开始计数，添0就是默认第一个',
                    'dependency' => array( 'qk_circle_tabbar_open', '==', '1' ),
                ),
                array(
                    'id'      => 'qk_circle_left_sidebar',
                    'type'    => 'switcher',
                    'title'   => '显示在左侧侧边栏',
                    'default' => false,
                    'dependency' => array( 'qk_circle_tabbar_open', '==', '1' ),
                ),
            )
        ));
    }
    
    public function save_circle_action($data, $term_id){
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';
        
        if(!empty($data['qk_circle_admin'])){
            
            $exists = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE circle_role = %s AND circle_id = %d", 'admin', $term_id),ARRAY_A);
            
            if(empty($exists) || $exists['user_id'] !== $data['qk_circle_admin']) {
            
                if(empty(Circle::is_user_joined_circle($data['qk_circle_admin'],$term_id))) {
                    $wpdb->insert(
                        $table_name, 
                        array(
                            'user_id'=>(int)$data['qk_circle_admin'],
                            'circle_id'=> $term_id,
                            'circle_role'=>'admin',
                            'join_date'=>current_time('mysql')
                        ),
                        array('%d', '%d','%s','%s',)
                    );
                }else{
                    $wpdb->update(
                        $table_name,
                        array(
                            'circle_role'=>'admin',
                        ),
                        array(
                            'user_id' => $data['qk_circle_admin'],
                            'circle_id' => $term_id
                        ),
                        array('%s'),
                        array('%d','%d')
                    );
                }
                
                if($exists['user_id'] !== $data['qk_circle_admin']){
                    $wpdb->update(
                        $table_name,
                        array(
                            'circle_role'=>'member',
                        ),
                        array(
                            'id' => $exists['id'],
                            'circle_id' => $term_id
                        ),
                        array('%s'),
                        array('%d','%d')
                    );
                }
            }
        }
        
        $res = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE circle_role = %s AND circle_id = %d", 'staff', $term_id),ARRAY_A);
        $staff = $data['qk_circle_staff'];
        $staff = !empty($staff) && is_array($staff) ? $staff : array();
        
        foreach ($staff as $value) {
            if (!in_array($value, array_column($res, 'user_id'))) {
                
                if(empty(Circle::is_user_joined_circle($value,$term_id))) {
                    // 插入新记录
                    $wpdb->insert(
                        $table_name,
                        array(
                            'circle_role' => 'staff',
                            'circle_id' => $term_id,
                            'user_id' => $value,
                            'join_date'=> current_time('mysql')
                        ),
                        array('%s', '%d','%d', '%s')
                    );
                }else {
                    $wpdb->update(
                        $table_name,
                        array(
                            'circle_role'=>'staff',
                        ),
                        array(
                            'user_id'=> (int)$value,
                            'circle_id'=> $term_id,
                        ),
                        array('%s'),
                        array('%d','%d')
                    );
                }
            }
        }
        
        $staff_to_delete = array_diff(array_column($res, 'user_id'), $staff);
        foreach ($staff_to_delete as $value) {
            $wpdb->update(
                $table_name,
                array(
                    'circle_role'=>'member',
                ),
                array(
                    'user_id'=> (int)$value,
                    'circle_id'=> $term_id,
                ),
                array('%s'),
                array('%d','%d')
            );
        }
    }
}