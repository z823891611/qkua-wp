<?php 
namespace Qk\Modules\Common;
use Qk\Modules\Common\Post;
use Qk\Modules\Common\User;
use Qk\Modules\Common\Search;
use Qk\Modules\Common\ShortCode;
use Qk\Modules\Common\Comment;
use Qk\Modules\Common\CircleRelate;

class Circle {
    
    //获取圈子设置
    public static function get_circle_settings($circle_id,$type){

        $setting = apply_filters('qk_get_circle_setting',$circle_id,$type);
        
        if($setting !== false){
            return $setting;
        }
        
        $default = qk_get_option($type);
        $default = $default ? $default :'';
        
        return $default;
    }
    
    /**
     * 获取搜索建议 搜索文章、用户、标签、分类和自定义分类法
     *
     * @param array $data 搜索词，可以是字符串或数组
     * @return array 结果数组
     */
    public static function get_search_circle($data) {
        
        if(!isset($data['type']) || !in_array($data['type'],array('circle_cat','topic'))) return array('error' => '搜索类型错误');
        $taxonomy = $data['type'];
        
        $search_terms = isset($data['keyword']) ? $data['keyword'] : '';
        // 检查搜索词的类型
        if (is_string($search_terms)) {
            $search_terms = array($search_terms);
        } elseif (!is_array($search_terms)) {
            return array(); // 返回空数组，表示没有搜索结果
        }
        
        // 过滤和验证搜索词
        $search_terms = array_map('sanitize_text_field', $search_terms);
        
        if(empty($search_terms)) return false;
        
        //搜索标签、分类和自定义分类法
        $term_args = array(
            'name__like' => $search_terms[0],
            'hide_empty' => false, // 显示没有文章的分类和标签
            'number' => 10, // 限制搜索结果数量为10个
        );
        $terms = get_terms($taxonomy, $term_args);
    
        // 判断是否有分类和标签结果
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $similarity = Search::calculate_similarity($term->name, $search_terms);
                if($taxonomy == 'circle_cat'){
                    // 获取分类和标签信息
                    $term_info = self::get_circle_data($term->term_id);
                }else {
                    // 获取分类和标签信息
                    $term_info = self::get_topic_data($term->term_id);;
                }
                
                $term_info['similarity'] = $similarity;
                $results[] = $term_info;
            }
        }
    
        // 根据相似度对结果数组进行排序
        usort($results, function($a, $b) {
            return $b['similarity'] - $a['similarity'];
        });
    
        // 返回结果数组
        return $results;
    }
    
    //发布帖子
    public static function insert_moment($data){
        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>'请先登录，才能发布帖子');
        
        //按 ID 或名称更改当前用户。
        wp_set_current_user($user_id);
        
        //圈子名字
        $circle_name = qk_get_option('circle_name');
        
        $data['circle_id'] = (int)$data['circle_id'];
        
        if(!$data['circle_id']){
            
            //默认圈子
            $default_circle_id = qk_get_option('default_post_circle');
            
            if(empty($default_circle_id)) {
                return array('error'=>sprintf('请选择帖子所在的%s',$circle_name));
            }
            
            $data['circle_id'] = (int)$default_circle_id;
            
        } else {
            // 检查圈子是否存在
            $circle_exist = term_exists($data['circle_id'], 'circle_cat');
            
            if ($circle_exist == 0 || $circle_exist == null) return array('error'=>sprintf('%s不存在，请重新选择',$circle_name));
        }
        
        if(!self::get_circle_settings($data['circle_id'],'circle_post_open')) {
            return array('error'=>sprintf('%s发帖功能已被关闭，请联系管理员',$circle_name));
        }
        
        //发帖权限
        $moment_role = self::check_insert_moment_role($user_id,$data['circle_id']);
        if(empty($moment_role)) return array('error'=>'权限错误');
        
        //检查是否需要加入圈子才能发帖
        if($moment_role['is_join_circle_post'] && !$moment_role['in_circle']) {
            return array('error'=>sprintf('你需要加入%s才能发帖',$circle_name));
        }
        
        //检查是否有发帖权限
        if(!$moment_role['can_create_moment']) {
            return array('error'=>sprintf('当前你无权限在%s发帖',$circle_name));
        }
        
        //帖子id
        $data['moment_id'] = isset($data['moment_id']) ? (int)$data['moment_id'] : 0;
        
        //检查标题
        $data['title'] = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['title'])));
        if(qkGetStrLen($data['title']) > 100) return array('error' => '标题太长，请限制在1-100个字符之内');
        
        //检查帖子内容
        $content = wp_strip_all_tags(str_replace(array('{{','}}'),'',$data['content']));
        if(qkGetStrLen($content) > $moment_role['media_count']['max_word_limit'] || qkGetStrLen($content) < $moment_role['media_count']['min_word_limit']) {
            return array('error'=>sprintf('内容长度请控制在%s-%s个字符之内',$moment_role['media_count']['min_word_limit'],$moment_role['media_count']['max_word_limit']));
        }
        
        //检查是否帖子隐私权限
        $privacy_role = self::check_privacy_role($data,$moment_role['privacy_role']);
        if(isset($privacy_role['error'])) return $privacy_role;
        
        //检查帖子类型
        $moment_type = self::check_moment_type($data,$moment_role['type_role']);
        if(isset($moment_type['error'])) return $moment_type;
        
        //检查媒体
        $check_media = self::check_media($data,$moment_role);
        if(isset($check_media['error'])) return $check_media;
        
        //检查是否有无需审核直接发布权限
        $post_status = 'pending';

        if($moment_role['can_moment_public']) {
            $post_status = 'publish';
        }
        
        //如果标题为空 自动设置标题
        $auto_title = false;
        if(empty($data['title'])){
            $data['title'] = mb_strimwidth($content,0,100,'','utf-8');
            if(strlen($data['title']) < strlen($content)) $data['title'] = $data['title'].' ......';
            $auto_title = true;
        }
        
        //准备文章发布参数
        $args = array(
            'post_type' => 'circle',
            'post_title' => $data['title'],
            'post_content' => $content,
            'post_status' => $post_status,
            'post_author' => $user_id,
        );
        
        //判断是发布还是修改帖子
        if(!empty($data['moment_id'])) {
            if(get_post_type($data['moment_id']) !== 'circle') return array('error' => '帖子不存在');
            
            //如果都不是 检查当前用户是否有编辑帖子的权力
            $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'circle_id'=>$data['circle_id'],'post_id' => $data['moment_id']));
        
            if(!$manage_role['can_edit']) return array('error'=>'您无权限编辑帖子');
            
            if($manage_role['is_self']) {
                $can_delete = self::check_user_can_delete($user_id,$data['moment_id']);
                if(isset($can_delete['error'])) return $can_delete;
            }
            
            unset($args['post_author']);
            $args['ID'] = (int)$data['moment_id'];
            
            $post_id = wp_update_post($args);
        }
        
        //发布帖子
        else {
            $post_id = wp_insert_post($args);
        }
        
        if($post_id){
            
            //设置圈子
            wp_set_post_terms($post_id,array($data['circle_id']),'circle_cat');
            
            //设置话题
            preg_match_all('/#([^#]+)#/', $content, $topics);
            $_topics = array();
            
            if (!empty($topics[1])) {
                foreach ($topics[1] as $topic) {
                    // 检查话题是否存在
                    $term = term_exists($topic,'topic');

                    if ($term !== 0 && $term !== null) {
                        // // 获取话题的链接
                        // $term_link = get_term_link($term['term_id'], 'topic');
        
                        // // 替换话题为链接
                        // $content = str_replace("#$topic#", "<a href='$term_link'>$topic</a>", $content);
                        $_topics[] = $topic;
                    }
                }
            }
            
            // 设置文章的话题
            wp_set_post_terms($post_id, $_topics, 'topic');
            
            //设置圈子板块（标签）
            if(!empty($data['tag'])) {
                $tags = get_term_meta($data['circle_id'], 'qk_circle_tags', true);
                $tags = !empty($tags) && is_array($tags) ? $tags :array();
                // 使用array_search函数来搜索包含特定名称的元素的键
                $key = array_search($data['tag'], array_column($tags, 'name'));
                
                if($key !== false) {
                    update_post_meta($post_id,'qk_circle_tag',$data['tag']);
                }
            }else{
                delete_post_meta($post_id,'qk_circle_tag');
            }
            
            //设置帖子隐私权限
            if(is_array($privacy_role)){
                
                if(isset($privacy_role['type']) && !empty($privacy_role['type'])){
                    update_post_meta($post_id,'qk_post_content_hide_role',$privacy_role['type']);
                }
                
                //余额
                if(isset($privacy_role['value']) && !empty($privacy_role['value'])){
                    
                    if(in_array($privacy_role['type'],array('money','credit'))) {
                        update_post_meta($post_id,'qk_post_price',(int)$privacy_role['value']);
                    }
                    
                    if($privacy_role['type'] == 'password') {
                        update_post_meta($post_id,'qk_post_password',(int)$privacy_role['value']);
                    }
                }
                
                //等级
                if($privacy_role['type'] == 'roles' && isset($privacy_role['roles']) && !empty($privacy_role['roles'])){
                    foreach($privacy_role['roles'] as $k=>$v){
                        $privacy_role['roles'][$k] = esc_attr(sanitize_text_field($v));
                    }
                    update_post_meta($post_id,'qk_post_roles',$privacy_role['roles']);
                }
                
            }
            
            //图片挂载到当前帖子
            if(!empty($check_media['image']) || (isset($data['moment_id']) && (int)$data['moment_id'])){
                
                update_post_meta($post_id,'qk_circle_image',$check_media['image']);
                
                foreach ($check_media['image'] as $k => $value) {
                    
                    if((int)$value['id']) {
                        //检查是否挂载过
                        if(!wp_get_post_parent_id($value['id']) || (int)wp_get_post_parent_id($value['id']) === 1){
                            wp_update_post(
                                array(
                                    'ID' => $value['id'], 
                                    'post_parent' => $post_id
                                )
                            );
                        }
                    }
                }
            }
            
            //视频挂载到当前帖子
            if(!empty($check_media['video']) || (isset($data['moment_id']) && (int)$data['moment_id'])){
  
                foreach ($check_media['video'] as $k => &$value) {
                    
                    if((int)$value['id']) {
                        //检查是否挂载过
                        if(!wp_get_post_parent_id((int)$value['id']) || (int)wp_get_post_parent_id((int)$value['id']) === 1){
                            wp_update_post(
                                array(
                                    'ID' => (int)$value['id'], 
                                    'post_parent' => $post_id
                                )
                            );
                        }
                        
                        if(!empty($value['thumb'])){
                            $thumb_id = attachment_url_to_postid($value['thumb']);
                            if($thumb_id) {
                                set_post_thumbnail((int)$value['id'],$thumb_id);
                            }
                        }
                        
                        unset($value['thumb']);
                    }
                }
                if(!empty($check_media['video'])){
                    update_post_meta($post_id,'qk_circle_video',$check_media['video']);
                }else {
                    delete_post_meta($post_id,'qk_circle_video');
                }
            }
            
            return array('msg' => '发布成功','data'=>self::get_moment_list_item(self::get_moment_data($post_id,$user_id)));
        }
        
        return array('error'=>'发布失败');
    }
    
    //检查发布帖子的权限
    public static function check_insert_moment_role($user_id,$circle_id = 0,$editor = false) {
        $role_data = apply_filters('qk_check_insert_moment_role', $user_id,$circle_id);
        
        if($editor) {
            $media_size = qk_get_option('media_upload_size');
            $media_size = is_array($media_size) ? array_map('intval', $media_size) : array();
            
            $role_data['editor'] = array(
                'toolbar' => self::get_circle_settings($circle_id,'circle_editor_toolbar'),
                'media_size' => $media_size
            );

            $roles = User::get_user_roles();
            foreach ($roles as $key => $value) {
                $role_data['roles'][$key] = $value['name'];
            }
        }
        
        return $role_data;
    }
    
    //检查帖子类型(待做)
    public static function check_moment_type($data,$type_role) {
        return true;
    }
    
    //检查媒体
    public static function check_media($data,$moment_role) {
        $media_role = (array)$moment_role['media_role'];
        $media_count = (array)$moment_role['media_count'];
        
        $args = array('image'=>'图片','video'=>'视频','file'=>'文件','card'=>'卡片');
        
        $attachment = array(
            'image'=>array(),
            'video'=>array(),
            'file'=>array(),
            'card'=>array()
        );
        
        foreach ($args as $key => $value) {
            $files = (array)$data[$key];
            if(!empty($files)) {
                
                if(isset($media_role[$key]) && $media_role[$key] === true) {
                    
                    if(count($files) > $media_count[$key.'_count']) return array('error'=>sprintf('最多允许发布带有%s',$count.$v));
                    
                    foreach ($files as $v) {
                        $post_type = get_post_type((int)$v['id']);
                        $url = !empty($v['url']) ? esc_url(sanitize_text_field($v['url'])) : '';
                        $thumb = !empty($v['thumb']) ? esc_url(sanitize_text_field($v['thumb'])) : '';
                        
                        if($post_type) {
                            if($key == 'video'){
                                $attachment[$key][] = array(
                                   'id' => (int)$v['id'],
                                   'thumb' => $thumb
                                );
                            }else{
                                $attachment[$key][] = array(
                                   'id' => (int)$v['id']
                                );
                            }
                        }elseif($url){
                            
                            if(!$thumb) return array('error'=>sprintf('请设置%s的封面',$value));
                            
                            $attachment[$key][] = array(
                               'id' => 0,
                               'url' => $url,
                               'thumb' => $thumb
                            );
                        }
                    }
                    
                }else{
                    return array('error'=>sprintf('无权发布带有%s的帖子',$value));
                }
            }
            
        }

        return $attachment;
    }
    
    //检查是否帖子隐私权限
    public static function check_privacy_role($data,$privacy_role) {
        if(!isset($data['privacy']['type']) || empty($data['privacy']['type'])) return array('error'=>'请设置帖子的阅读权限');
        
        $privacy_type = $data['privacy']['type'];
        $value = isset($data['privacy']['value']) && is_numeric($data['privacy']['value']) ? (int)$data['privacy']['value'] : 0;
        
        if($privacy_type !== 'none') {
            $title = str_replace(array('{{','}}'),'',$data['title']);
            $title = sanitize_text_field($title);
            
            if(qkGetStrLen($title) < 2) return array('error'=> '请设置一个标题，让用户了解您隐藏的是什么内容！');
        }else{
            return true;
        }
        
        if(!isset($privacy_role[$privacy_type]) || $privacy_role[$privacy_type] !== true) return array('error'=>'你无权发布相关的隐私权限');
        
        if($privacy_type == 'money' || $privacy_type == 'credit') {
            if($value <= 0 || $value > 99999) return array('error'=> '阅读权限，设置的价格错误');
        }
        
        elseif($privacy_type == 'password'){
            if($value <= 1000 || $value > 9999) return array('error'=> '密码阅读，请设置正确的长度为4位的数字');
        }
        
        elseif($privacy_type == 'roles'){
            $roles = User::get_user_roles();
            $_roles = isset($data['privacy']['roles']) && is_array($data['privacy']['roles']) ? (array)$data['privacy']['roles'] : array();
            
            if(empty($_roles)) return array('error'=> '限制等级阅读，请至少设置一个用户组限制');
            
            foreach ($_roles as $value) {
                if(!isset($roles[$value])) return array('error'=>sprintf('不存在%s此用户组',$value));
            }
        }
        
        if(!isset($data['privacy']['content']) || !$data['privacy']['content']) {
             return array('error'=> '请填写你需要隐藏的内容');
        }
        
        return $data['privacy'];
    }
    
    //创建圈子
    public static function create_circle($data){
        $user_id = get_current_user_id();
        if(!$user_id) return array('error'=>'请先登录');
        
        $type = isset($data['type']) ? (int)$data['type'] : '';
        
        //if(!in_array($type,array('topic','circle_cat'))) return array('error'=>'创建类型错误');
        
        //圈子名字
        $circle_name = qk_get_option('circle_name');
        
        $circle_id = isset($data['id']) ? (int)$data['id'] : 0;
        
        $is_edit = !!$circle_id;
        
        
        if($is_edit) {
            $circle = self::is_circle_exists($circle_id);
            if(is_array($circle) && isset($circle['error'])) return $circle;
        }
        
        $role = self::check_insert_moment_role($user_id,$circle_id);
        
        if(empty($role['can_create_circle']) && !$is_edit) return array('error'=>sprintf('您没有权限创建%s',$circle_name));

        if(empty($role['is_circle_staff']) && empty($role['is_admin'])  && $is_edit) return array('error'=>sprintf('您没有权限修改%s',$circle_name));
        
        //获取圈子分类
        $circle_cats = self::get_circle_cats();
        if(isset($circle_cats['error'])) return $circle_cats;
        
        if(!in_array($data['circle_cat'],array_column($circle_cats, 'name'))) return array('error'=>sprintf('请选择%s类别',$circle_name));
        
        //基础资料检查
        if(empty($data['name']) || empty($data['desc']) || empty($data['slug']) || empty($data['cover']) || empty($data['icon'])){
            return array('error'=>sprintf('请完善%s资料',$circle_name));
        }

        $name = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['name'])));
        $desc = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['desc'])));
        $slug = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['slug'])));
        
        if(qkgetStrLen($name) < 2 || qkgetStrLen($name) > 20){
            return array('error'=>sprintf('%s名称必须大于2个字符，小于10个字符',$circle_name));
        }
        
        $name_circle = get_term_by('name',$name, 'circle_cat');
        if($name_circle && $name_circle->term_id !== $circle_id) return array('error'=>sprintf('%s[%s]已被创建，请更换其他名称',$circle_name,$name));
        
        if(!$slug) return array('error'=>sprintf('请填写%s英文网页地址',$circle_name));

        if(mb_strlen($slug,'utf-8') !== strlen($slug)) return array('error'=>'请使用纯英文网页地址');
        
        //不用 wp_update_term 自动检查此别名“square”已被其他项目使用
        $slug_circle = get_term_by('slug',$slug, 'circle_cat');
        if($slug_circle && $slug_circle->term_id !== $circle_id) return array('error'=>sprintf('%s[%s]地址已存在，请更换英文网页地址',$circle_name,$slug));
        
        if(qkgetStrLen($desc) < 10 || qkgetStrLen($desc) > 100){
            return array('error'=>sprintf('%s简介必须大于10个字符，小于100个字符',$circle_name));
        }
        
        $icon = esc_url(sanitize_text_field($data['icon']));
        $cover = esc_url(sanitize_text_field($data['cover']));
        
        if(!attachment_url_to_postid($icon) || !attachment_url_to_postid($cover)) return array('error'=>sprintf('请完善%s图标与背景图',$circle_name));
        
        /********检查开始*********/
        if(!$is_edit || (!empty($role['is_circle_admin']) || !empty($role['is_admin']) && $is_edit)){
            $circle_privacy = self::check_circle_privacy($data);
            if(isset($circle_privacy['error'])) return $circle_privacy;
            
            $circle_layout = isset($data['layout']) && is_array($data['layout']) ? $data['layout'] : array();
            
            $arr = array('global','0','1','pc','mobile','all');
            
            foreach ($circle_layout as $value) {
                if(!in_array($value,$arr)) return array('error'=> '参数非法'); 
            }
            
            $circle_role = isset($data['role']) && is_array($data['role']) ? $data['role'] : array();
            foreach ($circle_role as $value) {
                if(!in_array($value,$arr)) return array('error'=> '参数非法'); 
            }
        }
        
        $args = array(
            'name'        => $name,
            'description' => $desc,
            'slug'        => $slug,
        );
        
        //判断是修改
        if(!empty($circle_id)) {
            $term = wp_update_term($circle_id, 'circle_cat', $args);
        }
        
        else {
            $term = wp_insert_term($name, 'circle_cat', $args);
        }
        
        if(is_wp_error( $term )){
            return array('error'=>$term->get_error_message());
        }
        
        $circle_id = $term['term_id'];
        
        if($circle_id) {
            
            //保存特色图与背景
            update_term_meta($circle_id,'qk_tax_img',$icon);
            update_term_meta($circle_id,'qk_tax_cover',$cover);
            
            //保存圈子分类
            update_term_meta($circle_id,'qk_circle_cat',$data['circle_cat']);

            if((!empty($role['is_circle_admin']) || !empty($role['is_admin']) && $is_edit) || !$is_edit) {
                
                //圈子隐私(帖子是否公开显示)
                update_term_meta($circle_id,'qk_circle_privacy',$circle_privacy['privacy']);
                //圈子加入权限
                update_term_meta($circle_id,'qk_circle_type',$circle_privacy['type']); //权限类型

                if($circle_privacy['type'] == 'password') {
                    update_term_meta($circle_id,'qk_circle_password',$circle_privacy['password']);
                }elseif($circle_privacy['type'] == 'roles') {
                    update_term_meta($circle_id,'qk_circle_roles',$circle_privacy['roles']);
                }elseif(in_array($circle_privacy['type'],array('money','credit'))) {
                    update_term_meta($circle_id,'qk_circle_pay_group',$circle_privacy['pay_group']);
                }
                
                update_term_meta($circle_id,'qk_circle_join_post_open',$circle_role['join_post']);
                
                update_term_meta($circle_id,'qk_circle_info_show',$circle_layout['info_show']);
                
                update_term_meta($circle_id,'qk_circle_input_show',$circle_layout['editor_show']);
            }
            
            if(!$is_edit) {
                
                //保存圈子创建者
                if(CircleRelate::update_data(array(
                    'user_id'=>$user_id,
                    'circle_id'=>$circle_id,
                    'circle_role'=>'admin',
                    'join_date'=>current_time('mysql')
                ))){
                    update_term_meta($circle_id,'qk_circle_admin',$user_id);
                }
            }
            
    
            //圈子板块
            //....
            
            //圈子推荐
            //....
            return true;
        }
        
        return array('error'=>sprintf('%s创建失败',$circle_name));
    }
    
    //检查是否圈子隐私权限
    public static function check_circle_privacy($data) {
        $circle_name = qk_get_option('circle_name');
        
        if(!isset($data['privacy']['type']) || empty($data['privacy']['type'])) return array('error'=>sprintf('请设置%s的权限',$circle_name));
        
        if(!isset($data['privacy']['privacy'])) return array('error'=>sprintf('请设置%s帖子隐私',$circle_name));
        
        $privacy_types = array('free','money','credit','roles','password');
        $privacy_type = $data['privacy']['type'];
        
        if(!in_array($privacy_type,$privacy_types)) return array('error'=>sprintf('%s的权限非法',$circle_name));
        
        if($privacy_type == 'password'){
            
            if(!isset($data['privacy']['password']) || empty($data['privacy']['password']) || (int)$data['privacy']['password'] <= 1000 || (int)$data['privacy']['password'] > 9999) return array('error'=>sprintf( '请设置%s正确的长度为4位的数字密码',$circle_name));
            
        }
        
        elseif($privacy_type == 'money' || $privacy_type == 'credit') {
            if(!isset($data['privacy']['pay_group']) || empty($data['privacy']['pay_group'])) return array('error'=>sprintf('请设置%s支付信息',$circle_name));
            
            $pay_group = is_array($data['privacy']['pay_group']) ? $data['privacy']['pay_group'] : array();
            
            foreach ($pay_group as $key => $value) {
                foreach ($value as $k => $v) {
                    
                    if($k != 'name'){
                       if(!is_numeric($v) || $v < 0 || $v > 9999) return array('error'=>'请填写数字，且最大长度为4位');
                    }else{
                        if(qkgetStrLen($v) < 1 || qkgetStrLen($v) > 10){
                            return array('error'=>'支付信息名称必须大于1个字符，小于10个字符');
                        }
                        
                        $data['privacy']['pay_group'][$key][$k] = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$v)));
                    }
                }
            }
            
        }
        
        elseif($privacy_type == 'roles'){
            $roles = User::get_user_roles();
            $_roles = isset($data['privacy']['roles']) && is_array($data['privacy']['roles']) ? (array)$data['privacy']['roles'] : array();
            if(empty($_roles)) return array('error'=> sprintf('专属%s，请至少设置一个用户组限制',$circle_name));
            
            foreach ($_roles as $value) {
                if(!isset($roles[$value])) return array('error'=>sprintf('不存在%s此用户组',$value));
            }
        }
        
        if($data['privacy']['privacy'] === false) {
            $data['privacy']['privacy'] = 'private';
        }else{
            $data['privacy']['privacy'] = 'public';
        }
        
        return $data['privacy'];
    }
    
    //创建话题
    public static function create_topic($data){
        $user_id = get_current_user_id();
        if(!$user_id) return array('error'=>'请先登录');
        
        $role = self::check_insert_moment_role($user_id,0);
        
        if(empty($role['can_create_topic']) && empty($role['is_admin'])) return array('error'=>sprintf('您没有权限创建%s','话题'));
        
        //基础资料检查
        if(empty($data['name']) || empty($data['desc']) || empty($data['slug']) || empty($data['icon'])){
            return array('error'=>sprintf('请完善%s资料',$circle_name));
        }

        $name = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['name'])));
        $desc = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['desc'])));
        $slug = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',$data['slug'])));
        
        if(qkgetStrLen($name) < 2 || qkgetStrLen($name) > 20){
            return array('error'=>sprintf('%s名称必须大于2个字符，小于10个字符',$circle_name));
        }
        
        if(get_term_by('name',$name, 'topic')) return array('error'=>sprintf('[%s]已被创建，请更换其他名称',$name));
        
        if(!$slug) return array('error'=>sprintf('请填写%s英文网页地址','话题'));

        if(mb_strlen($slug,'utf-8') !== strlen($slug)) return array('error'=>'请使用纯英文网页地址');
        
        //不用 wp_update_term 自动检查此别名“square”已被其他项目使用
        if(get_term_by('slug',$slug, 'topic')) return array('error'=>sprintf('[%s]地址已存在，请更换英文网页地址',$slug));
        
        if(qkgetStrLen($desc) < 10 || qkgetStrLen($desc) > 100){
            return array('error'=>sprintf('%s简介必须大于10个字符，小于100个字符',$circle_name));
        }
        
        $icon = esc_url(sanitize_text_field($data['icon']));
        //$cover = esc_url(sanitize_text_field($data['cover']));
        
        if(!attachment_url_to_postid($icon)) return array('error'=>sprintf('请完善%s图标与背景图','话题'));
        
        $args = array(
            'name'        => $name,
            'description' => $desc,
            'slug'        => $slug,
        );

        $term = wp_insert_term($name, 'topic', $args);
        
        if(is_wp_error( $term )){
            return array('error'=>$term->get_error_message());
        }
        
        $topic_id = $term['term_id'];
        
        if($topic_id) {
            
            //保存特色图与背景
            update_term_meta($topic_id,'qk_tax_img',$icon);
            //update_term_meta($topic_id,'qk_tax_cover',$cover);
            update_term_meta($topic_id,'qk_topic_admin',$user_id);
            return true;
        }
        
        return array('error'=>sprintf('%s创建失败',$circle_name));
    }
    
    //加入圈子
    public static function join_circle($data){
        $user_id = get_current_user_id();
        if(!$user_id) return array('error'=>'请先登录');
        
        $circle_id = $data['circle_id'] = isset($data['circle_id']) ? (int)$data['circle_id'] : 0;
        
        //检查圈子是否存在
        $circle = self::is_circle_exists($circle_id);
        if(is_array($circle) && isset($circle['error'])) return $circle;
        
        $circle_id = (int)$circle_id;
        
        if(self::is_user_joined_circle($user_id,$circle_id)){
            return array('error'=>'您已经加入了，无需再次加入');
        }
        
        if(apply_filters('qk_check_user_join_circle_role',$user_id,$data)) {
            if(CircleRelate::update_data(array(
                'user_id'=>$user_id,
                'circle_id'=>$circle_id,
                'circle_role'=>'member',
                'join_date'=>current_time('mysql')
            ))){
                return 'success';
            }
        }
        
        return array('error'=>'加入失败，您还未获得加入资格');
    }
    
    //获取加入权限
    public static function get_circle_role_data($user_id,$circle_id){
        $data = array(
            'type' => 'free',
            'type_name' => '免费',
            'roles' => array(),
            'pay_group'=> array(),
            'allow' => false
        );
        
        $circle_type = get_term_meta($circle_id,'qk_circle_type',true);
        $data['type'] = $circle_type ?: 'free';
        
        if($data['type'] == 'roles'){
            $roles = (array)get_term_meta($circle_id, 'qk_circle_roles', true);
            
            $lv = User::get_user_lv($user_id);
            $vip = User::get_user_vip($user_id);
            $lvs = array();
            
            if(!empty($lv['lv'])) {
                $lvs[] = 'lv'.$lv['lv'];
            }
            
            if(!empty($vip['lv'])) {
                $lvs[] = $vip['lv'];
            }
            
            if(!empty(array_intersect($roles,$lvs))){
                $data['allow'] = true;
            }
            
            $_roles = User::get_user_roles();
            foreach ($roles as $key => $value) {
                if(isset($_roles[$value])) {
                    $data['roles'][] = array(
                        'lv' => $value,
                        'name' => $_roles[$value]['name'],
                        'image' => $_roles[$value]['image'],
                    );
                }
            }
            
            $data['type_name'] = '专属';
        }
        
        elseif($data['type'] == 'credit' || $data['type'] == 'money'){
            
            $pay_group = (array)get_term_meta($circle_id, 'qk_circle_pay_group', true);
            $data['pay_group'] = (array)$pay_group;
            $data['type_name'] = '付费';
        }else if($data['type'] == 'password'){
            $data['type_name'] = '密码';
        }
        
        return $data;
    }
    
    public static function circle_user_pass($user_id){

        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';

        $res = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE `user_id`=%d AND `circle_key`!=''",$user_id)
        ,ARRAY_A);

        if($res){
            $ids = array();
            foreach ($res as $k => $v) {
                if($v['end_date'] !== '0000-00-00 00:00:00'){
                    if($v['end_date'] < current_time('mysql')){
                        $ids[] = array(
                            'id'=>$v['id'],
                            'circle_id'=>$v['circle_id']
                        );
                    }
                }
            }

            if(!empty($ids)){
                foreach ($ids as $v) {
                    CircleRelate::delete_data(array('circle_id'=>$v['circle_id'],'user_id'=>$user_id));
                }
            }
        }

        return;
    }
    
    //获取某个圈子用户
    public static function get_circle_users($data){
        $paged = isset($data['paged']) ? (int)$data['paged'] : 1;
        $size = isset($data['size']) ? (int)$data['size'] : 10;
        $circle_id = isset($data['circle_id']) ? (int)$data['circle_id'] : 0;
        $type = isset($data['type']) ? $data['type'] : 'staff';
        
        if($size > 20) return array('error'=>'请求数量过多');
        if($paged < 0) return array('error'=>'请求格式错误');
        
        $offset = ($paged -1) * (int)$size;
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';
        
        $where_condition = ($type == 'staff') ? "AND (circle_role = 'admin' OR circle_role = 'staff')" : "";
        
        $count = (int)$wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*) FROM $table_name
                WHERE circle_id = %s $where_condition",
                $circle_id
            )
        );
        
        $res = $wpdb->get_results(
        $wpdb->prepare("
            SELECT * FROM $table_name
            WHERE circle_id = %s $where_condition
            ORDER BY circle_role ASC,join_date ASC LIMIT %d,%d",
            $circle_id,
            $offset,
            $size
        ),ARRAY_A);
        
        $list = array();
        
        foreach ($res as $value) {
            $user = get_user_by( 'ID', $value['user_id']);
            if($user){
                
                $list[] = array_merge(array(
                    // 'id' => $value['user_id'],
                    // 'name' => User::get_user_name_html($value['user_id']),
                    // 'link' => get_author_posts_url($value['user_id']),
                    // 'avatar'=> get_avatar_url($value['user_id'],array('size'=>100)),
                    // 'desc' => get_the_author_meta('description',$value['user_id'])?:'这个人很懒什么都没有留下~',
                    'date' => $value['join_date'],
                    'role' => $value['circle_role'],
                    'is_self' => $value['user_id'] == $user_id,
                    'in_circle' => true
                ),User::get_user_public_data($value['user_id']));
            }
        }
        
        return array(
            'pages' => ceil($count / $size), // 计算总页数
            'count' => $count,
            'list' => $list
            
        );
    }
    
    //用户搜索
    public static function circle_search_users($key,$circle_id){
        
        if(!$key) return array();
        
        $user_id = get_current_user_id();

        $user_query = new \WP_User_Query( array(
            'search'         => '*'.$key.'*',
            'search_columns' => array(
                'display_name',
            ),
            'number' => 20,
            'paged' => 1
        ));

        $results = $user_query->get_results();

        $users = array();

        foreach ($results as $key => $user) {
            $users[] = array_merge(array(
                // 'id' => $user->ID,
                // 'name' => User::get_user_name_html($user->ID),
                // 'link' => get_author_posts_url($user->ID),
                // 'avatar'=> get_avatar_url($user->ID,array('size'=>100)),
                // 'desc' => get_the_author_meta('description',$user->ID)?:'这个人很懒什么都没有留下~',
                'date' => '',
                'role' => self::is_user_circle_staff($user->ID,$circle_id),
                'is_self' => $user->ID == $user_id,
                'in_circle' => self::is_user_joined_circle($user->ID, $circle_id)
            ),User::get_user_public_data($user->ID));;
        }
        
        return $users;
    }
    
    //获取圈子分类
    public static function get_circle_cats(){
        
        $circle_cats = qk_get_option('circle_cats');
        if(empty($circle_cats)){
            $circle_name = qk_get_option('circle_name');
            return array('error'=>sprintf('请设置%s分类',$circle_name));
        }
        
        return $circle_cats;
    }
    
    //获取所有圈子信息
    public static function get_all_circles(){
        
        $circle_cats = self::get_circle_cats();
        if(isset($circle_cats['error'])) return $circle_cats;
        
        $user_id = get_current_user_id();
        
        array_unshift($circle_cats, array('name'=>'我的'));
        
        $data = array(
            'cats' => $circle_cats,
            'list' => array(),
        );
        
        unset($circle_cats[0]);
        foreach ($circle_cats as $v) {
             $args = array(
                'taxonomy' => 'circle_cat',
                'orderby' => 'count',
                //'meta_key' => 'qk_hot_weight',
                'number' => 60,
                //'exclude' => array(get_option('_circle_default')),
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => 'qk_circle_cat',
                        'value' => $v['name'],
                        'compare' => '='
                    ),
                ),
                'hide_empty' => false,
                'cache_domain' => 'qk_circle_cats'
            );
            
            $circles = get_terms($args);

            $circle_data = array();

            if(!empty($circles)){
                foreach ($circles as $k => $_v) {
                    $circle_data[$k] = self::get_circle_data($_v->term_id);
                }
            }
            
            $data['list'][] = array(
                'cat_name'=>$v['name'],
                'list'=>$circle_data
            );
        }
        
        //获取用户的圈子
        $user_circles = self::get_user_circles($user_id);
        $_user_circles = array(
            'cat_name'=> '我的',
            'list'=> array()
        );
        foreach ($user_circles as $value) {
            
            if(!empty($value['list'])) {
                $value['list'][0]['cat_name'] = $value['cat_name'];
            }
            
            $_user_circles['list'] = array_merge($_user_circles['list'],$value['list']);
        }
        
        array_insert($data['list'],0,array($_user_circles));
        
        return $data;
    }
    
    /**
     * 获取某个用户创建的圈子、管理的圈子和加入的圈子
     *
     * @param int $user_id 用户ID
     * @return array 返回包含创建的圈子、管理的圈子和加入的圈子的数组
     */
    public static function get_user_circles($user_id) {
        
        if(!$user_id) return array();
        
        $res = CircleRelate::get_data(array(
            'user_id'=>$user_id,
            //'circle_role'=>'member',
            'count'=> 49
        ));
        
        $ids = array();
        if(!empty($res)) {
            $ids = array_column($res, 'circle_id');
        }

        $circles = get_terms(array(
            'taxonomy' => 'circle_cat',
            'hide_empty' => false,
            'include'=> $ids,
            // 'meta_query' => array(
            //     'relation' => 'OR',
            //     array(
            //         'key' => 'qk_circle_admin',
            //         'value' => $user_id,
            //         'compare' => '=',
            //     ),
            //     array(
            //         'key' => 'qk_circle_staff', 
            //         'value' => ':"' . $user_id . '";', // 使用正则表达式匹配序列化数据中的某个值
            //         'compare' => 'REGEXP',
            //     ),
            //     // array(
            //     //     'key' => 'qk_circle_staff', 
            //     //     'value' => '%i:'. $user_id .';%', // 使用LIKE比较运算符进行模糊匹配
            //     //     'compare' => 'LIKE',
            //     // ),
            // ),
            'orderby' => 'count',
            'order' => 'DESC',
            //'meta_key' => 'qk_hot_weight',
            'cache_domain'=>'qk_circle_cat'
        ));
    
        $created_circles = array();
        $managed_circles = array();
        $joined_circles = array();

        foreach ($circles as $circle) {

            $check = self::is_user_circle_staff($user_id,$circle->term_id);
            $circle_data = self::get_circle_data($circle->term_id);
            
            if($check == 'admin') {
                $created_circles[] = $circle_data;
            }elseif($check == 'staff') {
                $managed_circles[] = $circle_data;
            }else{
                $joined_circles[] = $circle_data;
            }
        }
        //圈子名字
        //$circle_name = qk_get_option('circle_name');

        $result = array(
            'created' => array(
                'cat_name' => '我创建的',
                'list' => $created_circles
            ),
            'managed' => array(
                'cat_name' => '我是版主的',
                'list' => $managed_circles
            ),
            'joined' => array(
                'cat_name' => '我加入的',
                'list' => $joined_circles
            ),
        );
    
        return $result;
    }
    
    /**
     * 获取圈子的创建者和版主信息
     *
     * @param int $circle_id 圈子ID
     * @return array 返回包含圈子创建者和版主信息的数组
     */
    public static function get_circle_admins($circle_id) {
        
        //创建者
        $admin = get_term_meta($circle_id, 'qk_circle_admin', true);
        $admin = !empty($admin) ? (int)$admin : 1;
        $admin_data = User::get_user_public_data($admin);
        
        //版主及工作人员
        $staff = get_term_meta($circle_id, 'qk_circle_staff', true);
        $staff = !empty($staff) && is_array($staff) ? $staff : array();
        $staff_data = array();
        
        if($staff) {
            foreach ($staff as $value) {
                $staff_data[] = User::get_user_public_data($value);
            }
        }
        
        $users = array(
            'admin' => $admin_data,//创建者
            'staff' => $staff_data, //版主及工作人员
        );
        
        return $users;
    }
    
    /**
     * 检查用户是否为圈子的创建者或版主
     *
     * @param int $user_id 用户ID
     * @param int $circle_id 圈子ID
     * @return string|bool 返回'admin'表示用户是圈子的创建者，返回'staff'表示用户是圈子的版主或工作人员，返回false表示用户既不是创建者也不是版主或工作人员
     */
    public static function is_user_circle_staff($user_id,$circle_id) {

        if(!$user_id || !$circle_id) return false;
        
        //创建者
        $admin = get_term_meta($circle_id, 'qk_circle_admin', true);
        $admin = !empty($admin) ? (int)$admin : 1;

        if((int)$user_id === $admin) {
            return 'admin';
        }
        
        //版主及工作人员
        $staff = get_term_meta($circle_id, 'qk_circle_staff', true);
        $staff = !empty($staff) && is_array($staff) ? $staff : array();
        
        if(in_array($user_id,$staff)) {
            return 'staff';
        }
        
        return false;
    }
    
    /**
     * 检查用户是否加入某个圈子
     *
     * @param int $user_id 用户ID
     * @param int $circle_id 圈子ID
     * @return bool 返回true表示用户已加入圈子，返回false表示用户未加入圈子
     */
    public static function is_user_joined_circle($user_id, $circle_id) {
        return apply_filters('qk_is_user_joined_circle',$user_id,$circle_id);
    }
    
    /**
     * 检查圈子是否存在
     *
     * @param int $circle_id 圈子ID
     * @return array 如果圈子存在，返回圈子数据数组；如果圈子不存在，返回错误信息
     */
    public static function is_circle_exists($circle_id) {
        //获取圈子
        $circle = get_term_by('id', $circle_id, 'circle_cat');

        if (!$circle || is_wp_error($circle)) {
            $circle_name = qk_get_option('circle_name');
            return array('error'=>sprintf('%s不存在',$circle_name));
        }
        
        return $circle;
    }
    
    //获取某个圈子信息
    public static function get_circle_data($circle_id){
        global $_GLOBALS;
        $user_id = get_current_user_id();
        
        // 检查 $_GLOBALS 中是否已缓存了圈子数据
        if(isset($_GLOBALS['qk_circle_data'][$circle_id])){
            return $_GLOBALS['qk_circle_data'][$circle_id];
        }

        //有多少个圈子
        $circle_count = wp_count_terms('circle_cat');
        
        $circle = self::is_circle_exists($circle_id);
        
        if(is_array($circle) && isset($circle['error'])) return $circle;
        $original_icon = get_term_meta($circle->term_id,'qk_tax_img',true);
        $icon = qk_get_thumb(array('url'=>$original_icon,'width'=>150,'height'=>150)) ?? '';
        
        $original_cover = get_term_meta($circle->term_id,'qk_tax_cover',true);
        $cover = qk_get_thumb(array('url'=>$original_cover,'width'=>804,'height'=>288)) ?? '';
        
        //圈子管理员及版主
        $admins = self::get_circle_admins($circle->term_id);
        
        //圈子板块（标签）
        $tags = get_term_meta($circle_id, 'qk_circle_tags', true);
        $tags = !empty($tags) && is_array($tags) ? $tags :array();
        
        //获取圈子分类
        $circle_cat = get_term_meta($circle_id,'qk_circle_cat',true);
        
        $is_admin = user_can($user_id, 'administrator' ) || user_can( $user_id, 'editor' );
        
        //是否是管理员或版主
        $is_circle_staff = self::is_user_circle_staff($user_id,$circle->term_id);
        
        //是否需要加入圈子才能发帖
        $join_post_open = !!self::get_circle_settings($circle->term_id,'circle_join_post_open');
        
        $in_circle = self::is_user_joined_circle($user_id,$circle->term_id) || $is_admin || $is_circle_staff;
        
        $circle_data = array(
            'id' => $circle->term_id,
            'name' => esc_attr($circle->name),
            'slug' => $circle->slug,
            'desc' => esc_attr($circle->description),
            'original_icon' => $original_icon,
            'original_cover' => $original_cover,
            'icon' => $icon,
            'cover' => $cover,
            'circle_count' => qk_number_format($circle_count), //圈子数量
            'circle_tags' => $tags, //圈子板块
            'circle_cat' => $circle_cat,
            'circle_badge' => self::get_circle_badge($circle->term_id),
            
            //是否已经加入该圈子
            'in_circle' => $in_circle,
            
            //是否是管理员或版主
            'is_circle_staff'=> $is_circle_staff,
            
            'is_join_circle_post' => $join_post_open,
            
            //用户数
            'user_count' => CircleRelate::get_count(array('circle_id'=>$circle_id)),
            
            //文章数
            'post_count' => qk_number_format($circle->count), //wp_count_posts('circle')->publish 
            
            //浏览量
            'views' => (int)get_term_meta($circle->term_id, 'views', true),
            
            'link'=>get_term_link($circle->term_id),
            //'file_role'=>$file_role,
            //'type'=>get_term_meta($circle->term_id, 'qk_circle_type', true),
        );
        
        $statistics = self::get_circle_or_topic_statistics($circle->term_id);
        
        $mergedArray = array_merge($admins, array_merge($statistics, $circle_data));
        
        $_GLOBALS['qk_circle_data'][$circle_id] = $mergedArray;

        return $mergedArray;
    }
    
    public static function get_manage_circle($circle_id) {
        $user_id = get_current_user_id();
        if(!$user_id) return array('error'=>'请先登录');
        
        $circle = self::is_circle_exists($circle_id);

        if(is_array($circle) && isset($circle['error'])) return $circle;
        
        $circle_staff = self::is_user_circle_staff($user_id,$circle_id);
        
        if($circle_staff !== 'admin' && !user_can( $user_id, 'manage_options' )) return array('error'=>'无权获取设置项');
        
        $join_post = get_term_meta($circle_id,'qk_circle_join_post_open',true);
        $join_post = $join_post ?: 'global';
        
        $info_show = get_term_meta($circle_id,'qk_circle_info_show',true);
        $info_show = $info_show ?: 'global';
        
        $editor_show = get_term_meta($circle_id,'qk_circle_input_show',true);
        $editor_show = $editor_show ?: 'global';
        
        $circle_type = get_term_meta($circle_id,'qk_circle_type',true);
        $circle_type = $circle_type ?: 'free';

        $circle_roles = get_term_meta($circle_id,'qk_circle_roles',true);
        $circle_roles = $circle_roles ?: array();
        
        $pay_group = get_term_meta($circle_id,'qk_circle_pay_group',true);
        $pay_group = $pay_group ?: array();
        
        $password = get_term_meta($circle_id,'qk_circle_password',true);
        
        $privacy = get_term_meta($circle_id,'qk_circle_privacy',true);
        $privacy = !$privacy || $privacy == 'public'? true: false;
        
        return array(
            'privacy' => array(
                'type' => $circle_type,
                'password' => $password,
                'roles' => $circle_roles,
                'pay_group' => $pay_group,
                'privacy' => $privacy
            ),
            'role' => array(
                'join_post' => $join_post
            ),
            'layout' => array(
                'info_show' => $info_show,
                'editor_show' => $editor_show
            )
        );
    }
    
    /**
     * 获取指定分类下的今日评论数、全部评论数和今日发帖数
     *
     * @param int $term_id 分类ID
     * @return array 包含今日评论数、全部评论数和今日发帖数的关联数组
     */
    public static function  get_circle_or_topic_statistics($term_id) {
        global $wpdb;
    
        // 检查是否已经缓存了统计数据
        if (isset($GLOBALS['taxonomy_statistics'][$term_id])) {
            return $GLOBALS['taxonomy_statistics'][$term_id];
        }
        
        // 获取今日评论数
        $daily_comments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_date >= CURDATE() AND comment_post_ID IN (
                SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d
            )",
            $term_id
        ));
    
        // 获取所有评论数
        $all_comments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_post_ID IN (
                SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d
            )",
            $term_id
        ));
    
        // 获取今日发帖数
        $daily_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'circle' AND post_status = 'publish' AND DATE(post_date) = CURDATE() AND ID IN (
                SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d
            )",
            $term_id
        ));
    
        // 缓存统计数据
        $GLOBALS['taxonomy_statistics'][$term_id] = array(
            'today_comment_count' => $daily_comments,
            'comment_count' => $all_comments,
            'today_post_count' => $daily_posts
        );
    
        return $GLOBALS['taxonomy_statistics'][$term_id];
    }
    
    //获取圈子的徽章标签
    public static function get_circle_badge($circle_id) {
        
        $data = array();
        
        $official = get_term_meta($circle_id, 'qk_circle_official', true); 
        
        if($official){
            $data['official'] = array(
                'icon' => 'ri-star-smile-fill',
                'name' => '官方'
            );
        }
        
        $hot_rank = get_term_meta($circle_id, 'qk_hot_rank', true); //热榜
        
        if($hot_rank) {
            $circle_hot = qk_get_option('circle_rank_hot'); 
            $rank_hot = isset($circle_hot['circle_rank_hot']) ? $circle_hot['circle_rank_hot'] : array();
            $before = !empty($rank_hot['before']) ? (int)$rank_hot['before'] : 0;
            $after = !empty($rank_hot['after']) ? (int)$rank_hot['after'] : 0;
            
            $ones = array('一','二','三','四','五','六','七','八','九');
            
            if($hot_rank < $before) {

                $data['rank'] = array(
                    'icon' => 'ri-fire-fill',
                    'name' => str_replace('${1}', $ones[$hot_rank - 1], $rank_hot['before_text'])
                );
            }else if($hot_rank < $before + $after) {
                $data['recom'] = array(
                    'icon' => 'ri-fire-fill',
                    'name' => str_replace('${1}', $hot_rank, $rank_hot['after_text'])
                );
            }
            
        }
        
        $hot = get_term_meta($circle_id, 'qk_hot', true); //热门
        if($hot && !$hot_rank) {
            $data['hot'] = array(
                'icon' => 'ri-fire-fill',
                'name' => '热榜'
            );
        }
        
        return $data;
    }
    
    
    /**
     * 获取用户创建的圈子
     *
     * @param int $user_id 用户ID
     * @return array 返回用户创建的圈子数组，每个圈子包含id、name、description等字段
     */
    function get_user_created_circles($user_id) {
        
    }
    
    /**
     * 获取用户加入的圈子
     *
     * @param int $user_id 用户ID
     * @return array 返回用户加入的圈子数组，每个圈子包含圈子的相关信息
     */
    function get_user_joined_circles($user_id) {
        
    }
    
    /**
     * 获取用户创建的话题
     *
     * @param int $user_id 用户ID
     * @return array 返回用户创建的圈子数组，每个圈子包含id、name、description等字段
     */
    function get_user_created_topic($user_id) {
        
    }
    
    /**
     * 获取话题
     *
     * @param int $user_id 用户ID
     * @return array 返回包含创建的圈子、管理的圈子和加入的圈子的数组
     */
    public static function get_topics($data) {

        $paged = isset($data['paged']) ? (int)$data['paged'] : 1;
        $size = isset($data['size']) ? (int)$data['size'] : 20;
        
        if($size > 20) return array('error'=>'请求数量过多');
        if($paged < 0) return array('error'=>'请求格式错误');
        
        $offest = ($paged - 1)*$size;
        
        /**
         * 'name'：按术语名称排序（默认值）
            'slug'：按术语别名（slug）排序
            'term_group'：按术语分组排序
            'term_id'：按术语ID排序
            'description'：按术语描述排序
            'count'：按术语关联的对象数量排序
         * */
        
        // 构建参数数组
        $args = array(
            'taxonomy' => 'topic', // 自定义分类法的名称
            'orderby' => 'count', // 按照数值进行排序
            'order' => 'DESC', // 降序排列
            'number' => $size,
            'offset' => $offest,
            'hide_empty' => false,
        );
        
        // //创建时间
        // if($args['orderby'] === 'term_id'){
        //     $args['order'] = 'DESC';
        // }
        
        // //权重
        // if($args['orderby'] === 'weight'){
        //     $args['orderby'] = 'meta_value_num';
        //     $args['meta_key'] = 'qk_hot_weight';
        // }
        
        // 获取符合条件的分类
        $topics = get_terms($args);
        
        $data = array();

        if(!empty($topics)){
            foreach ($topics as $k => $v) {
                $data[] = self::get_topic_data($v->term_id);
            }
        }
        
        // 获取总话题数
        $total_terms = wp_count_terms('topic');
        
        return array(
            'pages' => ceil($total_terms / $size), // 计算总页数
            'count' => $total_terms,
            'list' => $data
            
        );
    }
    
    //获取某个话题信息
    public static function get_topic_data($topic_id){
        
        $user_id = get_current_user_id();
        
        //获取圈子
        $topic = get_term_by('id', $topic_id, 'topic');
        
        $icon = qk_get_thumb(array('url'=>get_term_meta($topic->term_id,'qk_tax_img',true),'width'=>150,'height'=>150,'default'=>false)) ?: '';
        $cover = qk_get_thumb(array('url'=>get_term_meta($topic->term_id,'qk_tax_cover',true),'width'=>1200,'height'=>300,'default'=>false)) ?: QK_THEME_URI.'/Assets/fontend/images/topic-header-bg.png';
        
        //创建者
        $admin = get_term_meta($topic_id, 'qk_topic_admin', true);
        $admin = !empty($admin) ? (int)$admin : 1;
        $admin_data = User::get_user_public_data($admin);
        
        $topic_data = array(
            'id' => $topic->term_id,
            'name' => esc_attr($topic->name),
            'desc' => esc_attr($topic->description),
            'icon' => $icon,
            'cover' => $cover,
            //是否是创建者
            'is_topic_admin'=> $admin == $user_id,
            
            //用户数
            'user_count' => 0,//qk_number_format($default == $c->term_id ? User::user_count() : self::user_count_in_circle($c->term_id)),
            
            //文章数
            'post_count' => qk_number_format($topic->count), //wp_count_posts('circle')->publish 
            
            //浏览量
            'views' => get_term_meta($topic->term_id, 'views', true),
            
            'link' => get_term_link($topic->term_id),
            'admin' => $admin_data,
        );

        return $topic_data;
    }
    
    //获取圈子选卡栏
    public static function get_tabbar($tax){
        
        $taxonomy = isset($tax->taxonomy) ? $tax->taxonomy : '';
        $term_id = isset($tax->term_id) ? $tax->term_id : 0;
        
        if(!$term_id) {
            $tabbar = qk_get_option('circle_home_tabbar');
        }else{
            
            if($taxonomy == 'topic'){
                $tabbar = qk_get_option('topic_tabbar');
            }else{
                $tabbar = self::get_circle_settings($term_id,'circle_tabbar');
            }
        }
        
        return !empty($tabbar) ? $tabbar : array();;
    }
    
    //获取圈子默认选项栏目索引
    public static function get_default_tabbar_index($tax){
        
        $taxonomy = isset($tax->taxonomy) ? $tax->taxonomy : '';
        $term_id = isset($tax->term_id) ? $tax->term_id : 0;
        
        if(!$term_id) {
            $index = qk_get_option('circle_home_tabbar_index');
        }else{
            if($taxonomy == 'topic'){
                $index = qk_get_option('topic_tabbar_index');
            }else{
                $index = self::get_circle_settings($term_id,'circle_tabbar_index');
            }
        }
        
        return (int)$index;
    }
    
    public static function get_show_left_sidebar($tax){
        
        $taxonomy = isset($tax->taxonomy) ? $tax->taxonomy : '';
        $term_id = isset($tax->term_id) ? $tax->term_id : 0;
        
        if(!$term_id) {
            $left_sidebar = qk_get_option('circle_home_left_sidebar');
        }else{
            if($taxonomy == 'topic'){
                $left_sidebar = qk_get_option('topic_left_sidebar');
            }else{
                $left_sidebar = self::get_circle_settings($term_id,'circle_left_sidebar');
            }
        }
        
        return !!$left_sidebar;
    }
    
    //获取帖子数据列表
    public static function get_moment_list($data,$html = true){
        
        if(isset($data['tab_type']) && $data['tab_type'] != 'all' && $data['tab_type'] != 'follow') return array(
            'count'=>0,
            'pages'=>0,
            'data'=>[]
        );
        
        if($data['tab_type'] == 'follow'){
            $user_id = get_current_user_id();
            $res = CircleRelate::get_data(array(
                'user_id'=>$user_id,
                'count'=> 49
            ));
            
            $data['circle_cat'] = array_column($res, 'circle_id');
           
            if(empty($data['circle_cat']))return array(
                'count'=>0,
                'pages'=>0,
                'data'=>[]
            );
        }
        
        $circle_id = isset($data['circle_id']) ? (int)$data['circle_id'] : 0;
        //wp_parse_args
        // $args = array(
        //     'paged' => $data['paged'],
        //     'size'  => 5,
        //     'circle_cat' => 0,
        //     'topic' => 0,
        //     'file' => $data['type']
        // );
        
        //获取帖子数据
        $_moment_data = self::get_moments($data);
        
        if(!$html) {
            return $_moment_data;
        }
        
        $moment_data = $_moment_data['data'];
        
        //qkp($moment_data);
        
        $list = [];
        
        foreach ($moment_data as $k => $value) {
            
            // $thumb = qk_get_thumb(array(
            //     'url' => $v['thumb'],
            //     'width' => $size['w'],
            //     'height' => $v['thumb_ratio'] == 0 || !$data['waterfall_show'] ? $size['h'] : '100%',
            //     'ratio' => 2
            // ));

            $list[]= self::get_moment_list_item($value,$data);
            
            
        }
        
        return array(
            'count'=>$_moment_data['count'],
            'pages'=>$_moment_data['pages'],
            'data'=>$list
        );
    }
    
    public static function get_moment_list_item($value,$data = array()){
        
        $author = $value['author'];

        //圈子话题list
        $term_list = self::get_moment_circle_and_topic_list($value);
        
        $image_list = self::get_moment_image_list($value['attachment']['image'],$value['id']);
        
        $video = self::get_moment_video($value,$data);
        
        $meta_list = self::get_moment_meta_list($value);
        
        $content = ShortCode::get_shortcode_content($value['content'],'content_hide');
        
        $content_hide = self::get_moment_content_hide($value['id'],$content['shortcode_content']);
        
        $best = '';
        if($value['best']) {
            $best = '<span class="moment-best"></span>';
        }
        
        $title = '';
        
        if($value['title']) {
            $title = '<h2>'.$best.'<a target="_blank" href="'.$value['link'].'" class="no-hover">'.$value['title'].'</a></h2>';
            $best = '';
        }
        
        $more_menu = self::get_moment_more_menu($value);
        
        $ip_location = $author['ip_location'] !== '未知' && !empty($author['ip_location']) ? ' · 来自'.$author['ip_location'] : '';
        
        $list .= '<section class="moment-item moment-card">
            <div class="moment-card-inner item-in" data-id="'.$value['id'].'">
                <div class="moment-card-header">
                    <div class="moment-avatar"><a target="_blank" href="'.$author['link'].'">'.$author['avatar_html'].'</a></div>
                    <div class="" style=" display: flex; flex-direction: column; justify-content: space-between; flex: 1;">
                        '.$author['name_html'].'
                        <div class="date">'.$value['date'].$ip_location.($value['status'] == 'pending'?'<span style=" color: #F44336; " class="pending"> · 待审核</span>':'').($value['tag'] ? '<span style=" color: var(--theme-color); "> · '.$value['tag'].'</span>':'').'</div>
                    </div>
                    '.$more_menu.'
                </div>
                <!---#moment-header----->
                <div class="moment-card-body">
                    <div class="moment-content">
                        '.$title.'
                        <p class="content">'.$best.$content['content'].'</p>
                        <a target="_blank" href="'.$value['link'].'"></a>
                    </div>
                    '.$image_list.'
                    '.$video.'
                    '.$content_hide.'
                </div>
                <!---#moment-body----->
                <div class="moment-card-footer">
                    '.$term_list.'
                    '.$meta_list.'
                </div>
                <!---#moment-footer----->
            </div>
        </section>';
        
        return $list;
    }
    
    //获取帖子更多菜单
    public static function get_moment_more_menu($moment){
        $user_id = get_current_user_id();
        $manage_role = $moment['manage_role'];
        
        $menu = '<div class="more-menu-box">
            <div class="more-menu-icon">
                <i class="ri-more-2-line"></i>
            </div>
            <ul class="more-menu-list box">';
                
        if(qk_get_option('report_open')) {
            $menu .= '<li @click="report">投诉</li>';
        }
                
        if($manage_role['can_best']) {
            $menu .= '<li @click="setMomentBest">加精</li>';
        }
        
        if($manage_role['can_sticky']) {
            $menu .= '<li @click="setMomentSticky">置顶</li>';
        }
        
        if($manage_role['can_delete']) {
            $menu .= '<li @click="deleteMoment">删除</li>';
        }
        
        if ($manage_role['can_edit']) {
            $menu .= '<li><a href="'.qk_get_custom_page_url('moment').'?id='.$moment['id'].'" target="_blank" style=" width: 100%; ">编辑</a></li>';
        }
        
        if ($manage_role['can_public'] && $moment['status'] == 'pending') {
            $menu .= '<li @click="changeMomentStatus">通过审核</li>';
        }
                
        $menu .= '</ul>
        </div>';
        
        return $menu;
    }
    
    public static function get_moment_content_hide($post_id,$content){
        
        if(!$content) return '';
        
        $user_id = get_current_user_id();
        $role = ShortCode::get_content_hide_arg($post_id,$user_id);
        
        if(!$role || is_array($role)){
            $str = preg_replace('/^<\/p>/', '', $content);
            $str = preg_replace('/<p>$/', '', $str);
        }else{
            $str = '<div class="content-show-roles">'.$role.'</div>';
        }
        
        return '<div class="content-hidden">
            <div class="content-hidden-info">
                '.$str.'
            </div>
        </div>';
    }
    
    public static function get_moment_image_list($image,$post_id){
        
        $imageCount = is_array($image) && !empty($image) ? count($image) : 0;
        
        if(!$imageCount) return '';
        
        //最大列数
        $maxColumn = $imageCount <= 3 ? $imageCount : ($imageCount === 4 ? 2 : 3);
        
        $style = $imageCount > 1 ? ' mode-multiple' : '';
        $style .= $imageCount === 4 ? ' mode-four' : '';
        
        //单图 多图 全屏----长文章里图片是一张显示
        
        $html = '<div class="moment-image-wrap">
            <div class="moment-image-list'.$style.'" >';
        
        foreach ($image as $value) {
            $imageWidth = $value['width'];
            $imageHeight = $value['height'];
            $ratio = round($imageWidth / $imageHeight, 5);
            $padding = '';
            $ratio = '';
            if($imageCount === 1) {
                $imageSize = self::calculateImageSize($imageWidth, $imageHeight);
                $imageWidth = $imageSize['width'];
                $imageHeight = $imageSize['height'];
                $ratio = round($imageHeight/413, 5); //根据父元素最大宽度计算
                
                $padding = 'padding-top:'.($ratio * 100).'%;max-width:'.$imageWidth.'px;';
            }
            
            
            $html .= '<div class="image-item" style="'.$padding.'">
                    <div class="image-item-inner">
                        '.qk_get_img(array('src'=>$value['url'],'class'=>array('img','w-h'),'attribute'=>'data-fancybox="gallery-'.$post_id.'"')).'
                    </div>
                </div>';
        }
        
        
        $html .= '</div>
        </div>';
        
        return $html;
    }
    
    /**
     * 根据指定的最大宽度和高度计算图片的新尺寸
     * @param int $imageWidth 图片的宽度
     * @param int $imageHeight 图片的高度
     * @return array 包含新的宽度和高度的数组
     */
    public static function calculateImageSize($imageWidth, $imageHeight) {
        $maxWidth = 413; // 最大宽度
        $maxHeight = 280;//227; // 最大高度
        $maxRatio = $maxWidth / $maxHeight; // 最大宽高比
        $imageRatio = $imageWidth / $imageHeight; // 图片的宽高比
        
        if ($imageWidth > $maxWidth || $imageHeight > $maxHeight) {
            if ($imageRatio > $maxRatio) {
                $newWidth = $maxWidth;
                $newHeight = $maxWidth / $imageWidth * $imageHeight; // 根据宽度等比缩放高度
            } else {
                $newHeight = $maxHeight;
                $newWidth = $maxHeight / $imageHeight * $imageWidth; // 根据高度等比缩放宽度
            }
        } else {
            $newWidth = $imageWidth; // 图片尺寸在最大尺寸内，直接使用原尺寸
            $newHeight = $imageHeight;
        }
    
        return array(
            'width' => round($newWidth, 5),
            'height' => round($newHeight, 5),
            'ratio' => round($newHeight/$newWidth, 5) //高宽比
        ); 
    }
    
    public static function get_moment_video($moment,$data){
        $video = !empty($moment['attachment']['video']) && is_array($moment['attachment']['video'])  ? $moment['attachment']['video'] : 0;
        
        if(!$video) return '';

        $play_type = isset($data['video_play_type']) ? $data['video_play_type'] : 'click';
        
        $ratio = '';
        if(!empty($video[0]['height']) && !empty($video[0]['width'])){
            if($video[0]['width'] < $video[0]['height']){
                $ratio = round($video[0]['height'] / $video[0]['width'], 5); //高宽比
            }
        }
        
        $video_html = '';
        
        $poster = '
            <div class="video-image">
                <img src="'.$video[0]['poster'].'">
            </div>
            <div class="video-play-btn">
                <div class="play-btn"><i class="ri-play-fill"></i></div>
            </div>
            <div class="video-info">
                <div class="left">'.$video[0]['duration'].'</div>
                <!--<div class="info-right"><i class="ri-play-circle-line"></i>2 万</div>-->
            </div>
        ';
         
        if($play_type !== 'none'){
            $video_html = '<video src="'.$video[0]['url'].'" v-show="videoPlayId == \''.$video[0]['post_id'].'\'" x5-video-player-fullscreen="true" x5-playsinline playsinline webkit-playsinline controls="controls" type="video/mp4"></video>';
            $poster = '
                <transition name="fade">
                    <div class="video-poster" v-show="videoPlayId != \''.$video[0]['post_id'].'\'">
                        '.$poster.'
                    </div>
                </transition>';
        }else{
            $poster = '<a class="video-poster" target="_blank" href="'.$moment['link'].'">'.$poster.'</a>';
        }
        
        return '
            <div class="moment-video-wrap" video-id="'.$video[0]['post_id'].'" '.($ratio ? 'style=" max-width: 274px; "':'').'>
                <div class="video-player-card" '.($ratio ? 'style="padding-bottom:'.($ratio * 100).'%;"':'').'>
                    '.$video_html.'
                    '.$poster.'
                </div>
            </div>
        ';
        
    }
    
    public static function get_moment_meta_list($data){
        
        $icons = array(
            'comment' => array(
                'icon' =>'ri-chat-smile-2-',
                'name' =>'评论',
            ),
            'like' => array(
                'icon' => 'ri-thumb-up-',
                'name' =>'点赞',
            ),
            'collect' => array(
                'icon'=>'ri-star-smile-',
                'name' =>'收藏',
            ),
            'share' => array(
                'icon' => 'ri-share-circle-',
                'name' =>'分享',
            ),
        );
        
        $list  = '<div class="post-meta qk-flex">';
        
        $list .= '<span num=" 浏览" class="views">'.$data['meta']['views'].' </span>';
        
        unset($data['meta']['views']);
        
        foreach ($data['meta'] as $key => $value) {
            
            $list .= '<span num="'.(isset($value['count']) && !empty($value['count']) ? $value['count']: (!is_array($value) && $value ? $value : $icons[$key]['name'])).'" class="'.$key.(isset($value['is']) && !empty($value['is']) ? ' active' :'').'" @click="mataClick(\''.$key.'\')">
                <i class="'.$icons[$key]['icon'].(isset($value['is']) && !empty($value['is']) ? 'fill' : 'line').'"></i>
            </span>';
            
        }
        
        $list .= '</div>';
        
        return $list;
        
    }
    
    public static function get_moment_circle_and_topic_list($data){
        //文章分类
        $html = '';
        if(!empty($data['topics']) || !empty($data['circle'])){
            $html = '<div class="topic-list">';
            
            if(!empty($data['circle'])) {
                $html .= '<a class="topic-item circle" href="'.$data['circle']['link'].'">
                    <i class="ri-donut-chart-line"></i>
                    '.$data['circle']['name'].'
                </a>';
            }
            
            if(!empty($data['topics']) && is_array($data['topics'])) {
                foreach($data['topics'] as $topic){
                    $html .= '<a class="topic-item" href="'.$topic['link'].'">
                        <i class="ri-hashtag"></i>
                        '.$topic['name'].'
                    </a>';
                }
            }
            
            $html .= '</div>';
        }

        return $html;
    }
    
    //获取帖子数据
    public static function get_moment_data($moment_id,$user_id) {
        
        if(get_post_type($moment_id) !== 'circle') return array('error'=>'帖子不存在');
        
        //帖子作者
        $author_id = get_post_field ('post_author', $moment_id);
        
        if(!$author_id) return array();
        
        $author_data = User::get_user_public_data($author_id);
        
        //获取帖子所属圈子
        $circle = get_the_terms($moment_id,'circle_cat');
        if(!empty($circle)){
            $circle = $circle[0];
        }
        
        //话题
        $moment_topics = get_the_terms($moment_id,'topic');
        $topics = array();
        if ($moment_topics && !is_wp_error($moment_topics)) {
            foreach ($moment_topics as $topic) {
                $topic_icon = qk_get_thumb(array('url'=>get_term_meta($topic->term_id,'qk_tax_img',true),'width'=>50,'height'=>50,'default'=>false)) ?: '';
                $topics[] = array(
                    'id' => (int)$topic->term_id,
                    'name' => $topic->name,
                    'link' => get_term_link($topic->term_id),
                    'icon' => $topic_icon
                );
            }
        }
        
        //帖子附件
        $attacment = self::get_moment_attachment($moment_id);
        $title = html_entity_decode(get_the_title($moment_id));
        
        $content = get_post_field('post_content',$moment_id);
        
        $title = strpos($content, str_replace(' ......', '', $title)) !== false ? '' :$title;
        $content = preg_replace("/(\n\s*){1,}/", "<br>",html_entity_decode($content));
        $content = Comment::comment_filters($content);
        
        preg_match_all('/#([^#]+)#/', $content, $_topics);
        if (!empty($_topics[1])) {
            foreach ($_topics[1] as $topic) {
                // 检查话题是否存在
                $term = term_exists($topic,'topic');

                if ($term !== 0 && $term !== null) {
                    // 获取话题的链接
                    $term_link = get_term_link((int)$term['term_id']);
                    // 替换话题为链接
                    $content = str_replace("#$topic#", "<a href=".$term_link."> #$topic# </a>", $content);
                }
            }
        }
        
        //精华
        $best = get_post_meta($moment_id,'qk_circle_best', true);
        
        //置顶 
        $stickys = get_term_meta((int)$circle->term_id,'qk_tax_sticky_posts',true);
        $stickys = !empty($stickys) ? $stickys : array();
        
        $status = get_post_status($moment_id);

        if($status == 'pending'){
            $link = get_permalink($moment_id).'?viewtoken='.md5(AUTH_KEY.$user_id);
        }else{
            $link = get_permalink($moment_id);
        }
        
        //板块（标签）
        $tag = get_post_meta($moment_id,'qk_circle_tag',true);

        return array(
            'id' => (int)$moment_id,
            'date' => qk_time_ago(get_the_date('Y-n-j G:i:s',$moment_id)),
            'title' => $title,
            'content' => $content,
            //'data' => $data,
            'link' => $link,
            'author' => $author_data,
            'attachment' => $attacment,
            'meta'=>array(
                'views' => (int)get_post_meta($moment_id,'views',true),
                'comment' => qk_number_format(get_comments_number($moment_id)),
                'like' => Post::get_post_vote($moment_id),
                'collect' => Post::get_post_favorites($moment_id),
                'share' => 0
            ),
            'circle' => array(
                'id' => (int)$circle->term_id,
                'name' => $circle->name,
                'link' => get_term_link($circle->term_id),
                'icon' => qk_get_thumb(array('url'=>get_term_meta($circle->term_id,'qk_tax_img',true),'width'=>150,'height'=>150)) ?: '',
            ),
            'tag' => $tag,
            'topics' => $topics,
            'status' => $status,
            'status_name' => qk_get_post_status_name($status),
            'best' => !!$best,
            'sticky' => in_array($moment_id,$stickys) ? true : false,
            'manage_role' => apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'post_id'=>$moment_id,'circle_id'=>(int)$circle->term_id)),
        );
    }
    
    //获取帖子附件
    public static function get_moment_attachment($moment_id){
        $attachment = array(
            'image'=>array(),
            'file'=>array(),
            'video'=>array(),
            'card'=>array()
        );
        
        //图片
        $image = get_post_meta($moment_id,'qk_circle_image',true);

        if(!empty($image)){
            foreach ($image as $k => $v) {
                $img_data = wp_get_attachment_metadata($v['id']);

                if($img_data){
                    $full_size = wp_get_attachment_url($v['id']);

                    if(!isset($img_data['width']) || !$img_data['width']){
                        $img_data['width'] = 168;
                    }

                    if(!isset($img_data['height']) || !$img_data['height']){
                        $img_data['height'] = 168;
                    }

                    $w = 200;
                    $h = round(($w*$img_data['height'])/$img_data['width']);

                    if($img_data['width'] <= 200){
                        $w = $img_data['width'];
                        $h = $img_data['height'];
                    }

                    if($h > 200){
                        $h = 200;
                        $w = round(($h*$img_data['width'])/$img_data['height']);
                    }

                    $thumb = qk_get_thumb(array('url'=>$full_size,'width'=>round($w*2),'height'=>round($h*2)));
                    

                    $attachment['image'][] = array(
                        'id' => $v['id'],
                        'ratio'=>round($img_data['height']/$img_data['width'],5),
                        'thumb' => $thumb,
                        'width'=>$img_data['width'],
                        'height'=>$img_data['height'],
                        'url'=>$full_size,
                    );
                }
            }
        }
        
        //视频
        $video = get_post_meta($moment_id,'qk_circle_video',true);

        if(!empty($video)){
            
            foreach ($video as $k => $v) {
                $thumb = '';
                $name = '';
                if(isset($v['id']) && !empty($v['id'])) {
                    $video_data = wp_get_attachment_metadata($v['id']);
                    $url = wp_get_attachment_url($v['id']);
                    $name = get_the_title($v['id']);
                    //获取附件的封面
                    $video_image = wp_get_attachment_image_src( get_post_thumbnail_id( $v['id'] ), 'full' ); // 获取附件的封面图像

                    if($video_image) {
                        $thumb = $video_image[0]; // 封面图像的URL
                    }
                    
                }else{
                    $url = $v['url'];
                    $thumb = $v['thumb'];
                }
                
                $page_width = 600; //自定义
                $page_height = round($w/16*9);

                $attachment['video'][] = array(
                    'id'=>$v['id'],
                    'name' => $name,
                    'post_id' => $moment_id,
                    'width' => $video_data['width'],
                    'height' => $video_data['height'],
                    'url' => $url,
                    'duration'=>isset($video_data['length']) ? gmdate("i:s", $video_data['length']) : 0,
                    'filesize'=>isset($video_data['filesize']) ? $video_data['filesize'] : 0,
                    'mime_type'=>isset($video_data['mime_type']) ? $video_data['mime_type'] : '',
                    'poster'=>$thumb
                );
                
            }
        }
        
        return $attachment;
    }
    
    public static function get_moments($data){
        $paged = isset($data['paged']) ? (int)$data['paged'] : 1;
        $size = isset($data['size']) ? (int)$data['size'] : 10;
        
        $offset = ($paged -1) * (int)$size;
        
        $user_id = get_current_user_id();
        
        $role = self::check_insert_moment_role($user_id,(int)$data['circle_cat']);
        
        if((isset($data['author__in']) && (int)$user_id === (int)$data['author__in'][0]  && (int)$data['author__in'][0] !== 0) || (user_can( $user_id, 'manage_options') && isset($data['author__in']) && (int)$data['author__in'][0])){
            $data['post_status'] = array('publish','pending','draft');
        }
        
        elseif(!empty($role['is_circle_staff']) || !empty($role['is_admin'])){
            
            $data['post_status'] = isset($data['post_status']) && !empty($data['post_status']) ? $data['post_status'] : array('publish','pending');
        }
        
        else{
            $data['post_status'] = array('publish');
        }
        
        $args = array(
            'post_type'=> 'circle',
            'posts_per_page' => $size,
            'orderby'  => 'date', //默认时间降序排序
            'order'=>'DESC',
            'tax_query' => array(
                'relation' => 'AND',
            ),
            'meta_query'=>array(
                'relation' => 'AND',
            ),
            'offset' => $offset,
            'paged'=>$paged,
            'ignore_sticky_posts' => 1,
            'post_status' => $data['post_status'],
            'suppress_filters' => false,
        );
        
        //排序
        if(isset($data['orderby']) && !empty($data['orderby'])){
            switch($data['orderby']){
                case 'random':
                    $args['orderby'] = 'rand'; 
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
                    $args['orderby'] = 'meta_value_num';
                    break;
                case 'comments':
                    $args['orderby'] = 'comment_count';
                    break;
                case 'comment_date': //回复时间排序，需要在发布评论时最后一次评论时间
                    $args['meta_key'] = 'qk_last_comment_date';
                    $args['orderby'] = 'meta_value';
                    break;
            }
        }
        
        //权重排序
        if($query_args['orderby'] === 'weight'){
            
            $args['meta_query']['qk_hot_weight'] = array(
                array(
                    'key' => 'qk_hot_weight'
                )
            );
            
            $args['orderby'] = 'meta_value';
            $args['order'] = array('qk_hot_weight'=>'DESC');
        }
        
        //如果存在用户
        if(isset($data['author__in']) && !empty($data['author__in'])){
            $args['author__in'] = $data['author__in'];
        }
        
        //如果是存在圈子
        if(isset($data['circle_cat']) && !empty($data['circle_cat'])){
            array_push($args['tax_query'],array(
                'taxonomy' => 'circle_cat',
                'field'    => 'id',
                'terms'    => (array)$data['circle_cat'],
                'include_children' => true,
                'operator' => 'IN'
            ));
        }else{
            //如果是首页排除私密圈子
            $excluded_terms = get_terms(array('taxonomy' => 'circle_cat', 'meta_key' => 'qk_circle_privacy', 'meta_value' => 'private', 'fields' => 'ids'));

            array_push($args['tax_query'],array(
                'taxonomy' => 'circle_cat', 
                'field'    => 'id',
                'terms'    => $excluded_terms,
                'operator' => 'NOT IN'
            ));
        }
        
        //如果是存在话题
        if(isset($data['topic']) && !empty($data['topic'])){
            array_push($args['tax_query'],array(
                'taxonomy' => 'topic',
                'field'    => 'id',
                'terms'    => (array)$data['topic'],
                'include_children' => true,
                'operator' => 'IN'
            ));
        }
        
        //如果是视频 图片 文件筛选
        if(isset($data['file']) && !empty($data['file'])){
            $fliter = array('image','video','file','card');
            
            if(!in_array($data['file'],$fliter)){
                return array('error'=>'参数错误');
            }
            
            array_push($args['meta_query'],array(
                'key'     => 'qk_circle_'.$data['file'],
                'compare' => 'EXISTS'
            ));
        }
        
        //如果是文章权限筛选
        if(isset($data['role']) && !empty($data['role'])){
            $fliter = array('none','login','comment','money','credit','roles','fans','password');
            if(!in_array($data['role'],$fliter)){
                return array('error'=>'参数错误');
            }
             array_push($args['meta_query'],array(
                'key'     => 'qk_post_content_hide_role',
                'value'   => $data['role'],
                'compare' => '='
            ));
        }
        
        //如果是板块（标签）筛选
        if(isset($data['tag']) && !empty($data['tag'])){
             array_push($args['meta_query'],array(
                'key'     => 'qk_circle_tag',
                'value'   => $data['tag'],
                'compare' => '='
            ));
        }
        
        //精华帖子筛选
        if(isset($data['best']) && !empty($data['best'])){
             array_push($args['meta_query'],array(
                'key'     => 'qk_circle_best',
                'value'   => 1,
                'compare' => '='
            ));
        }
        
        //搜索
        if(isset($data['search']) && !empty($data['search'])){
            $args['search_tax_query'] = true;
            $args['s'] = esc_attr($data['search']);
        }
        
        $the_query = new \WP_Query( $args );
        
        $arr = array();
        $_pages = 1;
        $_count = 0;
        if ( $the_query->have_posts() ) {

            $_pages = $the_query->max_num_pages;
            $_count = $the_query->found_posts;

            while ( $the_query->have_posts() ) {

                $the_query->the_post();

                $moment_data = self::get_moment_data($the_query->post->ID,$user_id);
                if(!isset($moment_data['error'])){
                    $arr[] = $moment_data;
                }
            }
            wp_reset_postdata();
        }

        return array(
            'count' => $_count,
            'pages' => $_pages,
            'data' => $arr
        );
    }
    
    //根据文章id获取圈子id
    public static function get_circle_id_by_post_id($post_id){
        
        $circle_id = 0;
        
        $circle = get_the_terms($post_id,'circle_cat');
        if(!empty($circle)) {
            $circle_id = (int)$circle[0]->term_id;
        }

        return $circle_id;
    }
    
    //获取编辑帖子数据
    public static function get_edit_moment_data($moment_id) {
        $moment_id = (int)$moment_id;
        if(get_post_type($moment_id) !== 'circle') return array('error'=>'帖子不存在');
        
        $user_id = get_current_user_id();
        
        //获取帖子所属圈子
        $circle_id = self::get_circle_id_by_post_id($moment_id);

        $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'circle_id'=>$circle_id,'post_id' => $moment_id));
        
        if(!$manage_role['can_edit']) return array('error'=>'您无权限修改');
        
        //帖子附件
        $attacment = self::get_moment_attachment($moment_id);
        $images = array();
        foreach ($attacment['image'] as $image) {
            $images[] = array(
                'id'=>$image['id'],
                'url'=>$image['url'],
            );
        }
        
        $videos = array();
        foreach ($attacment['video'] as $video) {
            $videos[] = array(
                'id'=>$video['id'],
                'url'=>$video['url'],
                'name'=>$video['name'],
                'thumbList'=>array(
                    array('url'=>$video['poster'])
                ),
                'progress'=>100,
                'success'=>true,
                'size'=> $video['filesize'],
            );
        }
        
        $title = html_entity_decode(get_the_title($moment_id));
        
        $content = html_entity_decode(get_post_field('post_content',$moment_id));
        $shortcode_content = ShortCode::get_shortcode_content($content,'content_hide');
        $content = !empty($shortcode_content['content']) ? $shortcode_content['content'] : $content;
        $content_hide = !empty($shortcode_content['shortcode_content']) ? $shortcode_content['shortcode_content'] : '';
        
        $role_type = get_post_meta($moment_id,'qk_post_content_hide_role',true);
        $role_value = '';
        if($role_type == 'password') {
            $role_value = get_post_meta($moment_id,'qk_post_password',true);
        }else{
            $role_value = get_post_meta($moment_id,'qk_post_price',true);
        }
        
        $roles = get_post_meta($moment_id,'qk_post_roles',true);
        $roles = !empty($roles) ? $roles : array();
        
        //板块（标签）
        $tag = get_post_meta($moment_id,'qk_circle_tag',true);
        
        return array(
            'id' => (int)$moment_id,
            'title' => $title,
            'content' => $content,
            'circle_id' => $circle_id,
            'tag' => $tag,
            'privacy' => array(
                'type' => $role_type ? $role_type : 'none',
                'value' => $role_value,
                'roles' => $roles,
                'content' => $content_hide
            ),
            'type' => '',
            'image' => $images,
            'video' => $videos,
        );
    }
    
    //帖子加精
    public static function set_moment_best($moment_id){
        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>'请先登录');
        
        //圈子
        $circle_id = self::get_circle_id_by_post_id($moment_id);

        $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'circle_id'=>$circle_id));
        
        if(!$manage_role['can_best']) return array('error'=>'您无权限修改');

        $best = get_post_meta($moment_id, 'qk_circle_best',true);

        $type = true;

        if($best){
            delete_post_meta($moment_id, 'qk_circle_best');
            $type = false;
        }else{
            update_post_meta($moment_id,'qk_circle_best', 1);
        }
        
        return array(
            'message' => $type ? '成功加精' : '取消加精',
            'type' => $type
        );
    }
    
    //帖子置顶
    public static function set_moment_sticky($moment_id){
        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>'请先登录');
        
        //圈子
        $circle_id = self::get_circle_id_by_post_id($moment_id);
        $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'circle_id'=>$circle_id));
        if(!$manage_role['can_sticky']) return array('error'=>'您无权限修改');

        $type = true;

        if ($circle_id) {
            $stickys = get_term_meta($circle_id, 'qk_tax_sticky_posts', true);
            $stickys = is_array($stickys) ? $stickys : array();
            if (in_array($moment_id, $stickys)) {
                $stickys = array_diff($stickys, array($moment_id));
                update_term_meta($circle_id, 'qk_tax_sticky_posts', $stickys);
                $type = false;
            } else {
                $stickys[] = $moment_id;
                update_term_meta($circle_id, 'qk_tax_sticky_posts', $stickys);
            }
        }
        
        return array(
            'message' => $type ? '成功置顶' : '取消置顶',
            'type' => $type
        );
    }
    
    //检查用户是否有删除文章权限
    public static function check_user_can_delete($user_id,$post_id){
        
        $author = (int)get_post_field('post_author', $post_id);
        
        if(user_can($user_id, 'manage_options' )){
            return 'admin';
        } 
        
        if($author !== (int)$user_id) return array('error'=>'没有权限');

        $status = get_post_status($post_id);

        if($status == 'pending') return 'pending';

        if($status == 'draft') return 'draft';
        
        $post_date = get_the_time('Y-n-j G:i:s',$post_id);

        $m = round(( wp_strtotime(current_time( 'mysql' )) - wp_strtotime($post_date)) / 60);

        if(get_post_type($post_id) === 'circle'){
            $edit_time = 30;//话题发布之后多长时间内允许删除或编辑
        }else{
            $edit_time = 30;
        }

        if($m >= $edit_time){
            return array('error'=>sprintf('已过期，无法删除，请联系管理员'));
        }

        return $edit_time - $m;
    }
    
    //删除帖子
    public static function delete_moment($moment_id){
        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>'请先登录');

        $circle_id = self::get_circle_id_by_post_id($moment_id);

        $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'circle_id'=>$circle_id,'post_id' => $moment_id));
        
        if(!$manage_role['can_delete']) return array('error'=>'您无权限删除');
        
        if($manage_role['is_self']) {
            $can_delete = self::check_user_can_delete($user_id,$moment_id);
            if(isset($can_delete['error'])) return $can_delete;
        }
            
        $type = wp_trash_post($moment_id,true) ? true : false;
        
        return array(
            'message' => $type ? '删除成功' : '删除失败',
            'type' => $type
        );
    }
    
    //帖子审核
    public static function change_moment_status($post_id){
        $user_id = get_current_user_id();
        
        if(!$user_id) return array('error'=>'请先登录');

        wp_set_current_user($user_id);

        $circle_id = self::get_circle_id_by_post_id($post_id);

        $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'circle_id'=>$circle_id));
        if(!$manage_role['can_public']) return array('error'=>'您无权限修改');
        // if(get_post_status($post_id) === 'pending'){
        //     $data['status'] = 'publish';
        //     apply_filters( 'insert_ask_action', $data);
        // }

        return wp_update_post( array('ID'=>$post_id,'post_status'=>'publish') );
    }
    
    //移除圈子用户
    public static function remove_circle_user($user_id,$circle_id){
        $current_user_id = get_current_user_id();
        if(!$current_user_id) return array('error'=>'请先登录');
        
        $user = get_user_by('id', $user_id);
        if(empty($user)) return array('error'=>'此用户不存在'); 
        
        //检查圈子是否存在
        $circle = self::is_circle_exists($circle_id);
        if(is_array($circle) && isset($circle['error'])) return $circle;

        $role = self::check_insert_moment_role($current_user_id,$circle_id);
        
        if(empty($role['is_circle_staff']) && empty($role['is_admin'])) return array('error'=>'您没有权限移除此用户');
        
        //是否是管理员或版主
        $is_circle_staff = self::is_user_circle_staff($user_id,$circle_id);
        
        if($is_circle_staff){
            if((!empty($role['is_circle_admin']) || !empty($role['is_admin'])) && $is_circle_staff == 'staff'){
                //版主及工作人员
                $staff = get_term_meta($circle_id, 'qk_circle_staff', true);
                $staff = !empty($staff) && is_array($staff) ? $staff : array();
                $key_staff = array_search($user_id,$staff);
                if($key_staff !== false){
                    unset($staff[$key_staff]);
                    update_term_meta($circle_id,'qk_circle_staff',array_values($staff));
                }
                
                CircleRelate::update_data(array(
                    'user_id'=>$user_id,
                    'circle_id'=>$circle_id,
                    'circle_role'=>'member',
                    'join_date'=>current_time('mysql')
                ));
                
                //钩子
                do_action('qk_remove_circle_staff_action',array(
                    'from' => $current_user_id,
                    'to' => $user_id,
                    'circle_id' => $circle_id
                ));
                
                return array('msg'=>'移除成功！');
                
            }else{
                return array('error'=>'您没有权限移除此用户');
            }
        }else{
            CircleRelate::delete_data(array('circle_id'=>$circle_id,'user_id'=>$user_id));
        }
        
        return array('msg'=>'移除成功！');
    }
    
    //设置版主
    public static function set_user_circle_staff($user_id,$circle_id){
        $current_user_id = get_current_user_id();
        if(!$current_user_id) return array('error'=>'请先登录');
        
        $user = get_user_by('id', $user_id);
        if(empty($user)) return array('error'=>'此用户不存在'); 
        
        //检查圈子是否存在
        $circle = self::is_circle_exists($circle_id);
        if(is_array($circle) && isset($circle['error'])) return $circle;

        $role = self::check_insert_moment_role($current_user_id,$circle_id);
        
        if(empty($role['is_circle_admin']) && empty($role['is_admin'])) return array('error'=>'您没有权限设置版主');
        
        //是否是管理员或版主
        $is_circle_staff = self::is_user_circle_staff($user_id,$circle_id);
        
        if($is_circle_staff)  return array('error'=>'此用户已是版主，无需设置');
        
        //版主及工作人员
        $staff = get_term_meta($circle_id, 'qk_circle_staff', true);
        $staff = !empty($staff) && is_array($staff) ? $staff : array();
        $key_staff = array_search($user_id,$staff);
        
        if($key_staff === false){
            $staff[] = $user_id;
            update_term_meta($circle_id,'qk_circle_staff',array_values($staff));
        }
        
        if(CircleRelate::update_data(array(
            'user_id'=>$user_id,
            'circle_id'=>$circle_id,
            'circle_role'=>'staff',
            'join_date'=>current_time('mysql')
        ))){
            //钩子
            do_action('qk_set_circle_staff_action',array(
                'from' => $current_user_id,
                'to' => $user_id,
                'circle_id' => $circle_id
            ));
            
            return array('msg'=>'设置版主成功！');
        }
        
        return array('msg'=>'设置版主失败！');
    }
    
    //邀请用户加入圈子
    public static function invite_user_join_circle($user_id,$circle_id){
        $current_user_id = get_current_user_id();
        if(!$current_user_id) return array('error'=>'请先登录');
        
        //检查圈子是否存在
        $circle = self::is_circle_exists($circle_id);
        if(is_array($circle) && isset($circle['error'])) return $circle;
        
        $user = get_user_by('id', $user_id);
        if(empty($user)) return array('error'=>'此用户不存在'); 
        
        //检查用户是否已经加入
        if(self::is_user_joined_circle($user_id,$circle_id)) return array('error'=>'此用户已经加入圈子'); 
        
        $role = self::check_insert_moment_role($current_user_id,$circle_id);
        
        if(empty($role['is_circle_admin']) && empty($role['is_admin'])) return array('error'=>'您没有权限邀请');

        if(CircleRelate::update_data(array(
            'user_id'=>$user_id,
            'circle_id'=>$circle_id,
            'circle_role'=>'member',
            'join_date'=>current_time('mysql')
        ))){
            
            do_action('qk_invite_join_circle_action',array(
                'from' => $current_user_id,
                'to' => $user_id,
                'circle_id' => $circle_id
            ));
            
            return array('msg'=>'邀请成功！');
        }
        
        return array('msg'=>'邀请失败！');
    }
}