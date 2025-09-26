<?php namespace Qk\Modules\Common;

class Seo{
    
    public function init(){
       
        add_action('wp_head',array($this,'seo_head_meta'),5);
       
       //更改页面连接符
        add_filter( 'document_title_separator', array($this,'document_title_separator'), 10, 1 );
        add_filter( 'document_title_parts', array($this,'custom_page_document_title'));
        
        if(qk_get_option('remove_category_tag')){
            add_filter('request',array($this,'remove_category'));
            add_filter('pre_term_link',array($this,'pre_term_link'),10,2);
        }
    }
    
    public function custom_page_document_title($title){
        
        if(is_singular()){
            global $post;
            $custom_title = get_post_meta($post->ID, 'qk_seo_title',true);

            if($custom_title){ 
                $title["title"] = esc_attr($custom_title); 
            }
        }elseif(is_archive()){
            $term_id = get_queried_object_id();
            if($term_id){
                $custom_title = get_term_meta($term_id,'seo_title',true);
                if($custom_title){ 
                    $title["title"] = esc_attr($custom_title); 
                }
            }else{
                //$title["title"] = '';
            }
            
        }

        return $title;
    }
    
    public function seo_head_meta(){

        $name = get_bloginfo('name');

        global $wp;
        $current_url = home_url( add_query_arg( array(), $wp->request ) );
        
        //自定义头部代码
        $head = qk_get_option('header_code');
        
        if($head){
            echo $head;
        }

        echo '
    <meta property="og:locale" content="'.get_locale().'" />
    <meta property="og:type" content="article" />
    <meta property="og:site_name" content="'.$name.'" />
    <meta property="og:title" content="'.wp_get_document_title().'" />
    <meta property="og:url" content="'.$current_url.'" />
    ';

        if(is_home() || is_front_page()){
           

            $meta = self::home_meta();
    
            echo '<meta name="keywords" content="'.$meta['keywords'].'" />
    <meta name="description" content="'.$meta['description'].'" />
    <meta property="og:image" content="'.$meta['image'].'" />
    ';
        }
        
        elseif(is_post_type_archive('circle')){
            echo '
    <meta name="keywords" content="'.qk_get_option('circle_keywords').'" />
    <meta name="description" content="'.qk_get_option('circle_description').'" />
            ';
        }
        
        //判断是否时文章页面
        elseif(is_singular()){
            
            $meta = self::single_meta();

            echo '<meta name="keywords" content="'.$meta['keywords'].'" />
    <meta name="description" content="'.$meta['description'].'" />
    <meta property="og:image" content="'.$meta['image'].'" />
    <meta property="og:updated_time" content="'.$meta['updated_time'].'" />
    <meta property="article:author" content="'.$meta['author'].'" />
    ';

        }
        
        elseif(is_archive()){
            $term = get_queried_object();
            
            if(isset($term->term_id)){
                $img = get_term_meta($term->term_id,'qk_tax_img',true);
                
                $seo_keywords = get_term_meta($term->term_id,'seo_keywords',true);
                
                
                echo '
    <meta name="keywords" content="'.esc_attr($seo_keywords).'" />
    <meta name="description" content="'.trim(strip_tags(get_the_archive_description())).'" />
    <meta property="og:image" content="'.qk_get_thumb(array('url'=>$img,'width'=>600,'height'=>400)).'" />
                ';
            }

        }
    }
    
    //更改页面标题连接符
    function document_title_separator( $sep ) {
        return qk_get_option('separator');
    }
    
    
    //分类目录是否去掉category标签
    public function remove_category($query_vars){
        if(!isset($_GET['page_id']) && !isset($_GET['pagename']) && !empty($query_vars['pagename'])){
            
            $pagename	= $query_vars['pagename'];
            if(strpos($pagename,'/') !== false){
                $pagename = explode('/',$pagename);
                $pagename = end($pagename);
            }
            $categories	= get_categories(['hide_empty'=>false]);
            $categories	= wp_list_pluck($categories, 'slug');
    
            if(in_array($pagename, $categories)){
                $query_vars['category_name'] = $pagename;
                unset($query_vars['pagename']);
            }
        }

        if(!isset($_GET['page_id']) && !isset($_GET['name']) && !empty($query_vars['name'])){
            $pagename	= $query_vars['name'];
            if(strpos($pagename,'/') !== false){
                $pagename = explode('/',$pagename);
                $pagename = end($pagename);
            }
            $categories	= get_categories(['hide_empty'=>false]);
            $categories	= wp_list_pluck($categories, 'slug');
    
            if(in_array($pagename, $categories)){
                $query_vars['category_name'] = $pagename;
                unset($query_vars['name']);
            }
        }

        unset($categories);
        return $query_vars;
    }

    public function pre_term_link($term_link, $term){

        if($term->taxonomy === 'category'){
            return '%category%';
        }
    
        return $term_link;
    }
    
    public static function home_meta(){
        
        $options = qk_get_option();
        
        return array(
            'keywords'    => $options['home_keywords'],
            'description' => $options['home_description'] ? $options['home_description'] : get_bloginfo('blogdescription'),
            'title'       => get_bloginfo('name'),
            'image'       => $options['img_logo']
        );
    }
    
    public static function single_meta($post_id = 0){
        if(!$post_id){
            global $post;
            $post_id = $post->ID;
        }
        
        $author = esc_attr(get_post_field('post_author',$post_id));
        $title = get_the_title($post_id);

        $thumb_url = Post::get_post_thumb($post_id);

        $desc = get_post_meta($post_id,'qk_seo_description',true);

        $desc = $desc ? $desc : qk_get_desc($post_id,100);

        $key = get_post_meta($post_id,'qk_seo_keywords',true);
        if(!$key){
            $key = wp_get_post_tags($post_id);
            $key = array_column($key, 'name');
            $key = implode(',',$key);
        }

        return array(
            'id'=>$post_id,
            'title'=>wptexturize($title),
            'keywords'=>esc_attr($key),
            'description'=>wptexturize(esc_attr($desc)),
            'image'=>qk_get_thumb(array('thumb'=>$thumb_url,'width'=>600,'height'=>400)),
            'url'=>esc_url(get_permalink($post_id)),
            'updated_time'=>get_the_modified_date('c',$post_id),
            'author'=>wptexturize(get_author_posts_url($author))
        );
    }
}