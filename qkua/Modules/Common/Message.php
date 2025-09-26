<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\User;
use Qk\Modules\Common\Comment;
use Qk\Modules\Common\Post;

/**
 * 消息类型
 * chat -- 聊天
 * wallet -- 钱包通知
 * serve -- 服务通知
 * system -- 系统通知
 * follow -- 粉丝、新粉丝
 * like -- 赞收藏喜欢
 * comment -- 评论回复
 * 
 * //全局接收消息
 * 10000001 所有人
 * 10000002 所有VIP
 * 10000003 网站管理员
 * 
 * */

class Message{

    public function init(){
        
        //出售者通知
        add_action('qk_order_notify_return',array($this,'order_notify_return'),10, 1);
        
        //关注用户与取消关注通知
        add_action('qk_user_follow_action',array($this,'user_follow_action_message'),10,3);
        
        //文章收藏通知
        add_action('qk_post_favorite',array($this,'post_favorite_message'),10,3);
        
        //文章点赞
        add_action('qk_post_vote',array($this,'post_vote_message'),10,3);
        
        //评论通知
        add_action('qk_comment_post',array($this,'comment_post_message'));
        
        //评论点赞
        add_action('qk_comment_vote',array($this,'comment_vote_message'),10,3);
        
        //等级升级通知
        add_action('qk_update_user_lv',array($this,'update_user_lv_message'),10,2);
        
        //会员到期通知
        add_filter('qk_check_user_vip_time',array($this,'check_user_vip_time_message'));
        
        //签到成功通知
        add_action('qk_user_signin',array($this,'user_signin_message'),10,2);
        
        //任务完成通知
        add_action('qk_complete_task_action',array($this,'task_complete_message'),10,2);
        
        //认证通过通知
        add_action('qk_submit_verify_check_success',array($this,'verify_check_success_message'),10,1);
    }
    
    /**
     * 创建消息
     *
     * @param array $message_data 消息数据，包括以下字段：
     *   - sender_id: 发送者ID
     *   - receiver_id: 接收者ID
     *   - title: 消息标题
     *   - content: 消息内容
     *   - type: 消息类型
     *   - post_id: 文章ID（可选，默认为0）
     * 
     * @return int|false 新创建的消息ID，如果创建失败则返回false
     */
    public static function update_message($message_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_message'; 
    
        $content = wp_unslash(wpautop($message_data['content']));
        $content = str_replace(array('{{','}}'),'',$content);
        $content = sanitize_textarea_field($content);
    
        $data = array(
            'sender_id' => isset($message_data['sender_id']) ? $message_data['sender_id'] : 0,
            'receiver_id' => $message_data['receiver_id'],
            'title' => isset($message_data['title']) ? $message_data['title'] : '',
            'content' => $content,
            'type' => $message_data['type'],
            'date' => current_time('mysql'),
            'post_id' => isset($message_data['post_id']) ? $message_data['post_id'] : 0,
            'mark' => isset($message_data['mark']) ? maybe_serialize($message_data['mark']) : ''
        );
    
        $format = array(
            '%d', // sender_id
            '%d', // receiver_id
            '%s', // title
            '%s', // content
            '%s', // type
            '%s', // date
            '%d', // post_id
            '%s'  // mark
        );
        
        if($wpdb->insert($table_name, $data, $format)){

            do_action('qk_message_insert_data',$data);
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    //删除消息
    public function delete_message($args) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_message';
        
        $arr = array();

        if(isset($args['id'])){
            $arr['ID'] = (int)$args['id'];
        }
        
        if(isset($args['sender_id'])){
            $arr['sender_id'] = (int)$args['sender_id'];
        }
        
        if(isset($args['receiver_id'])){
            $arr['receiver_id'] = (int)$args['receiver_id'];
        }
        
        if(isset($args['type']) && $args['type'] !== ''){
            $arr['type'] = $args['type'];
        }

        if(isset($args['post_id'])){
            $arr['post_id'] = (int)$args['post_id'];
        }
        
        if(!$arr) return false;
        
        return $wpdb->delete($table_name, $arr);
    }
    
    public static function send_message($user_id,$content,$attachment_id){
        if(!$user_id){
            return array('error'=>'收件人不可为空');
        }
        
        $current_user_id = get_current_user_id();
        if(!$current_user_id) return array('error'=>'请先登录');
        
        //检查私信发送权限
        //...
        
        if((int)$current_user_id === (int)$user_id){
            return array('error'=>'不能给自己发私信');
        }
        
        if(!get_user_by( 'id', $user_id))return array('error'=>'收件人不存在');
        
        $content = str_replace(array('{{','}}'),'',$content);

        $content = sanitize_textarea_field($content);
        
        $image_data = wp_get_attachment_image_src( (int)$attachment_id, 'full' );
        
        if(empty($content) && !$image_data){
            return array('error'=> '消息不可为空');
        }
        
        if(!trim(strip_tags($content)) && !$image_data){
            return array('error'=> '消息非法');
        }
        
        $data = array(
            'sender_id' => $current_user_id,
            'receiver_id' => (int)$user_id,
            'content' => $content,
            'type' => 'chat',
        );
        
        if($image_data) {
            $data['mark'] = array(
                'id' => $attachment_id,
                'url' => $image_data[0],
                'width' => $image_data[1],
                'height' => $image_data[2],
                'type' => 'image'
            );
        }
        
        if(self::update_message($data)) {
            
            do_action('qk_send_message_action',$data);
            return true;
        }
        
        return false;
        
    }
    
    //获取消息数量
    public static function get_message_count($args){
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_message';
        
        $where = '';
        
        if(isset($args['sender_id']) && $args['sender_id'] !== ''){
            $where .= $wpdb->prepare(' AND `sender_id`=%d',$args['sender_id']);
        }
    
        if(isset($args['receiver_id']) && $args['receiver_id'] !== ''){
            $where .= $wpdb->prepare(' AND (`receiver_id` = %d OR `receiver_id` IN (10000001, 10000002, 10000003))',$args['receiver_id']);
        }
    
        if(isset($args['type'])){
            $where .= $wpdb->prepare(' AND `type`=%s',$args['type']);
        }
    
        if(isset($args['post_id']) && $args['post_id'] !== ''){
            $where .= $wpdb->prepare(' AND `post_id`=%d',$args['post_id']);
        }
    
        if(isset($args['read_by']) && $args['read_by'] !== ''){
            $where .= $wpdb->prepare(' AND (FIND_IN_SET(%d, `read_by`) = 0 OR `read_by` = "")', $args['read_by']);
        }
    
        if(!$where) return 0;
        
    
        $where = substr($where,4);
    
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
        
        return apply_filters('qk_get_message_count',(int)$count,$args);
    }
    
    
    /**
     * 获取未读消息并按类型返回数量
     *
     * @param int $receiver_id 接收者ID
     * @return array 关联数组，以类型为键，数量为值
     */
    public static function get_unread_message_count($receiver_id = 0) {
        
        if(!$receiver_id) {
            $receiver_id = get_current_user_id();
        }
        
        if(!$receiver_id) {
            return;
        }
    
        $results = apply_filters('get_unread_message_count',$receiver_id);
        
        $unread_messages = array_column($results, 'count', 'type');
        
        foreach ($unread_messages as $k => $v) {
            $unread_messages[$k] = intval($v);
        }
        
        $unread_messages['total'] = array_sum($unread_messages);
    
        return $unread_messages;;
    }
    
    
    public static function get_contact_list($paged) {
        $current_user_id = get_current_user_id();

        if(!$current_user_id) return array('error'=>'请先登录');

        $results = apply_filters('get_contact_list_data',$data,$current_user_id);
        
        if(isset($results['error'])) return $results;;
        
        $messageTypes = [
            'vip' => array('name' => '大会员','avatar' => QK_THEME_URI.'/Assets/fontend/images/vip.webp'),
            'wallet' => array('name' => '钱包通知','avatar'=> QK_THEME_URI.'/Assets/fontend/images/wallet.webp'),
            'serve' => array('name' => '服务通知','avatar'=> QK_THEME_URI.'/Assets/fontend/images/serve.webp'),
            'system' => array('name' => '系统通知','avatar'=>QK_THEME_URI.'/Assets/fontend/images/system.webp'),
            'follow' => array('name' => '新粉丝','avatar'=> QK_THEME_URI.'/Assets/fontend/images/follow.webp'),
            'like' => array('name' => '收到的赞','avatar'=> QK_THEME_URI.'/Assets/fontend/images/like.webp'),
            'comment' => array('name' => '互动消息','avatar'=> QK_THEME_URI.'/Assets/fontend/images/comment.webp'),
            'circle' => array('name' => '圈子消息','avatar'=> 'https://www.qkua.com/wp-content/uploads/2023/10/qkua.png'),
            'distribution' => array('name' => '推广返佣','avatar'=> QK_THEME_URI.'/Assets/fontend/images/wallet.webp'),
        ];
        
        $unread = self::get_unread_message_count($current_user_id);

        $data = array();
        if($results && !empty($results)){
            foreach ($results as $k => $v) {
                
                if(!empty($v['mark']) && empty($v['content']) && empty($v['title'])) {
                    $mark = maybe_unserialize($v['mark']);
                    
                    if(is_array($mark) && isset($mark['type']) && $mark['type'] == 'image') {
                        $v['content'] = '[图片]';
                    }
                }
                
                if($v['type'] == 'chat') {
                    
                    if(($v['sender_id'] == $current_user_id && $v['receiver_id'] >= 10000001) || (int)$v['sender_id'] === 0) continue;
                    
                    $userId = $v['sender_id'] != $current_user_id ? $v['sender_id'] : $v['receiver_id'];
                    $userData = User::get_user_public_data($userId, true);

                    $data[] = array(
                        'id' => $v['ID'],
                        'from' => $userData,
                        'date' => self::time_ago($v['date'],true),
                        'content' => $v['content'],
                        'type' => $v['type'],
                        'unread' => self::get_message_count(array('sender_id' => $v['sender_id'] == $current_user_id ? $v['receiver_id'] :$v['sender_id'],'receiver_id' => $current_user_id,'type'=> 'chat','read_by' =>$current_user_id ))
                    );
                }else {
                    if($v['post_id']) {
                        $v['content'] = self::replaceDynamicData($v['content'],array('post'=>get_the_title($v['post_id'])));
                    }
                    
                    $data[] = array(
                        'id' => $v['ID'],
                        'from' => $messageTypes[$v['type']],
                        'date' => self::time_ago($v['date'],true),
                        'content' => $v['content']?:$v['title'],
                        'type' => $v['type'],
                        'unread' => isset($unread[$v['type']]) ? $unread[$v['type']] : 0
                    );
                }
            }
        }
        return $data;
    }
    
    public static function get_contact($user_id) {
        $current_user_id = get_current_user_id();
        $user_id = (int)$user_id;
        if(!$current_user_id) return array('error'=>'请先登录');
        
        if(!$user_id) return  array('error'=>'错误联系人');
        
        if((int)$user_id == $current_user_id) return array('error'=>'不能给自己发私信');
        
        if(!get_user_by( 'id', $user_id))return array('error'=>'收件人不存在');

        $userData = User::get_user_public_data($user_id, true);
        
        $data = array(
            'id' => 0,
            'from' => $userData,
            'date' => self::time_ago(wp_date("Y-m-d H:i:s"),true),
            'content' => '',
            'type' => 'chat',
            'unread' => 0
        );
        
        return $data;
    }
    
    public static function get_message_list($data) {
        $current_user_id = get_current_user_id();

        if(!$current_user_id) return array('error'=>'请先登录');
        
        $_results = apply_filters('get_message_list_data',$data,$current_user_id);
        
        if(isset($_results['error'])) return $_results;
        
        $results = isset($_results['data']) && !empty($_results['data']) ? $_results['data'] : array();
        
        $messageTypes = [
            'vip' => array('name' => '大会员','avatar' => QK_THEME_URI.'/Assets/fontend/images/vip.webp'),
            'wallet' => array('name' => '钱包通知','avatar'=> QK_THEME_URI.'/Assets/fontend/images/wallet.webp'),
            'serve' => array('name' => '服务通知','avatar'=> QK_THEME_URI.'/Assets/fontend/images/serve.webp'),
            'system' => array('name' => '系统通知','avatar'=>QK_THEME_URI.'/Assets/fontend/images/system.webp'),
            'follow' => array('name' => '新粉丝','avatar'=> QK_THEME_URI.'/Assets/fontend/images/follow.webp'),
            'like' => array('name' => '收到的赞','avatar'=> QK_THEME_URI.'/Assets/fontend/images/like.webp'),
            'comment' => array('name' => '互动消息','avatar'=> QK_THEME_URI.'/Assets/fontend/images/comment.webp'),
            'circle' => array('name' => '圈子消息','avatar'=> 'https://www.qkua.com/wp-content/uploads/2023/10/qkua.png'),
            'distribution' => array('name' => '推广返佣','avatar'=> QK_THEME_URI.'/Assets/fontend/images/wallet.webp'),
        ];
        
        
        if(!empty($results)){
            
            if(in_array($data['type'],array('chat','vip','circle','distribution'))) {
                $results = array_reverse($results);
            }
            //将所有消息标为已读
            self::mark_message_as_read($data);
            
            $data = array();
            
            foreach ($results as $k => $v) {
                if($v['type'] == 'chat') {
                    
                    if((int)$v['sender_id'] === 0) continue;
                    
                    $data[] = array(
                        'id' => $v['ID'],
                        'from' => User::get_user_public_data($v['sender_id'], false),
                        'date' => self::time_ago($v['date']),
                        'title' => $v['title'],
                        'content' => Comment::comment_filters($v['content']),
                        'type' => $v['type'],
                        'mark' => maybe_unserialize($v['mark']),
                        'is_self' => $v['sender_id'] == $current_user_id,
                        'time' => wp_strtotime($v['date']),
                        'is_read' => self::check_is_read($v['read_by'],($v['receiver_id'] >= 10000001 ? $current_user_id : $v['receiver_id']))
                    );
                }else {
                    
                    $post = array();
                    
                    if($v['post_id']) {
                        
                        $thumb = qk_get_thumb(array(
                            'url' => Post::get_post_thumb($v['post_id']),
                            'width' => 100,
                            'height' => 100,
                        ));
                        
                        $post = array(
                            'title'=>get_the_title($v['post_id']),
                            'link'=>get_permalink($v['post_id']),
                            'post_type'=>get_post_type($v['post_id']),
                            'thumb' => $thumb
                        );
                        
                        if($v['type'] == 'comment' || $v['type'] == 'like'  && !empty($v['mark'])) {
                            $mark = maybe_unserialize($v['mark']);
                            
                            if(isset($mark[0])) {
                                $comment = get_comment($mark[0]);
                                
                                if($v['type'] == 'comment') {
                                    $v['content'] = Comment::comment_filters($comment->comment_content);
                                }else{
                                    $v['content'] = $v['content'].'<p>'.Comment::comment_filters($comment->comment_content).'</p>';
                                }
                                
                                if(isset($mark[1])) {
                                    $v['content'] = '回复 <a href="'.get_author_posts_url($current_user_id).'">@'.wp_get_current_user()->display_name.'</a> ：'.$v['content'].'<p>'.Comment::comment_filters(get_comment($mark[1])->comment_content).'</p>';
                                }
                            }
                        }//else if($v['type'] == 'serve') {
                            $v['content'] = self::replaceDynamicData($v['content'],array('post'=>'<a href="'.$post['link'].'">'.$post['title'].'</a>'));
                        //}
                    }
                    
                    
                    $data[] = array(
                        'id' => $v['ID'],
                        'from' => $v['sender_id'] ? User::get_user_public_data($v['sender_id'], false) : $messageTypes[$v['type']],
                        'post' => $post,
                        'date' => self::time_ago($v['date']),
                        'title' => $v['title'],
                        'content' => $v['content']?:$v['title'],
                        'type' => $v['type'],
                        'mark' => maybe_unserialize($v['mark']),
                        'time' => wp_strtotime($v['date']),
                        'is_read' => self::check_is_read($v['read_by'],($v['receiver_id'] >= 10000001 ? $current_user_id : $v['receiver_id']))
                    );
                }
            }
            
        }
        
        return array(
            'count' => $_results['count'],
            'pages' => $_results['pages'],
            'data' => $data
        );
    }
    
    /**
     * 替换字符串中的动态数据
     *
     * @param string $string 要处理的字符串
     * @param array $data 包含动态数据的关联数组，${} 中的内容作为键名
     * @return string 替换后的字符串
     */
    public static function replaceDynamicData($string, $data) {
        $pattern = '/\${(.*?)}/'; // 匹配 ${} 格式的正则表达式
        preg_match_all($pattern, $string, $matches); // 查找所有匹配的 ${} 标记
    
        foreach ($matches[1] as $match) {
            if (isset($data[$match]) && !empty($data[$match])) {
                $string = str_replace('${'.$match.'}', $data[$match], $string); // 替换动态数据
            }
        }
    
        return $string;
    }
    
    /**
     * 检查给定的用户ID是否为未读用户
     *
     * @param string $userIds 包含用户ID的字符串，以逗号分隔
     * @param int $targetId 要检查的目标用户ID
     * @return bool 返回一个布尔值，表示目标用户ID是否为未读用户
     */
    public static function check_is_read($userIds, $targetId) {

        $userIdsArray = explode(',', $userIds);
        $userIdsArray = array_map('intval', $userIdsArray);
        return in_array($targetId, $userIdsArray);
    }
    
    //将消息标记为已读
    public static function mark_message_as_read($data) {
        
        $current_user_id = get_current_user_id();
        
        $as_read = apply_filters('mark_message_as_read',$data,$current_user_id);
        
        return false;
    }
    
    /**
     * 转换时间格式
     *
     * @param int $ptime 'Y-m-d H:i:s'
     *
     */
    public static function time_ago($time, $date_only = false) {
        if (!is_string($time)) return;
    
        $current_time = current_time('timestamp');
        $time_diff = $current_time - wp_strtotime($time);
        
        if ($time_diff < 1) {
            $output = '刚刚';
        } 
        
        elseif ($time_diff <= 84600) {
            $output = wp_date('H:i', wp_strtotime($time));
        }
        
        elseif ($time_diff <= 172800) {
            $output = sprintf('昨天 %s', wp_date('H:i', wp_strtotime($time)));
        } 
        
        else {
            $date_format = ($date_only || wp_date('y', wp_strtotime($time)) == wp_date('y')) ? 'n-d' : 'y-m-d';
            $time_format = $date_only ? '' : ' H:i';
            $output = wp_date($date_format . $time_format, wp_strtotime($time));
        }
        
        return '<time class="qk-timeago" datetime="' . $time . '" itemprop="datePublished">' . $output . '</time>';
    }
    
    public function order_notify_return($data) {
        //作者id
        $author_id = get_post_field('post_author', $data['post_id']);
        
        if($data['order_type'] == 'vip_goumai' && $data['user_id']) {
            
            $user_vip_exp_date = get_user_meta($data['user_id'],'qk_vip_exp_date',true);
            $vip_date = (string)$user_vip_exp_date === '0' ? '永久' : wp_date('Y-m-d',$user_vip_exp_date);
            
            $roles = User::get_user_roles();
            $vip = $roles[$data['order_key']]['name'];
            
            $message_data = array(
                'sender_id' => 0,
                'receiver_id' => $data['user_id'],
                'title' => $vip.'服务开通成功通知',
                'content' => '恭喜您已开通'.((int)$data['order_value'] == 0 ? '永久' : $data['order_value'].'天').$vip.'服务，目前有效期至'.((int)$data['order_value'] == 0 ? '永久' : $vip_date).'。',
                'type' => 'vip',
                'mark' => array(
                    'meta' => array(
                        array(
                            'key'=> '开通类型',
                            'value'=> $data['pay_type'] == 'card' ? '卡密兑换' : '充值开通', //系统发放、充值开通 、活动赠送、会员领取、卡密兑换
                        ),
                        array(
                            'key'=> '支付金额',
                            'value'=> $data['order_total'],
                        ),
                        array(
                            'key'=> '当前状态',
                            'value'=> $vip,
                        )
                    )
                    
                )
            );
            
            if($data['pay_type'] == 'card') {
                unset($message_data['mark']['meta'][1]);
            }
            
            self::update_message($message_data);
            
        }else if ($data['order_type'] == 'money_chongzhi' || $data['order_type'] == 'credit_chongzhi') {
            
            $title = $data['order_type'] == 'money_chongzhi' ? '余额充值到账' : '积分充值到账';

            if($data['pay_type'] == 'card') {
                $content = $data['order_type'] == 'money_chongzhi' ? sprintf('您已成功使用卡密兑换余额已到账：%s元', $data['order_total']) : sprintf('您已成功使用卡密兑换积分已到账：%s积分', $data['order_total']);
            } else {
                $content = $data['order_type'] == 'money_chongzhi' ? sprintf('余额充值已到账：%s元', $data['order_total']) : sprintf('您使用 ￥%s 购买积分已到账：%s积分', $data['order_total'], $data['order_value']);
            }
            
            self::update_message(array(
                'sender_id' => 0,
                'receiver_id' => $data['user_id'],
                'title' => $data['pay_type'] == 'card' ? '卡密兑换到账' : $title,
                'content' => $content,
                'type' => 'wallet',
            ));
            
        }else if ($data['order_type'] == 'join_circle') {
            
            $end_date = current_time('mysql');
            $join_data = \Qk\Modules\Common\CircleRelate::get_data(array(
                'user_id'=>(int)$data['user_id'],
                'circle_id'=>(int)$data['post_id'],
            ));
            
            if(!empty($join_data[0])){
                
                if($join_data[0]['end_date'] == '0000-00-00 00:00:00') {
                    $end_date = '永久';
                }else{
                    $end_date = $join_data[0]['end_date'];
                }
            }
            
            $circle = get_term_by('id',(int)$data['post_id'], 'circle_cat');
            
            $message_data = array(
                'sender_id' => 0,
                'receiver_id' => $data['user_id'],
                'title' => $circle->name.'加入成功通知',
                'content' => '恭喜您已加入 '.$circle->name.' ，服务有效期至'.$end_date.'。',
                'type' => 'circle',
                'mark' => array(
                    'meta' => array(
                        array(
                            'key'=> '付费类型',
                            'value'=> $data['pay_type'] !== 'credit' ? '金额' : '积分', //系统发放、充值开通 、活动赠送、会员领取、卡密兑换
                        ),
                        array(
                            'key'=> '支付'.($data['pay_type'] !== 'credit' ? '金额' : '积分'),
                            'value'=> $data['order_total'],
                        )
                    )
                    
                )
            );
            
            self::update_message($message_data);
            
        //认证
        }else if ($data['order_type'] == 'verify') {
            
            
        }else {
            
            $array = array(
                'product'=>array(
                    'title'=>'产品出售',
                    'type_text'=>'产品',
                ),
                'video'=>array(
                    'title'=>'视频出售',
                    'type_text'=>'视频',
                ),
                'xiazai'=>array(
                    'title'=>'下载资源出售',
                    'type_text'=>'下载资源',
                ),
                'post_neigou'=>array(
                    'title'=>'隐藏内容出售',
                    'type_text'=>'隐藏内容',
                ),
            );
            
            //不给自己发送消息
            if($data['user_id'] != $author_id) {
                //给商家发送消息
                self::update_message(array(
                    'sender_id' => $data['user_id'],
                    'receiver_id'=> $author_id,
                    'title' => $array[$data['order_type']]['title'],
                    'content' => sprintf('购买了您的%s：${post}',$array[$data['order_type']]['type_text']),
                    'type' => 'serve',
                    'post_id' => !empty($data['chapter_id']) ? $data['chapter_id'] : $data['post_id'],
                ));
            }
            
            do_action('send_author_message_after', $author_id,$data,$array);
            
            if($data['user_id']) {
                //给当前用户发送消息
                self::update_message(array(
                    'sender_id' => 0,
                    'receiver_id'=> $data['user_id'],
                    'title' => '购买成功通知',
                    'content' => sprintf('您成功购买%s：${post}',$array[$data['order_type']]['type_text']),
                    'type' => 'serve',
                    'post_id' => !empty($data['chapter_id']) ? $data['chapter_id'] : $data['post_id'],
                ));
            }
        }
        
        return apply_filters('qk_order_notify_return_success',$data);
    }
    
    //关注用户与取消关注通知
    public function user_follow_action_message($user_id, $current_user_id, $success) {
        
        //如果是自己则不发送消息
        if($user_id == $current_user_id) return;
        
        if($success) {
            self::update_message(array(
                'sender_id' => $current_user_id,
                'receiver_id' => $user_id,
                'title' => '关注通知',
                'content' => '关注了你',
                'type' => 'follow',
            ));
        }else {
            self::delete_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $user_id,
                'type' => 'follow',
            ));
        }
    }
    
    //收藏通知
    public function post_favorite_message($post_id,$current_user_id,$success) {
        //获取文章作者id
        $author_id = get_post_field('post_author', $post_id);
        
        //如果是自己则不发送消息
        if($author_id == $current_user_id) return;
        
        if($success) {
            self::update_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $author_id,
                'title' => '收藏通知',
                'content' => '收藏了你的文章',
                'type' => 'like',
                'post_id' => $post_id,
            ));
        }else {
            self::delete_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $author_id,
                'type' => 'like',
                'post_id' => $post_id,
            ));
        }
    }
    
    //文章点赞通知
    public function post_vote_message($post_id,$current_user_id,$success) {
        //获取文章作者id
        $author_id = get_post_field('post_author', $post_id);
        
        //如果是自己则不发送消息
        if($author_id == $current_user_id) return;
        
        if($success) {
            self::update_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $author_id,
                'title' => '文章点赞',
                'content' => '给你的文章点了赞',
                'type' => 'like',
                'post_id' => $post_id,
            ));
        }else {
            self::delete_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $author_id,
                'type' => 'like',
                'post_id' => $post_id,
            ));
        }
    }
    
    //评论通知
    public function comment_post_message($comment) {
        
        //给文章作者发送通知
        if(isset($comment['is_self']) && !$comment['is_self'] && empty($comment['parent_comment_author'])) {
            self::update_message(array(
                'sender_id' => $comment['comment_author'],
                'receiver_id'=> $comment['post_author'],
                'title' => '评论了你的文章',
                'content' => '',
                'type' => 'comment',
                'post_id' => $comment['post_id'],
                'mark' => array((int)$comment['comment_id'])
            ));
        }
        
        //给被回复的评论发送消息，被回复的不是自己
        if($comment['parent_comment_author'] && $comment['parent_comment_author'] != $comment['comment_author']) {
            self::update_message(array(
                'sender_id' => $comment['comment_author'],
                'receiver_id'=> $comment['parent_comment_author'],
                'title' => '回复了你的评论',
                'content' => '',
                'type' => 'comment',
                'post_id' => $comment['post_id'],
                'mark' => array((int)$comment['comment_id'],(int)$comment['parent_comment_id'])
            ));
        }
    }
    
    //评论点赞通知
    public function comment_vote_message($comment_id,$current_user_id,$success) {
        //获取评论作者id
        $comment = get_comment($comment_id);
        $comment_author = $comment->user_id ? (int)$comment->user_id : (string)$comment->comment_author;
        
        //如果是自己则不发送消息
        if($comment_author == $current_user_id) return;
        
        if($success) {
            self::update_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $comment_author,
                'title' => '评论点赞',
                'content' => '给你的评论点了赞',
                'type' => 'like',
                'post_id' => $comment->comment_post_ID,
                'mark' => array($comment_id)
            ));
        }else {
            self::delete_message(array(
                'sender_id' => $current_user_id,
                'receiver_id'=> $comment_author,
                'type' => 'like',
                'post_id' => $comment->comment_post_ID,
            ));
        }
    }
    
    //等级升级通知
    public function update_user_lv_message($user_id,$lv) {
        // self::update_message(array(
        //     'receiver_id'=> $user_id,
        //     'title' => '等级升级了',
        //     'content' => $lv,
        //     'type' => 'comment_to_post',
        // ));
    }
    
    //会员到期通知(暂无功能)
    public function check_user_vip_time_message($vip) {
        // self::update_message(array(
        //     'sender_id' => 0,
        //     'receiver_id' => $data['user_id'],
        //     'title' => '超级大会员服务到期提醒',
        //     'content' => '您的超级大会员服务还有3天将到期，立即续费，继续享受大会员服务！  ',
        //     'type' => 'follow',
        // ));
        
        // self::update_message(array(
        //     'sender_id' => 0,
        //     'receiver_id' => $data['user_id'],
        //     'title' => '超级大会员服务过期通知',
        //     'content' => '您的超级大会员服务已经过期，快回来续费恢复超级大会员特权，番剧国创的快乐不能停！ ',
        //     'type' => 'follow',
        // ));
        
        return $vip;
    }
    
    public static function generate_vip_message($user_id,$vip,$day,$type) {
        $user_vip = get_user_meta($user_id,'qk_vip',true);
        $user_vip_exp_date = get_user_meta($user_id,'qk_vip_exp_date',true);
        $vip_date = (string)$user_vip_exp_date === '0' ? '永久' : wp_date('Y-m-d',$user_vip_exp_date + 86400 * $day);
        
        $roles = User::get_user_roles();
        $vip_name = !$user_vip ? $roles[$vip]['name'] : $roles[$user_vip]['name'];
        
        $message_data = array(
            'sender_id' => 0,
            'receiver_id' => $user_id,
            'title' => $vip_name.'服务开通成功通知',
            'content' => '恭喜您已开通'.$day.'天'.$vip_name.'服务，目前有效期至'.$vip_date.'。',
            'type' => 'vip',
            'mark' => array(
                'meta' => array(
                    array(
                        'key'=> '开通类型',
                        'value'=> $type, //系统发放、充值开通 、活动赠送、会员领取、卡密兑换
                    ),
                    array(
                        'key'=> '当前状态',
                        'value'=> $vip_name,
                    )
                )
                
            )
        );
        
        self::update_message($message_data);
    }
    
    //签到奖励会员通知
    public function user_signin_message($user_id,$bonus) {
        
        if(!isset($bonus['vip']) || empty($bonus['vip'])) return;
        $bonus_vip = $bonus['vip'];
        
        if(empty($bonus_vip['day']) || $bonus_vip['day'] == '0') return;
        
        self::generate_vip_message($user_id,$bonus_vip['vip'],$bonus_vip['day'],'签到赠送');
    }
    
    //任务完成通知
    public function task_complete_message($user_id,$task) {
        $task_bonus = isset($task['task_bonus']) && is_array($task['task_bonus']) ? $task['task_bonus'] : array();
        if(!$task_bonus) return;
        
        $bonus_vip = null;
        foreach ($task_bonus as $value) {
            if (strpos($value['key'], 'vip') !== false && $value['value'] != '0') {
                $bonus_vip = $value;
                break;
            }
        }
        
        if (!$bonus_vip) return;
        
        self::generate_vip_message($user_id,$bonus_vip['key'],$bonus_vip['value'],'任务活动');
    }
    
    //认证通过通知
    public function verify_check_success_message($user_id) {
        self::update_message(array(
            'sender_id' => 0,
            'receiver_id' => (int)$user_id,
            'title' => '认证服务',
            'content' => '您的认证申请已通过审核，您已拥有唯一身份标识，可在个人主页查看。',
            'type' => 'system',
        ));
    }
}