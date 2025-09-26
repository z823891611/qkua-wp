<?php namespace Qk\Modules\Settings;
use Qk\Modules\Common\User;

/**
 * 视频类型文章设置
 * 
 * */
class Video{
    
    public function init(){
        
        //过滤掉积分或余额变更原因
        add_filter('csf_single_video_metabox_save', function ($data){
            unset($data['qk_video_batch']);
            return $data;
        },10);
        
        //保存文章执行
        add_action('save_post', array($this,'save_episode_meta_box'),10,3);
        add_action('save_post', array($this,'save_video_meta_box'),99,3);
        
        //删除文章时执行的代码
        add_action('before_delete_post', array($this,'delete_episode_meta_box'));
        
        //添加导航
        add_action('admin_footer-edit.php', array($this,'qk_video_menu'));
        add_action('admin_footer-post.php', array($this,'qk_video_menu'));
        add_action('admin_footer-post-new.php', array($this,'qk_video_menu'));
        add_action('admin_footer-edit-tags.php', array($this,'qk_video_menu'));
        add_action('admin_footer-term.php', array($this,'qk_video_menu'));
        
        add_action( 'edit_form_top', array( $this, 'edit_form_top' ) );
        // add_action( 'edit_form_before_permalink', array( $this, 'edit_form_top' ) );
        // add_action( 'edit_form_after_title', array( $this, 'edit_form_top' ) );
        // add_action( 'edit_form_after_editor', array( $this, 'edit_form_top' ) );
        
        //video
        add_filter( 'manage_video_posts_columns', array($this,'filter_video_columns'));
        
        //episode
        add_filter( 'manage_episode_posts_columns', array($this,'filter_episode_columns'));
        add_action( 'manage_episode_posts_custom_column', array($this,'realestate_episode_column'), 10, 2);
        add_filter( 'pre_get_posts', array($this,'episode_posts_pre_query'),5);
        
        //注册视频类型文章设置
        $this->register_video_metabox();
        
        $this->register_episode_metabox();
    }
    
    public function filter_video_columns($columns){
        $new = array();
        $new['author'] = '作者';
        array_insert($columns,2,$new);
        
        return $columns;
    }
    
    public function filter_episode_columns($columns){
        $new = array();
        $new['video'] = '所属视频';
        array_insert($columns,2,$new);
        
        return $columns;
    }
    
    public function realestate_episode_column($column, $post_id){

        if ( $column === 'video' ) {
            $parent_id = get_post_field('post_parent', $post_id);

            if($parent_id){
                echo '<a class="row-title" href="' . add_query_arg('post_parent', $parent_id) . '">' . get_the_title($parent_id) . '</a>';
            }else{
                echo '无';
            }
            
            return;
        }
    }
    
    public function episode_posts_pre_query($wp_query){
        global $pagenow;
        if($pagenow == 'edit.php' && (isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'episode')){
            
            $post_id = $_REQUEST['post_parent'];
            if($post_id) {
                $wp_query->set( 'post_parent', $post_id);
            }
        }
    }

    public function edit_form_top() {
        $post_id = (int)$_GET['post']?:$_REQUEST['post_ID']?: 0;
        $post_type = get_post_type($post_id);
        
        if($post_type == 'video' && $post_id) {
            echo '<a href="'.esc_url(add_query_arg('post_parent',$post_id,admin_url( 'edit.php?post_type=episode' ))).'" class="button">查看当前视频的全部剧集 ></a>';
        }
                
        if($post_type == 'episode') {
            $parent_id = $_REQUEST['post_parent']?:wp_get_post_parent_id($post_id) ?:0;
            if($parent_id) {
                echo '<p>您正在为 <code>'.get_the_title($parent_id).'</code> 编辑或添加剧集</p>';
                echo '<a href="'.esc_url(add_query_arg('post',$parent_id,admin_url( 'post.php?action=edit' ))).'" class="button">< 返回查看父视频</a>';
                echo '<a href="'.add_query_arg('post_parent',$parent_id,admin_url( 'post-new.php?post_type=episode' )).'" class="button">继续添加新剧集 ></a>';
            }
        }
        
    }

    public function register_video_metabox(){
        //serialize
        // $meta = get_term_meta( 11, 'single_video_metabox', true );
        
        //unserialize
        //print_r(get_term_meta( 11, 'seo_title', true ));
        
        $prefix = 'single_video_metabox';
        
        //视频附加信息
        \CSF::createMetabox($prefix, array(
            'title'     => '视频',
            'post_type' => array('video'),
            'context'   => 'side', // The context within the screen where the boxes should display. `normal`, `side`, `advanced`
            'data_type' => 'serialize',
            'nav' => 'inline',
            'theme'     => 'light'
        ));
        
        $roles = User::get_user_roles();

        $roles_options = array();
        
        foreach ($roles as $key => $value) {
            $roles_options[$key] = $value['name'];
        }
        
        \CSF::createSection($prefix, array(
            //'title'  => '附加信息',
            'fields' => array(
                // array(
                //   'id' => 'qk_product_update_time',
                //   'type'    => 'text',
                //   'title' => '原名：',
                //   //'content' => '<p>'.$update_time.'</p>'
                // ),
                // array(
                //   'id' => 'qk_product_update_ime',
                //   'type'    => 'text',
                //   'title' => '状态：',
                //   'desc' => '说明是否是更新中，还是已完结',
                //   //'content' => '<p>'.$update_time.'</p>'
                // ),
                array(
                    'id'         => 'qk_video_role',
                    'type'       => 'radio',
                    'title'      => '视频观看权限',
                    'inline'     => true,
                    'options'    => array(
                        'free'   => '无限制(免费)',
                        'money'  => '支付费用观看',
                        'credit' => '支付积分观看',
                        'roles'  => '限制等级观看',
                        'comment'=> '评论观看',
                        'login'  => '登录观看',
                        'password'  => '输入密码观看',
                    ),
                    'default'    => 'free',
                ),
                array(
                    'id'         => 'qk_video_roles',
                    'type'       => 'checkbox',
                    'title'      => '允许免费查看的用户组',
                    'inline'     => true,
                    'options'    => $roles_options,
                    'desc'       => '（可多选）请选择允许指定免费查看视频的用户组',
                    'dependency'   => array(
                        array( 'qk_video_role', '==', 'roles' )
                    ),
                ),
                array(
                    'id'      => 'qk_video_not_login_buy',
                    'type'    => 'switcher',
                    'title'   => '开启未登录用户购买功能',
                    'desc'=> '未登录用户只能使用金钱支付，所有在设置权限是必须是 <code>付费</code>',
                    'default' => 0,
                    'dependency'   => array(
                        array( 'qk_video_role', '==', 'money' )
                    ),
                ),
                array(
                    'id'      => 'qk_video_pay_total',
                    'type'    => 'spinner',
                    'title'   => '支付的总费用',
                    'default' => 0,
                    'desc'=> '用于一次购买全部的费用',
                    'dependency'   => array(
                        array( 'qk_video_role', 'any', 'money,credit' )
                    ),
                ),
                array(
                    'id'      => 'qk_video_pay_value',
                    'type'    => 'spinner',
                    'title'   => '支付的单集费用',
                    'default' => 0,
                    'desc'=> '支持每集单独购买',
                    'dependency'   => array(
                        array( 'qk_video_role', 'any', 'money,credit' )
                    ),
                ),
                array(
                    'id'      => 'qk_video_auto_play',
                    'type'    => 'switcher',
                    'title'   => '视频自动播放',
                    'default' => 0,
                    'desc'=> '如果设置自动播放，打开页面以后视频会自动播放（因为浏览器的特殊配置，不能保证所有环境都能自动播放）',
                ),
                array(
                    'id'      => 'qk_video_auto_play_next',
                    'type'    => 'switcher',
                    'title'   => '视频自动播放下一集',
                    'default' => 0,
                    'desc'=> '如果存在多集，会在当前视频播放完成后会自动播放下一集',
                ),
                
            )
        ));
        
        //批量导入
        \CSF::createSection($prefix, array(
            //'title'  => '视频剧集',
            'fields' => array(
                array(
                    'id'      => 'qk_video_batch',
                    'type'    => 'textarea',
                    'title'   => '视频批量导入',
                    'desc'    => sprintf('格式为%s，每组占一行。%s比如：%s%s','<code>视频地址|标题名称|本地视频预览地址|文章内容</code>','<br>','<br><code>https://xxx.xxx.com/xxxx.mp4|第一课：学习Css选择器|https://xxx.xxx.com/xxxx.png|讲解了创建数组，数组转成字符串，数组排序，数组拼接</code>','<br>')
                ),
            )
        ));
        
        \CSF::createSection($prefix, array(
            //'title'  => '视频剧集',
            'fields' => $this->get_episodes()
        ));
    }
    
    //剧集
    public function register_episode_metabox(){
        $prefix = 'single_episode_metabox';
        
        //视频附加信息
        \CSF::createMetabox($prefix, array(
            'title'     => '视频剧集',
            'post_type' => array('episode'),
            'context'   => 'normal', // The context within the screen where the boxes should display. `normal`, `side`, `advanced`
            'data_type' => 'serialize',
            'theme'     => 'light'
        ));
        
        \CSF::createSection($prefix, array(
            'fields' => array(
                array(
                    'id'          => 'post_parent',
                    'type'        => 'select',
                    'title'       => '父视频',
                    'placeholder' => '选择父视频',
                    'chosen'      => true,
                    'ajax'        => true,
                    'options'     => 'posts',
                    'query_args'  => array(
                        'post_type' => 'video'
                    ),
                    'default' => (int)$_GET['post_parent']?: 0,
                    'settings' => array(
                        'min_length' => 1
                    )
                ),
                array(
                    'id'     => 'video',
                    'type'   => 'fieldset',
                    'title'  => '添加视频',
                    'sanitize' => false,
                    'fields' => array(
                        array(
                            'type'    => 'subheading',
                            'content' => '视频地址',
                        ),
                        array(
                            'id'          => 'preview_url',
                            'type'        => 'upload',
                            'title'       => '预览地址',
                            'preview'     => true,
                            'library'     => 'video',
                            'placeholder' => '选择预览视频或填写预览视频地址',
                        ),
                        array(
                            'id'          => 'url',
                            'type'        => 'upload',
                            'title'       => '视频地址',
                            'preview'     => true,
                            'library'     => 'video',
                            'placeholder' => '选择视频或填写视频地址',
                        ),
                    ),
                ),
            )
        ));
    }
    
    //获取所有剧集
    public function get_episodes(){
        $post_id = (int)$_GET['post']?:$_REQUEST['post_ID']?: 0;

        if(!$post_id) {
            return array(
                array(
                   'type'    => 'content',
                   'title' => '',
                   'content' => '<div style=" text-align: center; padding: 30px 15px;"><span class="dashicons dashicons-warning"></span> 请先发布后，刷新页面添加剧集</div>'
                )
            );
        }
        
        $args = array(
            'post_status'=>'publish',
            'post_type'=>'episode',
            'post_parent'=> $post_id,
            'posts_per_page' => -1,
        );
        
        $the_query = new \WP_Query( $args );

        $post_data = array();
        $_count = 0;
        
        $fields = array(
            'id'        => 'group',
            'type'      => 'group',
            'title'     => '剧集',
            'accordion_title_number' => true,
            'sanitize' => false,
            'fields'    => array(
                array(
                    'id'    => 'title',
                    'type'  => 'text',
                    'title' => '剧集标题',
                    'attributes' => array(
                        'readonly' => 'readonly',
                    ),
                    'dependency' => array('type', '==', 'episode'),
                ),
                array(
                    'id'    => 'id',
                    'type'  => 'text',
                    'title' => '剧集id',
                    'class' => 'qk—post—parent—id',
                    'attributes' => array(
                        'readonly' => 'readonly',
                    ),
                    'dependency' => array('type', '==', 'episode'),
                ),
                array(
                    'id'    => 'thumb',
                    'type'  => 'upload',
                    'title' => '缩略图',
                    'preview' => true,
                    'library' => 'image',
                    'attributes' => array(
                        'readonly' => 'readonly',
                    ),
                    'dependency' => array('type', '==', 'episode'),
                ),
                array(
                    'id'    => 'url',
                    'type'  => 'text',
                    'title' => '剧集地址',
                    'attributes' => array(
                        'readonly' => 'readonly',
                        'style'       => 'width: 100% ;',
                    ),
                    'sanitize' => false,
                    'dependency' => array('type', '==', 'episode'),
                ),
                array(
                    'id'    => 'preview_url',
                    'type'  => 'text',
                    'title' => '剧集预览地址',
                    'attributes' => array(
                        'readonly' => 'readonly',
                        'style'       => 'width: 100% ;',
                    ),
                    'sanitize' => false,
                    'dependency' => array('type', '==', 'episode'),
                ),
                array(
                    'id'         => 'type',
                    'type'       => 'button_set',
                    'title'      => '类型',
                    'options'    => array(
                        'episode'  => '剧集',
                        'chapter' => '章节',
                    ),
                    'default'    => 'episode'
                ),
                array(
                    'id'    => 'chapter_title',
                    'type'  => 'text',
                    'title' => '章节标题',
                    'dependency' => array('type', '==', 'chapter'),
                ),
                array(
                    'id'    => 'chapter_desc',
                    'type'  => 'text',
                    'title' => '章节介绍',
                    'dependency' => array('type', '==', 'chapter'),
                ),
                array(
                    'type'  => 'content',
                    'title' => '',
                    'class' => 'qk—edit-episode',
                    'content' => '<a href="'.esc_url(add_query_arg('post_parent',$post_id,admin_url( 'post.php?action=edit' ))).'" class="button button-primary">编辑当前剧集</a>',
                    'dependency' => array('type', '==', 'episode'),
                ),
            ),
        );
        
        if ( $the_query->have_posts() ) {
            
            $_count = $the_query->found_posts;
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                
                $_post_id = get_the_id();
                $meta = get_post_meta($_post_id, 'single_episode_metabox', true );
                $episodes = !empty($meta['video'])? $meta['video'] :array();

                $fields['default'][] = array(
                    'id' => $_post_id,
                    'title' => get_the_title(),
                    'url' => $episodes['url'],
                    'preview_url' => $episodes['preview_url'],
                );
            }
            
            wp_reset_postdata();
        }
        
        return array($fields);
    }
    
    public function save_video_meta_box ( $post_id, $post, $update ) {
        // 排除自动保存和修订版本
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // 只在文章发布时执行 如果文章状态不是“publish”，则不执行操作
        if($post->post_status !== 'publish'){
            return;
        }

        // 只在文章类型为 video 时执行
        if($post->post_type !== 'video'){
            return;
        }
        
        $video_meta = isset($_POST['single_video_metabox']) ? $_POST['single_video_metabox'] : array();
        $video_batch = isset($video_meta['qk_video_batch']) && !empty($video_meta['qk_video_batch']) ? $video_meta['qk_video_batch'] :'';
        
        if($video_batch) {
            $child_posts = explode(PHP_EOL, trim($video_batch, " \t\n\r") );
            
            $video_meta = get_post_meta($post_id, 'single_video_metabox', true );
            $video_meta = !empty($video_meta) && is_array($video_meta) ? $video_meta : array();
            $video_meta['group'] = !empty($video_meta['group']) && is_array($video_meta['group']) ? $video_meta['group'] : array();
            
            $i = count($video_meta['group']);
            
            foreach ($child_posts as $key => $child_post) {
                $data = explode("|", trim($child_post, " \t\n\r"));
                $i++;
                $video_url = isset($data[0]) && !empty($data[0]) ? $data[0] : '';
                $child_title = isset($data[1]) && !empty($data[1]) ? $data[1] : '第'.$i. '集';
                $preview_url = isset($data[2]) && !empty($data[2]) ? $data[2] : '';
                $post_content = isset($data[3]) && !empty($data[3]) ? $data[3] : '';
                //$post_content = isset($data[4]) && !empty($data[4]) ? $data[4] : '';
                
                // 判断是否为有效的视频地址
                if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
                    continue;
                }
    
                $child_post_args = array(
                    'post_title' => $child_title,
                    'post_content' => $post_content,
                    'post_type' => 'episode',
                    'post_status' => 'publish',
                    'post_parent' => $post_id,
                );
                
                $child_post_id = wp_insert_post($child_post_args);
                
                if($child_post_id) {
                    // if ($child_post_id && $child_post_thumbnail) {
                    //     set_post_thumbnail($child_post_id, $child_post_thumbnail);
                    // }
                    
                    $episode_metabox = array(
                        'video' => array(
                            'preview_url' => $preview_url,
                            'url' => $video_url
                        ),
                        'post_parent' => $post_id
                    );
                    
                    update_post_meta($child_post_id,'single_episode_metabox', $episode_metabox );
                    
                    //优化seo标题
                    update_post_meta($child_post_id,'qk_seo_title', $post->post_title.':'.$child_title );
                    
                    //添加新的
                    $episode_video = $episode_metabox['video'];
                    $episode_video['id'] = $child_post_id;
                    $episode_video['title'] = $child_title;
                    $episode_video['type'] = 'episode';
                    
                    //$episode_video['thumb'] = $child_post_thumbnail;
                    
                    $video_meta['group'][] = $episode_video;
                    
                    update_post_meta($post_id,'single_video_metabox', $video_meta );
                }
                
            }

        }
    }
    
    //保存自定义字段的值
    public function save_episode_meta_box( $post_id, $post, $update ) {
        
        // 排除自动保存和修订版本
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        
        // 只在文章发布时执行 如果文章状态不是“publish”，则不执行操作
        if($post->post_status !== 'publish'){
            return;
        }

        // 只在文章类型为 post 时执行
        if($post->post_type !== 'episode'){
            return;
        }

        $episode_meta = isset($_POST['single_episode_metabox']) ? $_POST['single_episode_metabox'] : array();
        
        if (isset($episode_meta['post_parent'])) {
            $post_parent = sanitize_text_field($episode_meta['post_parent']); // 对输入的值进行清理
            //如果父视频的类型不为video
            if(get_post_type($post_parent) !== 'video' && $post_parent) {
                return;
            }
            
            $current_parent = get_post_field('post_parent', $post_id); // 获取当前文章的post_parent值
            if ($post_parent != $current_parent) { // 只有当新值与当前值不同时才进行更新
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_parent' => (int)$post_parent // 更新post_parent值
                ));
                
                //删除原有的
                $current_video_meta = get_post_meta($current_parent, 'single_video_metabox', true );
                $current_video_meta = !empty($current_video_meta) && is_array($current_video_meta) ? $current_video_meta : array();
                
                if($current_video_meta) {
                    $current_key = array_search($post_id, array_column($current_video_meta['group'], 'id'));
                    if($current_key !== false) {
                        unset($current_video_meta['group'][$current_key]);
                        update_post_meta($current_parent,'single_video_metabox', $current_video_meta );
                    }
                }
            }
            
            //添加新的
            $video_meta = get_post_meta($post_parent, 'single_video_metabox', true );
            $video_meta = !empty($video_meta) && is_array($video_meta) ? $video_meta : array();
            
            if(!$video_meta) {
                return;
            }
            
            $key = array_search($post_id, array_column($video_meta['group'], 'id'));
            
            $episode_video = isset($episode_meta['video']) ? $episode_meta['video'] :  array();
            $episode_video['id'] = $post_id;
            $episode_video['title'] = $post->post_title;
            $episode_video['type'] = 'episode';
            
            $thumb_id = get_post_thumbnail_id($post_id);
            $thumb_url = wp_get_attachment_image_src($thumb_id,'full');
            
            $episode_video['thumb'] = $thumb_url[0]?:'';
            
            if($key !== false) {
                $video_meta['group'][$key] = $episode_video;
            }else {
                $video_meta['group'][] = $episode_video;
            }
            
            update_post_meta($post_parent,'single_video_metabox', $video_meta );
        }
    }
    
    // 删除文章时执行的代码
    public function delete_episode_meta_box($post_id) {
        if (get_post_type($post_id) === 'episode') {
            
            $parent_id = get_post_field('post_parent', $post_id);
            
            if(!$parent_id) return;
            
            $video_meta = get_post_meta($parent_id, 'single_video_metabox', true );
            $video_meta = !empty($video_meta) && is_array($video_meta) ? $video_meta : array();
            
            if($video_meta) {
                $key = array_search($post_id, array_column($video_meta['group'], 'id'));
                if($key !== false) {
                    unset($video_meta['group'][$key]);
                    update_post_meta($parent_id,'single_video_metabox', $video_meta );
                }
            }
        }
    }
    
    //添加导航
    public function qk_video_menu() {
        global $pagenow,$current_screen;;
        
        if (
            in_array($pagenow, array('edit.php'))
            || in_array($pagenow, array('post-new.php'))
            || in_array($pagenow, array('post.php' ))
            || isset($_GET['taxonomy'])
            || in_array($pagenow, array('edit.php')) 
            && isset($_REQUEST['post_type'])
        ) {
            
            if(
                isset($_REQUEST['post_type'])
                && in_array($_REQUEST['post_type'], array('video','episode'))
                || isset($current_screen->post_type)
                && in_array($current_screen->post_type, array('video','episode'))
                || isset($_GET['post']) 
                && in_array(get_post_type($_GET['post']), array('video','episode'))
            ) {
                
                $post_id = (int)$_GET['post'] ?:(int)$_GET['post_parent']?: 0;
                $post_type = (int)$_GET['post_type'] ?: get_post_type($post_id);
                
                if($post_type == 'episode' && $post_id) {
                    //获取给定文章的父级文章的 ID
                    $post_id = wp_get_post_parent_id() ?:0;
                }

                $current1a = in_array( $pagenow, array('edit.php')) && $_REQUEST['post_type'] == 'video' ? ' class="current"' : '';
                $current1b = in_array( $pagenow, array('post-new.php')) && $_REQUEST['post_type'] == 'video' ? ' class="current"' : '';
                $current1c = $_GET['taxonomy']=='video_cat' ? ' class="current"' : '';
                $current1d = $_GET['taxonomy']=='video_season' ? ' class="current"' : '';
                $current1e = in_array( $pagenow, array('edit.php')) && $_REQUEST['post_type'] == 'episode' ? ' class="current"' : '';
                $current1f = in_array( $pagenow, array('post-new.php')) && $_REQUEST['post_type'] == 'episode' ? ' class="current"' : '';
                
                echo'
                    <ul class="MnTpAdn filter-links" id="tr-grabber-menu" style="display: none;">
                        <li><a'.$current1a.' href="'.admin_url( 'edit.php?post_type=video' ).'">全部视频</a></li>
                        <li><a'.$current1b.' href="'.admin_url( 'post-new.php?post_type=video' ).'">添加视频</a></li>
                        <li><a'.$current1c.' href="'.admin_url( 'edit-tags.php?taxonomy=video_cat&post_type=video' ).'">分类</a></li>
                        <li'.$current1d.'><a href="'.add_query_arg('post_parent',$post_id,admin_url( 'edit-tags.php?taxonomy=video_season&post_type=video' )).'">视频系列</a></li>
                        <li><a'.$current1e.' href="'.add_query_arg('post_parent',$post_id,admin_url( 'edit.php?post_type=episode' )).'">全部剧集</a></li>
                        <li><a'.$current1f.' href="'.add_query_arg('post_parent',$post_id,admin_url( 'post-new.php?post_type=episode' )).'">添加剧集</a></li>
                    </ul>
                    ';
            }
        } 
    }
}