<?php namespace Qk\Modules\Common;

/**
 * 优化类
 *
 * @package Optimize
 */
class Optimize {

    public function init() {
        $optimize = qk_get_option('qk_system_optimize');
        
        if(!$optimize) return;
        
        // 移除多余的meta标签
        if($optimize['optimize_remove_meta_tags']) {
            add_action( 'after_setup_theme', array( $this, 'remove_meta_tags' ) );
        }
        
        // 禁用embed功能
        if($optimize['optimize_disable_embeds']) {
            add_action( 'init', array( $this, 'disable_embeds' ) );
        }
        
        //禁用响应式图片属性
        //add_filter( 'wp_calculate_image_srcset', '__return_false' );
        add_filter( 'use_default_gallery_style', '__return_false' );
        
        // 禁用缩放尺寸 禁用WordPress大图像自动缩放功能
        add_filter( 'big_image_size_threshold', '__return_false' );
        
        //禁用word press全局全局CSS样式
        if($optimize['optimize_remove_styles']) {
            add_action('wp_enqueue_scripts', array( $this,'remove_wordpress_styles') );
        }
        
        // 禁用全局样式表
        add_action( 'init', array( $this, 'disable_global_styles' ) );
        // 禁用获取全局样式表的URL
        add_action( 'init', array( $this, 'disable_global_stylesheet' ) );
        
        remove_action( 'pre_post_update', 'wp_save_post_auto_draft' );
        
        // 移除多余的图片尺寸 禁用自动生成的图片尺寸
        if($optimize['optimize_disable_image_sizes']) {
            add_action( 'intermediate_image_sizes_advanced', array( $this, 'disable_image_sizes' ) );
        }
        
        // 移除菜单多余的CLASS和ID沉余
        if($optimize['optimize_remove_menu_class']) {
            add_filter( 'nav_menu_css_class', array( $this, 'remove_menu_classes' ), 99, 1 );
            add_filter( 'nav_menu_item_id', array( $this, 'remove_menu_classes' ), 99, 1 );
            add_filter( 'page_css_class', array( $this, 'remove_menu_classes' ), 99, 1 );
        }
        
        // 移除WordPress自带的Emoji表情支持
        if($optimize['optimize_disable_emoji']) {
            add_action( 'init', array( $this, 'disable_wp_emoji' ) );
        }
        
        // 移除WordPress自带的Pingback功能
        if($optimize['optimize_disable_pingback']) {
            add_action( 'pre_ping', array( $this, 'disable_pingback' ) );
        }
        
        // 禁用文章自动保存 自动草稿 auto-draft
        if($optimize['optimize_disable_autosave']) {
            add_action( 'wp_print_scripts', array( $this, 'disable_autosave' ) );
            add_action( 'admin_init', array( $this, 'disable_auto_drafts' ) );
        }
        
        // 关闭文章编辑锁定功能
        // add_filter('wp_check_post_lock_window', '__return_false');
        // // 关闭文章编辑者跟踪功能
        // add_filter('edit_post_metadata', '__return_false', 10, 4);
        
        // 禁用文章修订版本
        if($optimize['optimize_disable_revisions']) {
            add_filter( 'wp_revisions_to_keep', array( $this, 'disable_revisions' ), 10, 2 );
        }
        
        // 禁用 WordPress 自动更新
        if($optimize['optimize_disable_wp_update']) {
            add_filter( 'auto_update_core', '__return_false' ); //禁止 WordPress 核心更新
            add_filter( 'auto_update_plugin', '__return_false' ); //禁止插件更新
            add_filter( 'auto_update_theme', '__return_false' ); //禁止主题更新
        }
        
        // 禁用WordPress自带的XML-RPC接口
        if($optimize['optimize_disable_xmlrpc']) {
            add_filter( 'xmlrpc_enabled', '__return_false' );
        }
        
        // 禁用WordPress自带的REST API 移除 wp-json
        add_filter( 'rest_enabled', '__return_false' );
        add_filter( 'rest_jsonp_enabled', '__return_false' );
        
        // 自定义 WordPress wp-json 路径
        add_filter( 'rest_url_prefix', function() {
            return 'wp-json';
        });
        
        //禁用工具条删除WP工具栏 show_admin_bar( false );
        if($optimize['optimize_disable_admin_bar']) {
            add_filter( 'show_admin_bar', '__return_false' );
        }
        
        // 禁用 Gutenberg 编辑器 和 移除 Gutenberg 编辑器相关的 CSS 和 JavaScript 文件
        if($optimize['optimize_disable_gutenberg']) {
            add_filter('use_block_editor_for_post', '__return_false');
            remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
        }
        
        // 禁用 Gutenberg 编辑器 和 经典编辑器 中的小工具区块编辑器
        if($optimize['optimize_disable_widgets_block']) {
            add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
            add_filter( 'use_widgets_block_editor', '__return_false' );
        }
        
        //禁用WordPress中的Open Sans字体。
        if($optimize['optimize_disable_open_sans']) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_open_sans' ) );
        }
        
        //限制非管理员访问 WordPress 后台。
        add_action( 'admin_init', array( $this, 'restrict_admin_access' ), 1 );
        
        //禁用RSS订阅
        if($optimize['optimize_disable_rss']) {
            add_action('do_feed', array( $this, 'disable_feed' ), 1);
            add_action('do_feed_rdf', array( $this, 'disable_feed' ), 1);
            add_action('do_feed_rss', array( $this, 'disable_feed' ), 1);
            add_action('do_feed_rss2', array( $this, 'disable_feed' ), 1);
            add_action('do_feed_atom', array( $this, 'disable_feed' ), 1);
            add_action('do_feed_rss2_comments', array( $this, 'disable_feed' ), 1);
            add_action('do_feed_atom_comments', array( $this, 'disable_feed' ), 1);
        }
        
        //禁用日期归档
        add_action('template_redirect', array( $this, 'disable_date_archives'));
    }

    /**
     * 移除多余的meta标签
     */
    public function remove_meta_tags() {
        remove_action( 'wp_head', 'wp_generator' ); // 移除WordPress版本号
        remove_action( 'wp_head', 'rsd_link' ); // 移除Really Simple Discovery链接
        remove_action( 'wp_head', 'wlwmanifest_link' ); // 移除Windows Live Writer链接
        remove_action( 'wp_head', 'wp_shortlink_wp_head',10); // 移除短链接
        remove_action( 'wp_head', 'feed_links', 2 ); // 移除文章和评论的Feed链接
        remove_action( 'wp_head', 'feed_links_extra', 3 ); // 移除其他Feed链接
        remove_action( 'wp_head', 'rest_output_link_wp_head', 10 ); // 移除REST API链接
        remove_action( 'wp_head', 'wp_resource_hints', 2 ); // 移除DNS预取链接
        
        // 移除 WordPress 的短链接功能
        remove_action( 'template_redirect', 'wp_shortlink_header', 11);
        
        // 移除 WordPress 中与 SEO 无关的头部链接
        remove_action( 'wp_head', 'index_rel_link', 10, 1);
        remove_action( 'wp_head', 'start_post_rel_link', 10, 1);
        remove_action( 'wp_head', 'parent_post_rel_link', 10, 0);
        remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0);
        remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
        remove_action( 'wp_head', 'rel_canonical', 10, 0);
    }

    /**
     * 禁用embed功能
     */
    public function disable_embeds() {
        /* @var WP $wp */
        global $wp;
        // Remove the embed query var.
        $wp->public_query_vars = array_diff( $wp->public_query_vars, array(
        'embed',
        ) );
        // Remove the REST API endpoint.
        remove_action( 'rest_api_init', 'wp_oembed_register_route' );
        // Turn off
        add_filter( 'embed_oembed_discover', '__return_false' );
        // Don't filter oEmbed results.
        remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
        // Remove oEmbed discovery links.
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
        // Remove oEmbed-specific JavaScript from the front-end and back-end.
        remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        add_filter( 'tiny_mce_plugins', array( $this, 'disable_embeds_tiny_mce_plugin' ) );
        // Remove all embeds rewrite rules.
        add_filter( 'rewrite_rules_array', array( $this, 'disable_embeds_rewrites' ) );
        remove_action( 'template_redirect', 'wp_old_slug_redirect' );
    }

    /**
     * 禁用embed TinyMCE插件
     *
     * @param array $plugins TinyMCE插件列表.
     * @return array 修改后的列表.
     */
    public function disable_embeds_tiny_mce_plugin( $plugins ) {
        return array_diff( $plugins, array( 'wpembed' ) );
    }

    /**
     * 禁用embed重写规则
     *
     * @param array $rules 重写规则数组.
     * @return array 修改后的规则数组.
     */
    public function disable_embeds_rewrites( $rules ) {
        foreach ( $rules as $rule => $rewrite ) {
            if ( false !== strpos( $rewrite, 'embed=true' ) ) {
                unset( $rules[ $rule ] );
            }
        }

        return $rules;
    }
    
    /**
     * 在插件激活时移除嵌入式内容的重写规则。
     *
     */
    public static function disable_embeds_remove_rewrite_rules() {
        add_filter( 'rewrite_rules_array', array( $this, 'disable_embeds_rewrites' ) );
        flush_rewrite_rules();
    }
    
    /**
     * 在插件停用时刷新重写规则。
     * 
     */
    public static function disable_embeds_flush_rewrite_rules() {
        remove_filter( 'rewrite_rules_array', array( $this, 'disable_embeds_rewrites' ) );
        flush_rewrite_rules();
    }
    
    /**
     * 移除多余的图片尺寸
     * 禁用自动生成的图片尺寸
     */
    public function disable_image_sizes($sizes) {
        unset($sizes['thumbnail']);    // 禁用通过set_post_thumbnail_size()添加的缩略图尺寸
        unset($sizes['medium']);       // 禁用medium尺寸
        unset($sizes['large']);        // 禁用large尺寸
        unset($sizes['medium_large']); // 禁用medium-large尺寸
        unset($sizes['1536x1536']);    // 禁用2x medium-large尺寸
        unset($sizes['2048x2048']);    // 禁用2x large尺寸
        
        return $sizes;
    }
    
    //禁用word press全局全局CSS样式
    public function remove_wordpress_styles(){
        wp_deregister_style( 'global-styles' );
        wp_dequeue_style( 'global-styles' );
        
        wp_deregister_style( 'wp-block-library' ); // 禁用Gutenberg样式表
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-block-style' );
        
        wp_deregister_style('classic-theme-styles');
        wp_dequeue_style('classic-theme-styles');
    }

    /**
     * 禁用全局样式表 不禁用会增加页面查询 拖慢网站速度
     */
    public function disable_global_styles() {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    }

    /**
     * 禁用获取全局样式表的URL 不禁用会增加页面查询 拖慢网站速度
     */
    public function disable_global_stylesheet() {
        remove_filter( 'stylesheet_uri', 'wp_get_global_stylesheet' );
    }

    /**
     * 移除菜单多余的CLASS和ID沉余 https://www.cnblogs.com/lanne/p/15513163.html
     *
     * @param array $classes 菜单项的CSS类数组.
     * @return array 修改后的CSS类数组.
     */
    public function remove_menu_classes( $classes ) {
        return is_array( $classes ) ? array_filter(
            $classes,
            function( $class ) {
                // 保留 current-menu-item 这个 class
                if ( $class === 'current-menu-item' ) {
                    return true;
                }
                return ( false === strpos( $class, 'menu' ) && false === strpos( $class, 'page' ) );
            }
        ) : '';
        
        //return is_array($classes) ? array_intersect($classes, array('current-menu-item','current-post-ancestor','current-menu-ancestor','current-menu-parent')) : ''; //删除当前菜单的四个选择器
    }

    /**
     * 移除WordPress自带的Emoji表情支持
     */
    public function disable_wp_emoji() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'tiny_mce_plugins', array( $this, 'disable_emoji_tinymce' ) );
    }

    /**
     * 禁用Emoji TinyMCE插件
     *
     * @param array $plugins TinyMCE插件列表.
     * @return array 修改后的列表.
     */
    public function disable_emoji_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
            return array_diff( $plugins, array( 'wpemoji' ) );
        } else {
            return array();
        }
    }

    /**
     * 移除WordPress自带的Pingback功能
     *
     * @param array $links 链接数组.
     * @return array 修改后的链接数组.
     */
    public function disable_pingback( $links ) {
        foreach ( $links as $link ) {
            if ( false !== strpos( $link, 'xmlrpc' ) ) {
                $link = '';
            }
        }

        return $links;
    }

    /**
     * 禁用文章自动保存
     */
    public function disable_autosave() {
        wp_deregister_script( 'autosave' );
    }
    
    /**
     * 禁用文章自动草稿
     */
    public function disable_auto_drafts() {
        // global $pagenow;
        // if ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'post' ) {
            remove_action( 'pre_post_update', 'wp_save_post_auto_draft' );
        // }
    }
    
    /**
     * 禁用文章修订版本
     */
    public function disable_revisions( $num, $post ) {
        return 0; 
    }
    
    /**
     * 移除后台谷歌字体
     */
    public function disable_open_sans() {
        wp_deregister_style( 'open-sans' );
        wp_register_style( 'open-sans', false );
        wp_enqueue_style('open-sans','');
    }
    
    /**
     * 限制非管理员访问 WordPress 后台。
     */
    public function restrict_admin_access() {
        if ( ! current_user_can( 'manage_options' ) && '/wp-admin/admin-ajax.php' != $_SERVER['PHP_SELF'] ) {
            wp_redirect( home_url() );
            exit;
        }
    }
    
    /**
     * 禁用RSS订阅
     */
    public function disable_feed() {
        if ( is_ssl() ) {
            wp_die( __('RSS feed is disabled.') );
        } else {
            wp_redirect( home_url() );
            exit;
        }
    }
    
    /**
     * 禁用日期归档页面
     */
    public function disable_date_archives() {
        if (is_date() || is_day()) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
        }
    }
}