<?php namespace Qk\Modules\Common;

use Qk\Modules\Common\User;
// use \Firebase\JWT\JWT;
use Qk\Modules\Templates\Modules\Posts;

class Post{

    public function init(){

        //隐藏主题自带的自定义字段
        
        //add_filter( 'post_gallery', array($this,'post_gallery'), 10, 2 );
        //add_action( 'save_post',array(__CLASS__,'save_post_qrcode'),10,3);

        //add_filter('post_link', array(__CLASS__,'post_link'),9,3);

        // add_action(  'transition_post_status', array($this,'publish_post'), 999, 3 );
        // add_filter( 'wp_insert_post_data',array($this,'insert_post_data'),10, 2);

        add_filter('filter_download_data', array($this,'filter_download_data'), 10, 2);
    }
    
    
    /**
     * 获取文章meta内容
     *
     * @param int $post_id
     *
     * @return array 文章meta内容
     * @author 
     * @version 1.0.0
     * @since 2023
     */
    public static function get_post_meta($post_id = 0){
        
        if(!$post_id){
            global $post;
            if(!isset($post->ID)) return;
            $post_id = $post->ID;
        }
        
        //文章作者id
        $user_id = get_post_field('post_author', $post_id);
        
        $user_data = User::get_user_public_data($user_id);

        //获取分类信息
        $post_cats = get_the_category($post_id);
        $cats_data = array();

        foreach($post_cats as $cat){

            if(isset($cat->term_id)){
                // $color = get_term_meta($cat->term_id,'tax_color',true);
                // $color = $color ? $color : '#607d8b';
                $link = get_category_link( $cat->term_id );
    
                $cats_data[] = array(
                    'name'=>$cat->name,
                    //'color'=>$color,
                    'link'=>$link
                );
            }
        }

        unset($post_cats);

        $view = (int)get_post_meta($post_id,'views',true);
        
        $date = get_the_date('Y-m-d H:i:s',$post_id);
        
        return array(
            'date'=>self::time_ago($date),
            'date_normal'=>get_the_date('Y-m-d',$post_id),
            'ctime'=>get_the_date('c',$post_id),
            '_date'=>$date,
            'cats'=>$cats_data,
            'like'=>qk_number_format(self::get_post_vote($post_id)['like']),
            'comment'=>qk_number_format(get_comments_number($post_id)),
            'views'=>qk_number_format($view),
            'author' => $user_data
        );
    }
    
    /**
     * 获取文章缩略图
     *
     * @param int $post_id
     *
     * @return void
     * @author 
     * @version 1.0.0
     * @since 2023
     */
    public static function get_post_thumb($post_id = 0,$no_default = false){
        if(!$post_id){
            global $post;
            if(!isset($post->ID)) return '';
            $post_id = $post->ID;
        }
        
        //文章缩略图地址
        $post_thumbnail_url = get_the_post_thumbnail_url($post_id,'full');
        
        if($post_thumbnail_url){
            return esc_url($post_thumbnail_url);
        }else{
            $post_content = get_post_field('post_content', $post_id);
            
            //获取文章第一张图
            $thumb = qk_get_first_img($post_content);
            
            if(!$thumb && !$no_default) {
                return qk_get_default_img();
            }
        }

        return apply_filters('qk_get_post_thumb',$thumb,$post_id,$no_default);
    }
    
    /**
     * 获取文章相关推荐
     *
     * @param int $post_id
     * @param int $count 数量
     * 
     * @return void
     * @author 
     * @version 1.0.0
     * @since 2023
     */
    public static function get_posts_related($post_id,$count = 6){
        
        if(!(int)$post_id) return;
        
        $categorys = get_the_terms($post_id, 'category');
        $tags      = get_the_terms($post_id, 'post_tag');
        
        $args = array(
            'showposts'           => $count,
            'ignore_sticky_posts' => 1,
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'order'               => 'DESC',
            'orderby'             => 'meta_value_num',
            'meta_key'            => 'views',
            'tax_query'           => array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => array_column((array)$categorys, 'term_id'),
                ),
                array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'term_id',
                    'terms'    => array_column((array)$tags, 'term_id'),
                ),
            ),
        );
        
        $the_query = new \WP_Query( $args );

        $post_data = array();
        $_pages = 1;
        $_count = 0;
        
        if ( $the_query->have_posts() ) {

            $_pages = $the_query->max_num_pages;
            $_count = $the_query->found_posts;

            while ( $the_query->have_posts() ) {

                $the_query->the_post();

                $post_data[] = Posts::get_post_metas($the_query->post->ID,$data);
            }
            
            wp_reset_postdata();
        }
        
        unset($the_query);
        return array(
            'count'=>$_count,
            'pages'=>$_pages,
            'data'=>$post_data
        );
    }
    
    /**
     * 转换时间格式
     *
     * @param int $ptime 'Y-m-d H:i:s'
     *
     * @return void
     * @author 
     * @version 1.0.0
     * @since 2023
     */
    public static function time_ago($ptime,$return = false){

        if(!is_string($ptime)) return;
     
        $_ptime = strtotime($ptime);
        $etime = current_time('timestamp') - $_ptime;
        
        if ($etime < 1){
            $text = __('刚刚','qk');
        }else{
            $interval = array (         
                60 * 60                 =>  __('小时前','qk'),
                60                      =>  __('分钟前','qk'),
                1                       =>  __('秒前','qk')
            );
    
            if($etime <= 84600){
                foreach ($interval as $secs => $str) {
                    $d = $etime / $secs;
                    if ($d >= 1) {
                        $r = round($d);
                        $text = $r . $str;
                        break;
                    }
                };
            }else{
    
                $date = date_create($ptime);
    
                $y = date_format($date,"y");
                if($y == date('y')){
                    $text = sprintf(__('%s月%s日','qk'),date_format($date,"n"),date_format($date,"j"));
                }else{
                    $text = sprintf(__('%s年%s月%s日','qk'),$y,date_format($date,"n"),date_format($date,"j"));
                }
            }
        }  

        if($return) return $text;

        return '<time class="qk-timeago" datetime="'.$ptime.'" itemprop="datePublished">'.$text.'</time>';
    }
    
    //发布文章
    public static function insert_post($data){

        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>__('请先登录，才能发布文章','qk'));
        
        if(!qk_get_option('write_allow')) return array('error'=>__('投稿功能已被关闭，请联系管理员','qk'));
        
        //文本审查 ---开发中
        // $censor = apply_filters('qk_text_censor', $data['title'].$data['content'].$data['excerpt']);
        // if(isset($censor['error'])) return $censor;
        
        //是否是编辑文章
        $edit = isset($data['post_id']) && (int)$data['post_id'] !== 0;
        
        //检查当前用户是否是文章作者
        if($edit){
            if((get_post_field( 'post_author', $data['post_id'] ) != $user_id || get_post_type($data['post_id']) != 'post') && !user_can($user_id, 'administrator' ) && !user_can( $user_id, 'editor' )){
                return array('error'=>__('非法操作，不能编辑他人文章','qk'));
            }
        }
        
        //检查文章标题
        if(!isset($data['title']) || !$data['title']){
            return array('error'=>__('标题不可为空','qk'));
        }

        //检查文章内容
        if(!isset($data['content']) || !$data['content']){
            return array('error'=>__('内容不可为空','qk'));
        }
        
        //检查分类
        if(!isset($data['cats']) || !$data['cats']){
            return array('error'=>__('请选择文章分类','qk'));
        }
        
        $post_id = false;

        if($data['type'] !== 'draft'){
            if((user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'editor' ))){
                
                //管理员直接发布
                $data['type'] = 'publish';

            }else{
                
                //审核
                $data['type'] = 'pending';
            }

            // $can_publish = User::check_user_media_role($user_id,'post');
            // if($can_publish){
            //     $data['type'] = 'publish';
            // }
        }else{
            //存草稿
            $data['type'] = 'draft';
        }
        
        $data['title'] = str_replace(array('{{','}}'),'',sanitize_text_field($data['title']));

        if($edit){
            
            //获取文章作者ID
            $user_id = get_post_field( 'post_author', $data['post_id'] );
        }
        
        //提交
        $arg = array(
            'ID'=> $edit ? $data['post_id'] : null,
            'post_title' => $data['title'],
            'post_content' => wp_slash($data['content']),
            'post_status' => $data['type'],
            'post_author' => $user_id,
            'post_category' => $data['cats'],
            //'post_excerpt'=>$data['excerpt'],
        );
        
        if($edit){
            $post_id = wp_update_post($arg);
        }else{
            $post_id = wp_insert_post($arg);
        }
        
        if($post_id){

            if(!empty($data['tags'])){
                //设置标签
                $tags = array();
                foreach ($data['tags'] as $key => $value) {
                    $tags[] = str_replace(array('{{','}}'),'',sanitize_text_field($value));
                }
                wp_set_post_tags($post_id, $tags, false);
            } 
            
            //设置特色图
            $thumb_id = self::get_attached_id_by_url($data['thumb']);
            if($thumb_id){
                set_post_thumbnail($post_id,$thumb_id);
            }
            
            //隐藏内容权限设置
            if(isset($data['role'])){
                
                if(isset($data['role']['key']) && $data['role']['key']){
                    update_post_meta($post_id,'qk_post_content_hide_role',esc_attr(sanitize_text_field(wp_unslash($data['role']['key']))));
                }
                
                //余额
                if(isset($data['role']['num']) && $data['role']['num'] && in_array($data['role']['key'],array('money','credit'))){
                    $data['role']['num'] = (int)$data['role']['num'];
                    if($data['role']['num'] <= 0) return array('error'=>__('金额错误','qk'));
                    
                    update_post_meta($post_id,'qk_post_price',(int)$data['role']['num']);
                    
                }
                
                //等级
                // if(isset($data['role']['roles']) && !empty($data['role']['roles'])){
                //     $i = 0;
                //     foreach($data['role']['roles'] as $k=>$v){
                //         $data['role']['roles'][$i] = esc_attr(sanitize_text_field($v));
                //         $i++;
                //     }
                //     update_post_meta($post_id,'qk_post_roles',$data['role']['roles']);
                // }
                
                
                
            }
            
            //图片挂载到当前文章
            $regex = '/src="([^"]*)"/';
            preg_match_all( $regex, $data['content'], $matches );
            $matches = array_reverse($matches);

            if(!empty($matches[0])){
                foreach($matches[0] as $k => $v){
                    $thumb_id = self::get_attached_id_by_url($v);
                    if($thumb_id){
                        //检查是否挂载过
                        if(!wp_get_post_parent_id($thumb_id) || (int)wp_get_post_parent_id($thumb_id) === 1){
                            wp_update_post(
                                array(
                                    'ID' => $thumb_id, 
                                    'post_parent' => $post_id
                                )
                            );
                        }
                    }
                }
            }
            return array(
                'msg' => $data['type'] == 'draft' ? '草稿保存成功，你可选择继续编辑或发布文章' : '发布成功，等待管理员审核',
                'url' => $data['type'] == 'draft' ? qk_get_custom_page_url('write').'?id='.$post_id : qk_get_account_url('post')//get_author_posts_url($user_id)
            );
        }
        
        return array('error'=>__('发布失败','qk'));
    }
    
    //根据图片地址获取id
    public static function get_attached_id_by_url($url){
         return attachment_url_to_postid($url);
        
        $path = parse_url($url);

        if($path['path']){
            global $wpdb;
 
            $sql = $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND guid LIKE %s",
                'attachment',
                '%'.$path['path'].'%'
            );
        
            $post_id = $wpdb->get_var( $sql );

            return (int)$post_id;
        }
    }
    
    /**
     * 文章投票
     *
     * @param string $type 投票类型 like dislike
     * @param init $post_id 文章id
     * 
     * @return string 成功
     * @author 青青草原上
     * @version 1.0.0
     * @since 2023
     */
    public static function post_vote($type,$post_id){

        $user_id = get_current_user_id();
        if(!$user_id) return array('error'=>'请先登录之后再参与参与投票哦！');
        
        $post_author = (int)get_post_field('post_author',$post_id);
        if(!$post_author) return array('error'=>'参数错误');
        
        if($user_id === $post_author) return array('error'=>'不能给自己投票');
        
        //用户喜欢的评论
        $post_likes = get_user_meta($user_id, 'qk_post_likes', true);
        $post_likes = is_array($post_likes) ? $post_likes : array();
        $key = array_search($post_id, $post_likes); //in_array()
        
        $post_like = (int)get_post_meta($post_id, 'qk_post_like', true);

        if($key === false) {
            $post_likes[] = $post_id;
            $post_like += 1;
        }else{
            unset($post_likes[$key]);
            $post_like -= 1;
        }
        
        update_user_meta($user_id,'qk_post_likes',$post_likes);
        update_post_meta($post_id,'qk_post_like',$post_like);
        
        do_action('qk_post_vote',$post_id,$user_id,$key === false);
        
        return array(
            'message' => $key === false ? '点赞成功！' : '点赞取消！',
            'is_like' => $key === false ? true : false,
            'like' => $post_like,
            'count' => $post_like
        );
    }
    
    public static function get_post_vote($post_id){
        if(!$post_id) return;
        
        $user_id = get_current_user_id();
        
        $post_likes = get_user_meta($user_id, 'qk_post_likes', true);
        $post_likes = is_array($post_likes) ? $post_likes : array();
        
        $is_like = in_array($post_id, $post_likes); 

        $post_like = (int)get_post_meta($post_id, 'qk_post_like', true);
        
        return array(
            'is_like' => $is_like,
            'like' => $post_like,
            'count' => $post_like,
            'is' => $is_like,
        );
    }
    
    //获取用户是否收藏文章 和文章收藏数量
    public static function get_post_favorites($post_id){

        if(!$post_id) return;
        
        $current_user_id = get_current_user_id();

        //获取文章的收藏数据
        $post_favorites = get_post_meta($post_id, 'qk_post_favorites', true );
        $post_favorites = is_array($post_favorites) ? $post_favorites : array();

        $is_favorite = in_array($current_user_id,$post_favorites);

        return array(
            'is_favorite' => $is_favorite,
            'count' => count($post_favorites),
            'is' => $is_favorite,
        );;
    }
    
    /**
     * 获取指定用户文章投稿的所有分类
     * @param int $user_id 用户ID
     * @return array 分类信息数组
     */
    public static function get_user_post_categories($user_id) {
        $categories = array(); //存储分类的数组
        $args = array(
            'author' => $user_id, //筛选指定用户的文章
            'post_type' => 'post', //文章类型为post
            'posts_per_page' => -1, //获取所有文章
            'fields' => 'ids', //只获取文章ID，加快查询速度
            'category__not_in' => array(1) //排除默认分类，加快查询速度
        );
        $post_ids = get_posts($args); //获取文章ID列表
        if (!empty($post_ids)) {
            $categories = get_the_category($post_ids[0]); //获取第一篇文章的分类列表
            for ($i = 1; $i < count($post_ids); $i++) {
                $post_categories = get_the_category($post_ids[$i]); //获取文章的分类列表
                $categories = array_merge($categories, $post_categories); //合并分类列表
            }
            $categories = array_unique($categories, SORT_REGULAR); //去重
            //$categories = wp_list_pluck($categories, 'name'); //获取分类名称列表
            foreach ($categories as $category) {
                $category_info = array(
                    'name' => $category->name, //分类名称
                    'url' => get_category_link($category->term_id), //分类链接
                    'id' => $category->term_id //分类ID
                );
                $categories_info[] = $category_info; //存储分类信息到数组中
            }
        }
        return $categories_info;
    }
    
    //获取文章资源下载数据
    public static function get_post_download_data($post_id){
        $post_id = (int)$post_id;
        $user_id = get_current_user_id();
        
        $download_open = get_post_meta($post_id,'qk_single_post_download_open',true);
        //是否开启文章下载功能
        if(!$download_open) return array('error'=>'文章下载未开启');
        
        $download_data = get_post_meta($post_id,'qk_single_post_download_group',true);
        $download_data = is_array($download_data) ? $download_data : array();
        $download_data = apply_filters('filter_download_data',$download_data,$post_id);

        if(!$download_data || !is_array($download_data))  return array('error'=>'文章没有下载资源');
        
        $data = array();
        $index = 0;
        
        $user_lv = array(
            'lv'=> User::get_user_lv($user_id),
            'vip' => User::get_user_vip($user_id)
        );
        
        //获取是否开启游客支付
        $can_not_login_pay = get_post_meta($post_id,'qk_down_not_login_buy',true);
        
        foreach ($download_data as $key => $value) {
            $rights = apply_filters('qk_get_download_rights', $value['rights']);
            $can = apply_filters('check_user_can_download', $post_id,$user_id,$rights,$index);
            
            $not_login_pay = false;
            
            if($can_not_login_pay) {
                foreach ($rights as $k => $v) {
                    if($v['lv'] === 'not_login' || $v['lv'] === 'all'){
                        $not_login_pay = true;
                    }
                }
            }

            $data[] = array(
                'title' => !empty($value['title']) ? $value['title'] : get_the_title($post_id),
                'link' => qk_get_custom_page_url('download').'?post_id='.$post_id.'&index='.$index,
                'attrs' => !empty($value['attrs']) ? self::get_download_attrs($value['attrs']) :array(),
                'rights' => $rights,
                'current_user'=>array(
                    'can'=>$can,
                    'lv'=> $user_lv,
                    'not_login_pay' => $not_login_pay
                ),
                //'demo' => !empty($data['download_group']) ? self::get_download_links($post_id,$data['download_group']) : array(),
            );
            
            $index++;
        }
        
        return $data;
    }
    
    //将下载数据中的属性字符串转换成数组
    public static function get_download_attrs($attrs){
        if(!$attrs) return array();

        $attrs = trim($attrs, " \t\n\r");
        $attrs = explode(PHP_EOL, $attrs );

        $args = array();

        foreach ($attrs as $k => $v) {
            $v = trim($v, " \t\n\r");
            $_v = explode('|', $v);
            if(!isset($_v[0]) && !isset($_v[1])) continue;

            $args[] = array(
                'key'=>$_v[0],
                'value'=>$_v[1]
            );
        }
        
        return $args;
    }
    
    //获取下载页面数据
    public static function get_download_page_data($post_id,$index){

        $user_id = get_current_user_id();
        
        $download_open = get_post_meta($post_id,'qk_single_post_download_open',true);
        //是否开启文章下载功能
        if(!$download_open) return array('error'=>'文章下载未开启');

        $download_data = get_post_meta($post_id,'qk_single_post_download_group',true);
        $download_data = is_array($download_data) ? $download_data : array();
        $download_data = apply_filters('filter_download_data',$download_data,$post_id);

        if(!$download_data || !isset($download_data[$index]))  return array('error'=>'没有找到您要下载的资源');
        
        $data = $download_data[$index];
        
        $rights = apply_filters('qk_get_download_rights', $data['rights']);
        
        $can = apply_filters('check_user_can_download', $post_id,$user_id,$rights,$index);
        
        return array(
            'title' => get_the_title($post_id),
            'attrs' => !empty($data['attribute']) ? $data['attribute'] :array(),
            'links' => !empty($data['download_group']) ? self::get_download_links($post_id,$user_id,$data['download_group']) : array(),
            'can'=>$can,
        );

    }
    
    public static function filter_download_data($data,$post_id){
        $orderby = get_post_meta($post_id,'qk_download_data_orderby',true);
        
        if (is_array($data) && $orderby) {
            $data = array_reverse($data);
        }
        
        return $data;
    }
    
    
    //获取下载连接
    public static function get_download_links($post_id,$user_id,$data) {
        if(!$data || !is_array($data)) return array();
        
        $arg = array();
        
        foreach ($data as $key => $value) {
            
            if(class_exists('Jwt_Auth_Public') && !empty($value['url'])){
                $issuedAt = time();
                $expire = $issuedAt + 300;//5分钟时效
                
                $token = array(
                    "iss" => QK_HOME_URI,
                    "iat" => $issuedAt,
                    "nbf" => $issuedAt,
                    'exp'=>$expire,
                    'data'=>array(
                        'url'=> $value['url'],
                        'sign'=> md5($post_id.AUTH_KEY.$user_id),
                        'user_id'=>$user_id,
                        'post_id'=>$post_id,
                    )
                );

                $token = \Firebase\JWT\JWT::encode($token, AUTH_KEY);
            }
            
            //加密下载地址
            $arg[] = array(
                'name' => $value['name'] ?: '下载',
                'token' => $token ?: '',
                'jy' => $value['jy'],
                'tq' => $value['tq']
            );
        }
        
        return $arg;
    }
    
    //获取文件的真实地址
    public static function download_file($token){

        try{
            //检查验证码
            $decoded = \Firebase\JWT\JWT::decode($token, AUTH_KEY,array('HS256'));

            if(!isset($decoded->data->sign) || !isset($decoded->data->user_id)){
                return array('error'=>'参数错误');
            }

            $sign = md5($decoded->data->post_id.AUTH_KEY.$decoded->data->user_id);

            if($sign !== $decoded->data->sign) return array('error'=>'参数错误');

            $down_count = apply_filters('check_user_can_download_all', $decoded->data->user_id);

            if(!in_array($decoded->data->post_id,$down_count['posts']) && (int)$down_count['count'] < 9999 && get_user_meta($decoded->data->user_id,'qk_download_count',true)){
                
                $down_count['posts'][] = $decoded->data->post_id;
                $down_count['count'] = (int)$down_count['count'] - 1;
                
                update_user_meta($decoded->data->user_id,'qk_download_count',$down_count);
            }
            
            return $decoded->data->url;

        }catch(\Firebase\JWT\ExpiredException $e) {  // token过期
            return array('error'=>'网页时效过期，请重新发起');
        }catch(\Exception $e) {  //其他错误
            return array('error'=>'解码失败');
        }

    }
}