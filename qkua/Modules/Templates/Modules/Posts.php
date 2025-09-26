<?php namespace Qk\Modules\Templates\Modules;

use Qk\Modules\Common\Post;
use Qk\Modules\Templates\Single;

//文章卡片模板

class Posts{

    /**
     * 文章模块启动
     *
     * @param array $data 设置数据
     * @param int $i 第几个模块
     *
     * @return string
     */
    public function init($data, $i, $return = false){
        if(empty($data) || empty($data['post_type'])) return;
    
        $type = str_replace('-','_',$data['post_type']);
        if(!method_exists(__CLASS__, $type)) return;
    
        return self::$type($data, $i, $return);
    }
    
    /**
     * 获取文章列表html(post_1) 网格模式
     *
     * @param array $data 设置项数据
     * @param int $i 第几个模块
     * @param bool $return 是否直接返回 li 标签中的 html 代码，用作ajax加载
     *
     * @return string
     */
    public static function post_1($data,$i,$return = false){
  
        $index = $i;
        
        $i = isset($data['key']) && $data['key'] ? $data['key'] : 'ls'.round(100,999);
        
        //获取文章数据
        $_post_data = self::get_post_data($data);
        $post_data = $_post_data['data'];
        
        //计算宽度和高度
        $size = self::get_thumb_size($data,$data['post_row_count']);

        $html = '';
        
        foreach ($post_data as $k => $v) {
            
            $thumb = qk_get_thumb(array(
                'url' => $v['thumb'],
                'width' => $size['w'],
                'height' => $v['thumb_ratio'] == 0 || !$data['waterfall_show'] ? $size['h'] : '100%',
                'ratio' => 2
            ));
            
            //显示哪些post_meta
            $post_meta = self::post_meta_show($v,$data['post_meta']);
            
            $post_style = Single::get_single_post_settings($v['id'],'single_post_style');
            
            $padding_top = $data['waterfall_show'] == true ? ($v['thumb_ratio']*100).'%': $size['ratio'].'%';

            $html .= '<li class="post-list-item item-'.$post_style.'" id="item-'.$v['id'].'">
                <div class="item-in box qk-radius">
                    <div class="post-thumbnail" style="padding-top:'.$padding_top.'">
                        <a href="'.$v['link'].'" rel="nofollow" class="thumb-link">'.qk_get_img(array('src'=>$thumb,'class'=>array('post-thumb','w-h'),'alt'=>$v['title'])).'</a>
                    </div>
                    <div class="post-info">
                        <h2 class="text-ellipsis"><a href="'.$v['link'].'">'.$v['title'].'</a></h2>
                        '.$post_meta['desc'].'
                        <div class="post-info-buttom">
                            '.$post_meta['user'].'
                            '.$post_meta['date'].'
                            '.$post_meta['views'].'
                            '.$post_meta['like'].'
                            '.$post_meta['comment'].'
                        </div>
                    </div>
                </div>
            </li>';
        }
        
        if($return){
            return array(
                'count'=>$_post_data['count'],
                'index'=>$i,
                'pages'=>$_post_data['pages'],
                'data'=>$html
            );
        }
        return ($data['post_row_count'] != 5 ?
        '<style>
            '.($data['waterfall_show'] == true ? 
            
            '.post-item-'.$i.' ul.qk-waterfall > li{
                width:'.((floor((1/$data['post_row_count'])*10000)/10000)*100).'%;
            }' :
                
            '.post-item-'.$i.' .qk-grid{
                grid-template-columns: repeat('.$data['post_row_count'].', minmax(0, 1fr));
            }').'
        </style>':'').'
        <div class="'.$data['post_type'].' post-list post-item-'.$i.'" id="post-item-'.$i.'" data-key="'.$i.'" data-i="'.$index.'">
            '.self::get_post_modules_top($data).'
            <div class="hidden-line">
                <ul class="qk-grid '.($data['waterfall_show'] == true ? 'qk-waterfall' : '').'">'.$html.'</ul>
            </div>
            '.self::get_load_more_btn($data,$_post_data['pages']).'
        </div>';
    }
    
    /**
     * 获取文章列表html(post_2) 列表模式
     *
     * @param array $data 设置项数据
     * @param int $i 第几个模块
     * @param bool $return 是否直接返回 li 标签中的 html 代码，用作ajax加载
     *
     * @return string
     */
    public static function post_2($data,$i,$return = false){
  
        $index = $i;
        
        $i = isset($data['key']) && $data['key'] ? $data['key'] : 'ls'.round(100,999);
        
        //获取文章数据
        $_post_data = self::get_post_data($data);
        $post_data = $_post_data['data'];

        //计算宽度和高度
        $size = self::get_thumb_size($data,$data['post_row_count']);

        $html = '';
        
        foreach ($post_data as $k => $v) {
            
            $thumb = qk_get_thumb(array(
                'url' => $v['thumb'],
                'width' => $size['w'],
                'height' => $size['h'],
                'ratio' => 2
            ));
            
            //显示哪些post_meta
            $post_meta = self::post_meta_show($v,$data['post_meta']);
            
            $post_style = Single::get_single_post_settings($v['id'],'qk_single_post_style');

            $html .= '<li class="post-list-item item-'.$post_style.'" id="item-'.$v['id'].'">
                <div class="item-in box qk-radius">
                    <div class="post-module-thumb">
                        <div class="qk-radius post-thumbnail"'.($data['post_thumb_ratio'] ? ' style="padding-top:'.$size['ratio'].'%"':'').'>
                            <a href="'.$v['link'].'" rel="nofollow" class="thumb-link">'.qk_get_img(array('src'=>$thumb,'class'=>array('post-thumb','w-h','qk-radius'),'alt'=>$v['title'])).'</a>
                        </div>
                    </div>
                    <div class="post-info">
                        <h2 class="text-ellipsis"><a href="'.$v['link'].'">'.$v['title'].'</a></h2>
                        '.$post_meta['desc'].'
                        <div class="post-info-buttom">
                            <div class="buttom-left">
                                '.$post_meta['user'].'
                            </div>
                            <div class="buttom-right qk-flex">
                                '.$post_meta['date'].'
                                '.$post_meta['views'].'
                                '.$post_meta['like'].'
                                '.$post_meta['comment'].'
                            </div>
                        </div>
                    </div>
                </div>
            </li>';
        }
        
        if($return){
            return array(
                'count'=>$_post_data['count'],
                'index'=>$i,
                'pages'=>$_post_data['pages'],
                'data'=>$html
            );
        }
        return ($data['post_row_count'] !== 5 ?
        '<style>
            .post-item-'.$i.' .qk-grid{
                grid-template-columns: repeat('.$data['post_row_count'].', minmax(0, 1fr));
            }
        </style>':'').'
        <div class="'.$data['post_type'].' post-list post-item-'.$i.'" id="post-item-'.$i.'" data-key="'.$i.'" data-i="'.$index.'">
            '.self::get_post_modules_top($data).'
            <div class="hidden-line">
                <ul class="qk-grid">'.$html.'</ul>
            </div>
            '.self::get_load_more_btn($data,$_post_data['pages']).'
        </div>';
    }
    
    /**
     * 获取文章图片网格html(post_3) 图片网格
     *
     * @param array $data 设置项数据
     * @param int $i 第几个模块
     * @param bool $return 是否直接返回 li 标签中的 html 代码，用作ajax加载
     *
     * @return string
     * @version 1.0.0
     * @since 2023
     */
    public static function post_3($data,$i,$return = false){
  
        $index = $i;
        
        $i = isset($data['key']) && $data['key'] ? $data['key'] : 'ls'.round(100,999);
        
        //获取文章数据
        $_post_data = self::get_post_data($data);
        $post_data = $_post_data['data'];

        //计算宽度和高度
        $size = self::get_thumb_size($data,$data['post_row_count']);

        $html = '';
        
        foreach ($post_data as $k => $v) {
            $thumb = qk_get_thumb(array(
                'url' => $v['thumb'],
                'width' => $size['w'],
                'height' => $v['thumb_ratio'] == 0 || !$data['waterfall_show'] ? $size['h'] : '100%',
                'ratio' => 2
            ));
            
            $post_style = Single::get_single_post_settings($v['id'],'qk_single_post_style');
            
            if(!empty($v['cats'])) {
                $cat = $v['cats'][0];
                
                $badge_html = '<div class="post-module-badges">
                    <a href="'.$cat['link'].'" class="badge-item no-hover">'.$cat['name'].'</a>
                </div>';
            }
            
            $padding_top = $data['waterfall_show'] == true ? ($v['thumb_ratio']*100).'%': ($v['image_count'] > 1 ? 'calc('.$size['ratio'].'% - 15.6px)' : $size['ratio'].'%');

            $html .= '<li class="post-list-item item-'.$post_style.'" id="item-'.$v['id'].'">
                <div class="item-in">
                    <div class="post-module-thumb">
                        '.($v['image_count'] > 1 ? '<div class="post-thumb-shadow"></div>': '').'
                        <div class="post-thumbnail qk-radius" style="padding-top:'.$padding_top.';">
                            <a href="'.$v['link'].'" rel="nofollow" class="thumb-link">'.qk_get_img(array('src'=>$thumb,'class'=>array('post-thumb','w-h','qk-radius'),'alt'=>$v['title'])).'</a>
                        </div>
                    </div>
                    <a href="'.$v['link'].'" class="post-info"><h2 class="text-ellipsis">'.$v['title'].'</h2></a>
                    '.$badge_html.'
                </div>
            </li>';
        }
        
        if($return){
            return array(
                'count'=>$_post_data['count'],
                'index'=>$i,
                'pages'=>$_post_data['pages'],
                'data'=>$html
            );
        }
        return ($data['post_row_count'] != 5 ?
        '<style>
            '.($data['waterfall_show'] == true ? 
            
            '.post-item-'.$i.' ul.qk-waterfall > li{
                width:'.((floor((1/$data['post_row_count'])*10000)/10000)*100).'%;
            }' :
                
            '.post-item-'.$i.' .qk-grid{
                grid-template-columns: repeat('.$data['post_row_count'].', minmax(0, 1fr));
            }').'
        </style>':'').'
        <div class="'.$data['post_type'].' post-list post-item-'.$i.'" id="post-item-'.$i.'" data-key="'.$i.'" data-i="'.$index.'">
            '.self::get_post_modules_top($data).'
            <div class="hidden-line">
                <ul class="qk-grid '.($data['waterfall_show'] == true ? 'qk-waterfall' : '').'">'.$html.'</ul>
            </div>
            '.self::get_load_more_btn($data,$_post_data['pages']).'
        </div>';
    }
    
    /**
     * 获取文章图片网格html(post_4) 社区模式
     *
     * @param array $data 设置项数据
     * @param int $i 第几个模块
     * @param bool $return 是否直接返回 li 标签中的 html 代码，用作ajax加载
     *
     * @return string
     * @version 1.0.0
     * @since 2023
     */
    public static function post_4($data,$i,$return = false){
  
        $index = $i;
        
        $i = isset($data['key']) && $data['key'] ? $data['key'] : 'ls'.round(100,999);
        
        //获取文章数据
        $_post_data = self::get_post_data($data);
        $post_data = $_post_data['data'];

        //计算宽度和高度
        $size = self::get_thumb_size($data,$data['post_row_count']);

        $html = '';
        
        foreach ($post_data as $k => $v) {
            $thumb = qk_get_thumb(array(
                'url' => $v['thumb'],
                'width' => $size['w'],
                'height' => $v['thumb_ratio'] == 0 || !$data['waterfall_show'] ? $size['h'] : '100%',
                'ratio' => 2
            ));
            
            $post_style = Single::get_single_post_settings($v['id'],'qk_single_post_style');
            
            if(!empty($v['cats'])) {
                $cat = $v['cats'][0];
                
                $badge_html = '<div class="post-module-badges">
                    <a href="'.$cat['link'].'" class="badge-item no-hover">'.$cat['name'].'</a>
                </div>';
            }
            
            $padding_top = $data['waterfall_show'] == true ? ($v['thumb_ratio']*100).'%': ($v['image_count'] > 1 ? 'calc('.$size['ratio'].'% - 15.6px)' : $size['ratio'].'%');

            $html .= '<li class="post-list-item item-'.$post_style.'" id="item-'.$v['id'].'">
                <div class="item-in">
                    <div class="post-module-thumb">
                        '.($v['image_count'] > 1 ? '<div class="post-thumb-shadow"></div>': '').'
                        <div class="post-thumbnail qk-radius" style="padding-top:'.$padding_top.';">
                            <a href="'.$v['link'].'" rel="nofollow" class="thumb-link">'.qk_get_img(array('src'=>$thumb,'class'=>array('post-thumb','w-h','qk-radius'),'alt'=>$v['title'])).'</a>
                        </div>
                    </div>
                    <a href="'.$v['link'].'" class="post-info"><h2 class="text-ellipsis">'.$v['title'].'</h2></a>
                    '.$badge_html.'
                </div>
            </li>';
        }
        
        if($return){
            return array(
                'count'=>$_post_data['count'],
                'index'=>$i,
                'pages'=>$_post_data['pages'],
                'data'=>$html
            );
        }
        return ($data['post_row_count'] != 5 ?
        '<style>
            '.($data['waterfall_show'] == true ? 
            
            '.post-item-'.$i.' ul.qk-waterfall > li{
                width:'.((floor((1/$data['post_row_count'])*10000)/10000)*100).'%;
            }' :
                
            '.post-item-'.$i.' .qk-grid{
                grid-template-columns: repeat('.$data['post_row_count'].', minmax(0, 1fr));
            }').'
        </style>':'').'
        <div class="'.$data['post_type'].' post-list post-item-'.$i.'" id="post-item-'.$i.'" data-key="'.$i.'" data-i="'.$index.'">
            '.self::get_post_modules_top($data).'
            <div class="hidden-line">
                <ul class="qk-grid '.($data['waterfall_show'] == true ? 'qk-waterfall' : '').'">'.$html.'</ul>
            </div>
            '.self::get_load_more_btn($data,$_post_data['pages']).'
        </div>';
    }
    
    
    public static function get_load_more_btn($data,$pages) {
        $post_meta = isset($data['module_meta']) && is_array($data['module_meta']) ? $data['module_meta'] : array();
        
        if (in_array('load', $post_meta) && $pages > 1) {
            return '<div class="modules-bottom load-more" v-cloak>
                    <span v-if="locked">加载中...</span>
                    <span v-else-if="noMore">没有更多了</span>
                    <span @click.stop.self="loadMore()" v-else>加载更多 <i class="ri-arrow-right-s-line"></i></span>
                </div>';
        }
        
        return '';
    }
    
    /**
     * 获取文章模块的顶部内容
     * 
     * @param array $data 文章数据
     *   - post_meta: array 模块元数据
     *   - title: string 模块标题
     *   - desc: string 模块描述
     *   - nav: array 导航列表
     *   - change: string 换一换按钮
     *   - more: string 查看全部按钮链接
     * 
     * @return string 模块顶部内容的 HTML 代码
     */
    public static function get_post_modules_top($data) {
        
        $post_meta = isset($data['module_meta']) && is_array($data['module_meta']) ? $data['module_meta'] : array();
        
        //换一换
        $change = '';
        if (in_array('change', $post_meta)) {
            $change = '<div class="button" @click="exchange">
                            <i class="ri-refresh-line"></i>
                            <span>换一换</span>
                        </div>';
        }
        
        //查看全部
        $more = '';
        if (in_array('more', $post_meta) && !empty($data['module_btn_text']) && !empty($data['module_btn_url'])) {
            $more = '<a class="button see-more no-hover" href="'.$data['module_btn_url'].'" target="_blank">
                        <span>'.$data['module_btn_text'].'</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>';
        }
        
        //模块标题
        $title = '';
        if (in_array('title', $post_meta) && !empty($data['title'])) {
            $title = '<div class="module-info qk-flex">
                        <h2 class="module-title">'.$data['title'].'</h2>
                        '.($change || $more ? '<div class="module-action qk-flex">'.$change.$more.'</div>' : '').'
                    </div>';
        }
        
        //模块描述
        $desc = '';
        if (in_array('desc', $post_meta) && !empty($data['desc'])) {
            $desc = '<div class="module-desc">'.$data['desc'].'</div>';
        }
        
        //导航
        $nav = '';
        $nav_cat = isset($data['nav_cat']) ? (array)$data['nav_cat'] : array();
        
        if (in_array('nav', $post_meta) && $nav_cat) {
            
            $nav_items = '<li class="cat-item" @click="getList(0,\'all\')" :class="[{\'bg-text\':id == 0}]">
                        <span>全部</span>
                    </li>';
            
            foreach ($nav_cat as $cat_id) {
                //$cat_name = get_cat_name($cat_id);
                $term = get_term($cat_id); // 获取自定义分类的信息
                $cat_name = $term->name; // 获取自定义分类的名称
                // 如果分类名称为空字符串，跳过当前循环
                if (empty($cat_name)) continue;
                
                $nav_items .= '<li class="cat-item" @click="getList('.$cat_id.')" :class="[{\'bg-text\':id == '.$cat_id.'}]">
                        <span>'.$cat_name.'</span>
                    </li>';
            }
            
            $nav = '<div class="module-nav">
                        <ul class="post-cats-list">'.$nav_items.'</ul>
                    </div>';
        }
        
        $html = '';
        if ($title || $desc || $nav) {
            $html .= '<div class="modules-top">';
            
            $html .= $desc;
            
            if ($title || $nav) {
                $html .= '<div class="module-top-wrapper">';
                
                $html .= $title.$nav;
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * 获取文章数据
     *
     * @param array $data 查询参数
     * @param int $data['post_paged'] 当前页码，默认为1
     * @param int $data['post_count'] 每页显示的文章数量，默认为10
     * @param string $data['_post_type'] 文章类型，默认为'post'
     * @param array $data['author__in'] 查询指定作者的文章，默认为空数组
     * @param array $data['post_cat'] 查询指定分类的文章，默认为空数组
     * @param array $data['post_tag'] 查询指定标签的文章，默认为空数组
     * @param array $data['metas'] 自定义字段筛选条件，默认为空数组
     * @param string $data['month'] 月度筛选条件，默认为空
     * @param string $data['year'] 年度筛选条件，默认为空
     * @param string $data['search'] 搜索关键词，默认为空
     * @param bool $data['post_ignore_sticky_posts'] 是否忽略置顶文章，默认为false
     * @param string $data['post_order'] 文章排序方式，默认为空
     *        可选值：'random'（随机排序）、'sticky'（置顶文章优先）、'modified'（按修改时间排序）、'views'（按浏览量排序）、'like'（按点赞数排序）、'comments'（按评论数排序）
     * 
     * @return array 包含文章数量、页数和数据的数组
     */
    public static function get_post_data($data){
        
        $paged = isset($data['post_paged']) ? (int)$data['post_paged'] : 1;
        
        if($data['is_mobile'] && isset($data['mobile_post_count']) && !empty($data['mobile_post_count'])){
            $data['post_count'] = (int)$data['mobile_post_count'];
        }
        
        //偏移量 开始
        $offset = ($paged -1)*(int)$data['post_count'];
        
        //文章自定义类型查询
        $post_type = isset($data['_post_type']) && $data['_post_type'] ? $data['_post_type'] : 'post';

        $user_id = get_current_user_id();

        if((isset($data['author__in']) && (int)$user_id === (int)$data['author__in'][0]  && (int)$data['author__in'][0] !== 0) || (user_can( $user_id, 'manage_options') && isset($data['author__in']) && (int)$data['author__in'][0])){
            $data['post_status'] = array('publish','pending','draft');  //文章状态 发布 草稿
        }else{
            $data['post_status'] = array('publish');
        }
        
        //查询条件
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => (int)$data['post_count'] ? (int)$data['post_count'] : 10, //查询数量
            'tax_query'      => array(
                'relation'   => 'AND',
            ),
            'meta_query'     => array(
                'relation'   => 'AND',
            ),
            'date_query'     => array(
                'relation'   => 'AND',
            ),
            'offset'         => $offset,
            'post_status'    => $data['post_status'],
            'include_children' => true,
        );
        
        //排序
        if(isset($data['post_order']) && !empty($data['post_order'])){
            switch($data['post_order']){
                case 'random':
                    $args['orderby'] = 'rand'; 
                    break;
                case 'sticky': //置顶文章
                    $args['post__in'] = get_option( 'sticky_posts' );
                    $args['ignore_sticky_posts'] = 1;
                    break;
                case 'modified':
                    $args['orderby'] = 'modified'; //修改时间
                    break;
                case 'views':
                    $args['meta_key'] = 'views';
                    $args['orderby'] = 'meta_value_num';
                    break;
                case 'like':
                    $args['meta_key'] = 'qk_post_like';
                    $args['orderby'] = 'meta_value';
                    break;
                case 'comments':
                    $args['orderby'] = 'comment_count';
                    break;
            }
        }
        
        //如果存在用户 查询用户文章
        if(isset($data['author__in']) && !empty($data['author__in'])){
            $args['author__in'] = $data['author__in'];
        }
        
        //如果存在文章id 直接查询
        if(isset($data['post__in']) && !empty($data['post__in'])) {
            $args['post__in'] = $data['post__in'];
        }
        
        //如果是存在分类 查询分类下文章
        if(isset($data['post_cat']) && !empty($data['post_cat'])){
            // if(count($data['post_cat']) > 1){
            //     $data['post_cat'] = $data['post_cat'][0];
            // }
            array_push($args['tax_query'],array(
                'taxonomy' => 'category',
                'field'    => 'id',
                'terms'    => (array)$data['post_cat'],
                'include_children' => true,
                'operator' => 'IN'
            ));
        }
        
        //如果存在视频分类
        if(isset($data['video_cat']) && !empty($data['video_cat'])){
            array_push($args['tax_query'],array(
                'taxonomy' => 'video_cat',
                'field'    => 'id',
                'terms'    => (array)$data['video_cat'],
                'include_children' => true,
                'operator' => 'IN'
            ));
        }
        
        //如果存在专辑
        // if(isset($data['collection_slug']) && !empty($data['collection_slug'])){
        //     array_push($args['tax_query'],array(
        //         'taxonomy' => 'collection',
        //         'field'    => 'id',
        //         'terms'    => (array)$data['collection_slug'],
        //         'include_children' => true,
        //         'operator' => 'IN'
        //     ));
        // }

        //如果存在标签  自定义分类法 查询
        if(isset($data['post_tag']) && !empty($data['post_tag'])){
            array_push($args['tax_query'],array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => (array)$data['post_tag'],
                'include_children' => true,
                'operator' => 'IN'
            ));
        }
        
        //如果存在标签
        if(isset($data['tags']) && !empty($data['tags'])){
            array_push($args['tax_query'],array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => (array)$data['tags'],
                'operator' => 'AND'
            ));
        }
        
        //如果自定义字段筛选
        if(isset($data['metas']) && !empty($data['metas'])){
            foreach($data['metas'] as $k => $v){
                array_push($args['meta_query'],array(
                    'key'     => $k,
                    'value'   => $v,
                    'compare' => '=',
                ));
            }unset($v);
        }
        
        //如果是月度筛选
        if(isset($data['month']) && !empty($data['month'])){
            array_push($args['date_query'],array(
                'month' => esc_attr($data['month'])
            ));
        }

        //如果是年度筛选
        if(isset($data['month']) && !empty($data['month'])){
            array_push($args['date_query'],array(
                'year' => esc_attr($data['year'])
            ));
        }
        
        if(isset($data['search']) && !empty($data['search'])){
            $args['search_tax_query'] = true;
            $args['s'] = esc_attr($data['search']);
        }

        if(isset($data['post_ignore_sticky_posts'])){
            $args['ignore_sticky_posts'] = $data['post_ignore_sticky_posts']; // 忽略置顶文章
        }

        
        $the_query = new \WP_Query( $args );

        $post_data = array();
        $_pages = 1;
        $_count = 0;
        
        if ( $the_query->have_posts() ) {

            $_pages = $the_query->max_num_pages;
            $_count = $the_query->found_posts;

            while ( $the_query->have_posts() ) {

                $the_query->the_post();

                $post_data[] = self::get_post_metas($the_query->post->ID,$data);
            }
            wp_reset_postdata();
        }

        // if($data['post_order'] !== 'sticky' && $data['ignore_sticky_posts'] === 0){

        // }
        
        unset($the_query);
        return array(
            'count'=>$_count,
            'pages'=>$_pages,
            'data'=>$post_data
        );
    }
    
    //获取文章自定义字段信息
    public static function get_post_metas($post_id,$data = array()){

        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = wp_get_attachment_image_src($thumb_id,'full');

        if(!isset($thumb_url[0]) || !$thumb_url[0]){
            $thumb_url = array(
                \Qk\Modules\Common\Post::get_post_thumb($post_id),
                400,
                300
            );
            
            //https://www.php.cn/blog/detail/19716.html
            if($data['waterfall_show'] == true){//$data['post_type'] === 'post-3' //如果是瀑布流并没有设置文章缩略图 获取文章第一张图 的宽高
                $thumb_url = wp_get_attachment_image_src(attachment_url_to_postid($thumb_url[0]),'full');
            }
        }
        
        $post_meta = array();
        if(!isset($post_meta['get_post_meta'])) {
            $post_meta = Post::get_post_meta($post_id);
        }

        $post_meta['id'] = $post_id;
        $post_meta['title'] = get_the_title($post_id);
        $post_meta['subtitle'] =  get_post_meta($post_id, 'qk_subtitle', true);
        $post_meta['link'] = get_permalink($post_id);
        $post_meta['thumb'] = $thumb_url[0];
        $post_meta['thumb_ratio'] = $thumb_url[2] ? round($thumb_url[2]/$thumb_url[1],6) : 1;
        $post_meta['thumb_ratio'] = $post_meta['thumb_ratio'] >= 2 ? 2 : $post_meta['thumb_ratio'];
        
        $post_content = get_the_content($post_id);
        
        if($data['post_type'] === 'post-3') {
            $images = qk_get_first_img($post_content,'all'); //获取文章所有图像计数
            $post_meta['image_count'] = is_array($images) ? count($images) : 0;
        }
        
        //删除钩子
        // qk_remove_filters_with_method_name('the_content','post_download',10,1);
        // $excerpt = get_the_excerpt($post_id);
        $post_meta['desc'] = qk_get_desc($post_id,150,$post_content); //qk_get_desc(0,200,$excerpt ? $excerpt : get_the_content($post_id));
        $post_meta['status'] = get_post_status($post_id);
        unset($data);
        return $post_meta;
    }
    
    /**
     * 获取缩略图宽高 (宽高获取不太准确需要修复)
     *
     * @param array $data 设置项
     * @param string $thumb_count 每行显示数量
     *
     * @return void
     * @version 1.0.0
     * @since 2023
     */
    public static function get_thumb_size($data,$thumb_count){
        
        $thumb_count = (int)$thumb_count;
        
        //比例
        $data['post_thumb_ratio'] = $data['post_thumb_ratio'] ? $data['post_thumb_ratio'] : '1/0.618';
        
        //计算小工具后的页面宽的
        $page_width = $data['width'] ? $data['width'] : qk_get_page_width($data['widget_show'],$data['widget_width']);

        //获取缩略图比例
        $ratio = explode('/',$data['post_thumb_ratio']);
        $w_ratio = $ratio[0];
        $h_ratio = $ratio[1];
        
        //间距
        $gap = (int)qk_get_option('qk_gap');
        
        //计算每个卡片宽度
        $w = ($page_width - ($thumb_count - 1)*$gap) / $thumb_count;
        
        //计算高度
        $h = round($w/$w_ratio*$h_ratio);
        
        return apply_filters('qk_post_thumb_size', array(
            'w'=>$w,
            // 'm_w'=>$m_w,
            'h'=>$h,
            'page_w'=>$page_width,
            'ratio'=>round($h_ratio/$w_ratio*100,6)
        ));
    }
    
    /**
     * 获取当前文章的所有分类
     *
     * @param array $cats 分类数组
     *
     * @return void
     */
    public static function get_post_cats_list($cats){
        //文章分类
        $html = '';
        if(is_array($cats)){
            $html = '<ul class="post-categories qk-flex">';
            foreach($cats as $cat){
                $html .= '<li><a href="'.$cat['link'].'" rel="category tag">'.$cat['name'].'</a></li>';
            }
            
            unset($cat);
            
            $html .= '</ul>';
        }

        return $html;
    }
    
    /**
     * post_meta 显示
     *
     * @param array 数组
     *
     * @return void
     */
    public static function post_meta_show($data,$post_meta){
        
        $arr = array(
            'user'   => '',
            'date'   => '',
            'like'   => '',
            'comment'=> '',
            'views'  => '',
            'cats'   => '',
            'desc'   => ''
        );
        
        if(!empty($post_meta) && is_array($post_meta)) {
            
            foreach ($post_meta as $meta) {
                if($meta === 'date'){
                    $arr['date'] = '<span class="post-date"><i class="ri-time-line"></i>'.$data['date'].'</span>';
                }elseif($meta === 'comment'){
                    $arr['comment'] = '<span class="comment"><i class="ri-message-3-line"></i>'.$data['comment'].'</span>';
                }elseif($meta === 'views'){
                    $arr['views'] = '<span class="post-views"><i class="ri-eye-line"></i>'.$data['views'].'</span>';
                }elseif($meta === 'desc'){
                    $arr['desc'] = '<p class="post-excerpt text-ellipsis">'.$data['desc'].'</p>';
                }elseif($meta === 'user'){
                    $author = $data['author'];
                    $arr['user'] = '<a href="'.$author['link'].'" rel="nofollow" class="post-user">
                                    '.qk_get_avatar(array('src'=>$author['avatar'],'alt'=>$author['name'].'的头像')).'
                                    <span class="user-name">'.$author['name'].'</span>
                                </a>';
                }elseif($meta === 'like'){
                    $arr['like'] = '<span class="post-like"><i class="ri-thumb-up-line"></i>'.$data['like'].'</span>';
                }elseif($meta === 'cats'){
                    $arr['cats'] = '<span class="post-cats"><i class="ri-eye-line"></i>'.$data['cats'].'</span>';
                }
            }
        }
        
        return $arr;
    }
}