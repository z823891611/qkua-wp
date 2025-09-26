<?php namespace Qk\Modules\Templates;
use Qk\Modules\Common\Post;
/******存档模块*******/

class Archive{

    public function init(){ 
        
        //分类页顶部
        add_action('qk_archive_category_top',array($this,'archive_top'),10);
        //标签页顶部
        add_action('qk_archive_post_tag_top',array($this,'archive_top'),10);
        add_action('qk_archive_normal_top',array($this,'archive_top'),10);
        add_action('qk_archive_video_cat_top',array($this,'archive_top'),10);
        
        //置顶文章
        add_action('qk_archive_category_content_before',array($this,'archive_sticky_posts'),10);
        add_action('qk_archive_post_tag_content_before',array($this,'archive_sticky_posts'),10);
        add_action('qk_archive_normal_content_before',array($this,'archive_sticky_posts'),10);
        add_action('qk_archive_video_cat_content_before',array($this,'archive_sticky_posts'),10);
        
        //圈子
        add_action('qk_circle_sticky_posts',array($this,'archive_sticky_posts'),10);
    }
    
    /**
     * 存档页面顶部
     *
     * @return string
     */
    public function archive_top(){
        
        $term = get_queried_object();
        if(!isset($term->term_id)) return;

        //$title = get_the_archive_title();

        // global $wp;
        // $url = QK_HOME_URI.'/'.$wp->request;
        // $request = http_build_query($_REQUEST);
        // $request = $request ? '?'.$request : '';
        // $request = remove_query_arg('archiveSearch',$request);
        // $url = preg_replace('#page/([^/]*)$#','', $url);
        
        // $url = get_permalink();
        // $request = http_build_query(array_diff_key($_REQUEST, array('archiveSearch' => '')));
        // $url = $request ? $url . '?' . $request : $url;

        $img = qk_get_thumb(array('url'=>get_term_meta($term->term_id,'qk_tax_img',true),'width'=>150,'height'=>150)) ?? '';
        $cover = qk_get_thumb(array('url'=>get_term_meta($term->term_id,'qk_tax_cover',true),'width'=>1200,'height'=>300)) ?? '';

        //$search = isset($_GET['archiveSearch']) && !empty($_GET['archiveSearch']) ? $_GET['archiveSearch'] : '';

        $fliter = self::archive_filters();
        
        do_action('qk_archive_top',$term);

        ?>
        <div class="qk-tax-header">
            <div class="wrapper">
                <div class="tax-info mg-b qk-radius box">
                    <div class="tax-cover" style="background-image: url(<?php echo $cover ?>);"></div>
                    <div class="tax-details">
                        <div class="tax-detail">
                            <img src="<?php echo $img ?>" alt="<?php echo $term->name ?>" class="tax-icon">
                            <div class="tax-title">
                                <h1 class="tax-name"><?php echo $term->name ?></h1>
                                <div class="tax-count"><span class="count"><?php echo qk_number_format($term->count) ?></span> 篇主题 &nbsp;|  &nbsp;<span class="count">0</span> 人关注</div>
                            </div>
                            <div class="tax-join qk-flex"><span class="button">订阅</span></div>
                        </div>
                        <p class="tax-desc"><?php echo $term->description ?></p>
                    </div>
                </div>
                <?php if($fliter){ ?>
                    <div class="tax-fliter mg-b qk-radius box">
                        <?php echo $fliter; ?>
                    </div>
                <?php } ?>
                <?php do_action('qk_tax_header_after',$term); ?>
            </div>
        </div>
        
    <?php
    }
    
    /**
     * 获取指定分类的置顶文章
     *
     * @param object $term 分类对象
     * @return array $posts 置顶文章数组
     */
    public function archive_sticky_posts() {
        
        $term_id = get_queried_object_id();
        
        if (empty($term_id)) return;
        
        // 获取分类的置顶文章
        $sticky_posts = get_term_meta($term_id, 'qk_tax_sticky_posts', true);

        // 判断是否有置顶文章
        if (empty($sticky_posts)) return;
        
        // 设置查询参数
        // $args = array(
        //     'post__in' => $sticky_posts,
        //     'ignore_sticky_posts' => 1,
        //     'orderby' => 'post__in'
        // );
        
        // // 获取置顶文章
        // $posts = get_posts($args);
        
        // 遍历文章数组，生成 HTML 列表
        $html = '<div class="tax-sticky-posts mg-b qk-radius box"><ul class="sticky-posts-list">';
        foreach ($sticky_posts as $post_id) {
            $html .= '<li class="item"><span class="tax-sticky bg-text"><i class="ri-pushpin-fill"></i>置顶</span><a href="' . get_permalink($post_id) . '" class="text-ellipsis">' . get_the_title($post_id) . '</a></li>';
        }
        $html .= '</ul></div>';
        
        echo $html;
    }
    
    public static function get_fliter_data($term_id){

        $fliter_group = (array)qk_get_option('tax_fliter_group');

        if(!empty($fliter_group)){
            foreach ($fliter_group as $key => $value) {
                if(isset($value['fliter_group']) && $value['filter_open']){
                    
                    foreach ($value['fliter_group'] as $k => $v) {
                        if(isset($v['type']) && $v['type'] == 'cats') {
                            if(in_array((string)$term_id,$v['cats'])){
                                return $value;
                            }
                        }
                    }
                }
            }
        }

        $filters = get_term_meta($term_id,'qk_filter',true);

        return $filters;
    }
    
    /**
     * 存档页面筛选html
     *
     * @return string
     * @version 1.0.0
     * @since 2023
     */
    public function archive_filters(){

        $term = get_queried_object();
        if(!isset($term->term_id)) return;
        
        //获取筛选设置项数据
        $filters = self::get_fliter_data($term->term_id);
        
        if(!isset($filters['filter_open']) || !$filters['filter_open']) return;
        
        $request = http_build_query($_REQUEST);
        $request = $request ? '?'.$request : '';

        $request = remove_query_arg('archiveSearch',$request);
        
        global $wp;
        $url = QK_HOME_URI.'/'.$wp->request;
        $url = preg_replace('#page/([^/]*)$#','', $url);
        
        
        $html = '
            <div class="filters-box">
                <ul>';
        
        if(isset($filters['fliter_group'])) {
            foreach ($filters['fliter_group'] as $key => $value) {
                $type = 'filter_'.$value['type'];
                
                if(!method_exists(__CLASS__, $type)) return;

                $html .= self::$type($value[$value['type']],$term,$request,$url,$value);
            }
        }

        $html .= '</ul></div>';
        
        return $html;
    }
    
    /**
     * 分类目录筛选
     *
     * @param array $term_id 允许筛选的分类目录数组
     *
     * @return bool 设置项错误或为空
     * @return string 设置项转html

     * @version 1.0.0
     * @since 2023
     */
    public static function filter_cats($cats,$term,$request,$url,$data){
        if(empty($cats)) return;

        $is_tax = $term->taxonomy === 'category' || $term->taxonomy === 'video_cat';

        $a = '';
        foreach($cats as $k => $v){
            $_term = get_term($v);//get_terms(array('category', 'video_cat'), array('include' => $v));//get_term_by('id',$v,'category');
            if(isset($_term->term_id)){
                if($is_tax){
                    $url = get_term_link($_term->term_id).$request;
                }else{
                    $url = add_query_arg('post_cat',$v,$url.$request);
                }
    
                $a .= '<a href="'.$url.'" class="'.($term->term_id == $v || isset($_GET['post_cat']) && $_GET['post_cat'] == $v ? 'current' : '').'" title="'.$_term->name.'">'.$_term->name.'</a>';
            }
        }

        if($a){
            if(!$is_tax){
                $a = '<a href="'.(remove_query_arg('post_cat',$url.$request)).'" class="'.(!isset($_GET['post_cat']) ? 'current' : '').'">全部</a>'.$a;
            }
            
            return '<li><div class="filter-name">'.($data['title']?:'分类').'：</div><div class="filter-items">'.$a.'</div></li>';
        }
    }
    
    /**
     * 标签筛选
     *
     * @param array $tags 允许筛选的标签
     *
     * @return bool 设置项错误或为空
     * @return string 设置项转html

     * @version 1.0.0
     * @since 2023
     */
    public static function filter_tags($tags,$term,$request,$_url,$data){
        if(empty($tags)) return;

        static $i = '';
        $is_tax = $term->taxonomy === 'post_tag';
        $a = '';

        foreach($tags as $k=>$v){
            $_term = get_term_by('id',$v, 'post_tag');

            if(isset($_term->term_id)){
                if($is_tax){
                    $url = get_term_link($_term->term_id).$request;
                }else{
                    $url = add_query_arg('tags'.$i,$_term->slug,$_url.$request);
                }

                $a .= '<a href="'.$url.'" class="'.($term->slug === $_term->slug || (isset($_GET['tags'.$i]) && $_GET['tags'.$i] === urldecode($_term->slug)) ? 'current' : '').'" title="'.$_term->name.'">'.$_term->name.'</a>';
            }
            
        }

        if($a){
            if(!$is_tax){
                $a = '<a href="'.(remove_query_arg('tags'.$i,$_url.$request)).'" class="'.(!isset($_GET['tags'.$i]) ? 'current' : '').'">全部</a>'.$a;
            }
            $i = 0;
            $i++;
            return '<li><div class="filter-name">'.($data['title']?:'标签').'：</div><div class="filter-items">'.$a.'</div></li>';
        }
    }
    
    /**
     * 自定义字段筛选（高级）
     *
     * @param string $meta 自定义字段的设置项
     *
     * @return bool 设置项错误或为空
     * @return string 设置项转html
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function filter_metas($metas,$term,$request,$_url,$data){

        if(empty($metas)) return;

        $html = '';
        
        $meta_key = isset($data['meta_key']) ? $data['meta_key'] : '';

        if($meta_key) {
        
            $a = '<div class="filter-items"><a href="'.(remove_query_arg($meta_key,$_url.$request) ?: $url).'" data-key="all" class="'.(!isset($_GET[$meta_key]) ? 'current' : '').'">全部</a>';
            
            foreach($metas as $k => $v){
                
                $a .= '<a class="'.(isset($_GET[$meta_key]) && $_GET[$meta_key] == $v['meta_value'] ? 'current' : '').'" href="'.(add_query_arg($meta_key,$v['meta_value'],$url.$request)).'" data-key="'.$v['meta_value'].'" title="'.$v['meta_name'].'">'.$v['meta_name'].'</a>';
                
            }
            
            $a .= '</div>';
        }

        $html .= '<li><div class="filter-name">'.($data['title']?:'自定义').'：</div>'.$a.'</li>';
        
        return $html;
    }
    
    /**
     * 排序筛选
     *
     * @param array $orders 排序的筛选项
     *
     * @return bool 设置项错误或为空
     * @return string 设置项转html
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function filter_orderbys($orderbys,$term,$request,$url,$data){
        if(empty($orderbys)) return;
        
        $options = array(
            'new'  => '最新',
            'random'  => '随机',
            'views'  => '浏览',
            'like'  => '喜欢',
            'comments'  => '评论',
            'modified'  => '更新',
        );
        
        $html = '';
        foreach ($orderbys as $orderby) {
            
            if($orderby == 'new') {
                $html .= '<a href="'.(add_query_arg('post_order','new',$url.$request)).'" class="'.(!isset($_GET['post_order']) || (isset($_GET['post_order']) && $_GET['post_order'] == 'new') ? 'current' : '').'">'.$options[$orderby].'</a>';
            }else{
                $html .= '<a href="'.(add_query_arg('post_order',$orderby,$url.$request)).'" class="'.(isset($_GET['post_order']) && $_GET['post_order'] == $orderby ? 'current' : '').'">'.$options[$orderby].'</a>';
            }
            
        }
        
        if($html){
            return '<li><div class="filter-name">'.($data['title']?:'排序').'：</div><div class="filter-items">'.$html.'</div></li>';
        }
    }
}