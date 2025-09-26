<?php namespace Qk\Modules\Common;

use Qk\Modules\Common\User;

/*
* 商城订单项
* $order_type //订单类型
* choujiang : 抽奖 ，duihuan : 兑换 ，goumai : 购买 ，post_neigou : 文章内购 ，dashang : 打赏 ，xiazai : 资源下载 ，money_chongzhi : 余额充值 ，vip_goumai : VIP购买 ,credit_chongzhi : 积分购买,
* video : 视频购买,verify : 认证付费,mission : 签到填坑 , coupon : 优惠劵订单,join_circle : 支付入圈 
*
* $order_commodity //商品类型
* 0 : 虚拟物品 ，1 : 实物
*
* $order_state //订单状态
* 0 : 等待付款 ，1 : 已付款未发货 ，2 : 已发货 ，3 : 已签收 ，4 : 已退款，5 : 已删除 
*/
class Orders{
    public function init(){ 
        
        //支付成功回调
        //add_filter( 'qk_order_notify_return', array(__CLASS__,'order_notify_return'),5,1);
        add_action( 'qk_order_notify_return', array(__CLASS__,'order_notify_return'),5,1);
    }
    
    //生成订单号
    public static function build_order_no() {
        $year_code = array('A','B','C','D','E','F','G','H','I','J');
        $order_number = $year_code[intval(date('Y'))-2020] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $order_number;
    }
    
    //创建订单
    public static function build_order($data){
        $user_id = get_current_user_id();
        
        //游客购买
        $can_not_login_pay = apply_filters('qk_can_not_login_buy', $data);

        if(!$user_id && !(int)$can_not_login_pay) return array('error'=>__('请先登录','qk'));
        
        // 获取已创建的订单
        // $order_id = isset($data['order_id']) ? trim($data['order_id'], " \t\n\r\0\x0B\xC2\xA0") : '';
        
        // if($order_id) {
        //     //支付已有订单
        //     return self::pay_existing_order($order_id);
        // }
        
        $data['order_type'] = isset($data['order_type']) ? trim($data['order_type'], " \t\n\r\0\x0B\xC2\xA0") : '';
        $data['pay_type'] =  isset($data['pay_type']) ? trim($data['pay_type'], " \t\n\r\0\x0B\xC2\xA0") : '';
        $data['order_count'] = isset($data['order_count']) ? (int)$data['order_count'] : 1;
        $data['user_id'] = $user_id;
        
        $order_type = self::get_order_type();

        if(empty($data['order_type']) || !isset($order_type[$data['order_type']])) return array('error'=>__('订单类型错误','qk'));
        
        if(!$data['pay_type'] || isset($data['_pay_type'])) return array('error'=>__('订单类型错误','qk'));
        
        if($data['order_count'] < 1) return array('error'=>__('请选择购买至少一个商品！','qk'));
        
        if(isset($data['order_price']) && $data['order_price'] < 0) return array('error'=>__('订单金额错误！','qk'));

        $data = self::build_order_action($data);

        if(is_array($data)){
            //选择支付平台
            return Pay::pay($data);
        }else{
            return $data;
        }
    }
    
    //订单操作
    public static function build_order_action($data){
        $user_id = get_current_user_id();
        $data['post_id'] = isset($data['post_id']) ? (int)$data['post_id'] : 0;
        
        //章节id
        $data['chapter_id'] = isset($data['chapter_id']) ? (int)$data['chapter_id'] : 0;
        
        //付款方式  'alipay' 'wechat';
        $data['payment_method'] = $data['pay_type'];
        
        //判断支付类型
        $pay_type = Pay::pay_type($data['pay_type']);
        if(isset($pay_type['error'])) return $pay_type;
        $data['pay_type'] = $pay_type['type'];
        
        //订单号
        $order_id = self::build_order_no();
        $data['order_id'] = $order_id;
        
        //检查支付金额
        $order_price = apply_filters('qk_order_price', $data); 

        if(isset($order_price['error']) || is_array($order_price)) return $order_price;
        
        //订单金额
        $data['order_price'] = $order_price;
        
        if($data['order_price'] < 0 && $data['order_type'] !== 'coupon') return array('error'=>__('订单总金额错误','qk'));
        
        //如果是合并支付
        if($data['order_type'] === 'g' || $data['order_type'] === 'coupon'){
            $total = $order_price;
        }else{
            //订单价格和订单数量相乘，得到订单的总价格。其中，`bcmul()` 是一个 PHP 函数，用于对两个高精度数字进行乘法运算。
            $total = bcmul($data['order_price'],$data['order_count'],2);
        }

        //检查总金额
        if(isset($data['order_total']) && (float)$data['order_total'] !== (float)$total){
            return array('error'=>__('订单总金额错误','qk'));
        }
        
        $data['order_total'] = $total;
        
        //标题
        if(isset($data['title'])){
            $data['title'] = qk_get_desc(0,30,urldecode($data['title']));
        }
        
        //金额类型
        $data['money_type'] = $data['pay_type'] == 'credit' ? 1 : 0;
        
        //检查是虚拟物品还是实物
        $data['order_commodity'] = 0; //0虚拟，1实物， 待处理
        
        //order_key
        $data['order_key'] = isset($data['order_key']) ? esc_sql(str_replace(array('{{','}}'),'',sanitize_text_field($data['order_key']))) : '';

        $data['order_value'] = isset($data['order_value']) && $data['order_value'] != '' ? urldecode($data['order_value']) : '';

        $data['order_value'] = esc_sql(str_replace(array('{{','}}'),'',sanitize_text_field($data['order_value'])));
        
        //order_content
        $data['order_content'] = isset($data['order_content']) ? urldecode($data['order_content']) : '';
        $data['order_content'] = isset($data['order_content']) && $data['order_content'] != '' ? esc_sql(str_replace(array('{{','}}'),'',sanitize_text_field($data['order_content']))) : '';

        $data['order_address'] = isset($data['order_address']) ? esc_sql(str_replace(array('{{','}}'),'',sanitize_text_field($data['order_address']))) : '';
        
        //过滤钩子
        $data = apply_filters('qk_order_build_before', $data);

        $check_data = serialize($data);

        if(strlen($check_data) > 50000) return array('error'=>__('非法操作','qk'));
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id'],
                'post_id'=>$data['post_id'],
                'chapter_id'=>$data['chapter_id'],
                'order_type'=>$data['order_type'],
                'order_commodity'=>$data['order_commodity'],
                'order_state'=> 0, //等待付款
                'order_date'=>current_time('mysql'),
                'order_count'=>$data['order_count'],
                'order_price'=>$data['order_price'],
                'order_total'=>$data['order_total'],
                'money_type'=>$data['money_type'],
                'order_key'=>$data['order_key'],
                'order_value'=>$data['order_value'],
                'order_content'=>$data['order_content'],
                'pay_type'=>$data['pay_type'],
                'tracking_number'=>'',
                'order_address'=>'', //待处理
                'ip_address'=>qk_get_user_ip(),
                'order_mobile'=>isset($data['order_mobile']) ? $data['order_mobile'] : '',
            ),
            array(
                '%s',
                '%d',
                '%d',
                '%d',
                '%s',
                '%d',
                '%d',
                '%s',
                '%d',
                '%f',
                '%f',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
        
        //过滤钩子
        return apply_filters('qk_order_build_after',$data,$wpdb->insert_id);
    }
    
    //支付现有订单站未实现
    // public static function pay_existing_order($order_id){
    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'qk_order';

    //     //获取订单数据
    //     $order = $wpdb->get_row(
    //         $wpdb->prepare("
    //             SELECT * FROM $table_name
    //             WHERE order_id = %s
    //             ",
    //             $order_id
    //         )
    //     ,ARRAY_A);
        
    //     if(empty($order)) return array('error'=>__('支付信息错误','qk'));
        
    //     // 判断是否等待支付
    //     if((int)$order['order_state'] !== 0) return array('error'=>__('订单已支付','qk'));
        
    //     return Pay::pay($data);
    // }
    
    /**
     * 删除订单
     * @param int $user_id 用户ID
     * @param int $order_id 订单ID
     * @return bool 删除成功返回true，未找到订单或没有权限删除订单返回false
     */
    public static function delete_order($user_id, $order_id) {
        $user_id = (int)$user_id;
        $current_user_id = (int)get_current_user_id();
        
        if(!$user_id && !$current_user_id){
            return array('error'=>'请先登录');
        }
        
        if($user_id !== $current_user_id && !user_can($current_user_id, 'administrator' )) return array('error'=>'权限不足');
        
        $user_id = $current_user_id;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';
    
        $order = $wpdb->get_row($wpdb->prepare("SELECT user_id, order_state FROM $table_name WHERE user_id = %d AND order_id = %s", $user_id,$order_id),ARRAY_A);
        
        if(empty($order)){
            return array('error'=>'没有找到这个订单');
        }
        
        //如果已经支付成功，直接返回
        if((int)$order['order_state'] === 0) {
            if($wpdb->delete(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'order_id' => $order_id
                )
            )) {
                return true; // 成功删除订单
            }
            
        } else {
            if($wpdb->update(
                $table_name,
                array(
                    'order_state' => 5
                ), 
                array(
                    'user_id' => $user_id,
                    'order_id' => $order_id
                )
            )) {
                return true; // 成功删除订单
            }
        }
        
        return array('error' => '删除订单失败');
    }
    
    //订单成功支付异步回调
    public static function order_confirm($order_id,$money){
        if(!qk_check_repo(md5($order_id))) return array('error'=>__('订单回调错误','qk'));

        if(!$order_id) return array('error'=>__('订单数据不完整','qk'));
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';

        //获取订单数据
        $order = $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE order_id = %s
                ",
                $order_id
            )
        ,ARRAY_A);
        
        if(empty($order)){
            return array('error'=>__('没有找到这个订单','qk'));
        }
        
        //如果已经支付成功，直接返回
        if((int)$order['order_state'] !== 0) return 'success';

        if($money && (float)$money != $order['order_total']){
            return array('error'=>__('订单金额错误','qk'));
        }
        
        //虚拟物品还是实物
        if((int)$order['order_commodity'] === 0 || $order['order_type'] == 'goumai'){
            $order_state = 3; //已签收
        }else{
            $order_state = 1; //已付款未发货
        }

        //更新订单
        if(apply_filters('qk_update_orders', array('order_state'=>$order_state,'order'=>$order))){

            return do_action('qk_order_notify_return', $order);
        }
        
        return array('error'=>__('回调错误','qk'));
    }
    
    //支付成功回调
    public static function order_notify_return($data){

        if(empty($data)) return array('error'=>__('更新订单失败','qk'));

        $order_type = 'callback_'.$data['order_type'];
        
        if(!method_exists(__CLASS__,$order_type)) return do_action($order_type,$data);
        
        return self::$order_type($data);
    }
    
    //认证服务
    public static function callback_verify($data){
        return apply_filters('qk_order_callback_verify',$data);
    }
    
    //支付加入圈子
    public static function callback_join_circle($data){
        return apply_filters('qk_order_callback_join_circle',$data);
    }
    
    //回调文章内容隐藏阅读
    public static function callback_post_neigou($data){
        //非游客支付
        if($data['user_id']) {
            $buy_data = get_post_meta($data['post_id'],'qk_buy_user',true);
            $buy_data = is_array($buy_data) ? $buy_data : array();
    
            if(!in_array($data['user_id'],$buy_data)){
                $buy_data[] = (int)$data['user_id'];
                update_post_meta($data['post_id'],'qk_buy_user',$buy_data);
            }
        }
        
        return apply_filters('qk_order_callback_post_neigou', $buy_data, $data);
    }
    
    //余额充值回调
    public static function callback_money_chongzhi($data){
        return apply_filters('qk_order_callback_money_chongzhi',$data);
    }
    
    //积分充值回调
    public static function callback_credit_chongzhi($data){
        return apply_filters('qk_order_callback_credit_chongzhi',$data);
    }
    
    //VIP购买
    public static function callback_vip_goumai($data){
        $user_vip = get_user_meta($data['user_id'],'qk_vip',true);

        // $vip_data = qk_get_option('user_vip_group');
        
        // $vip_index = (string)preg_replace('/\D/s','',$data['order_key']);
        
        // $vip = $vip_data[$vip_index];
        
        //开通vip天数
        $vip_day = (int)$data['order_value'];
        $end = '';
        
        if($user_vip && $user_vip === $data['order_key']) {
            
            $user_vip_exp_date = get_user_meta($data['user_id'],'qk_vip_exp_date',true);
            
            //如果是同等级会员续费
            // if($user_vip === 'vip'.$vip_index){
                
                if($vip_day == 0){
                    $end = 0; //续费永久
                }else if((string)$user_vip_exp_date !== '0'){
                    $end = $user_vip_exp_date + 86400 * $vip_day;
                }else{
                    $end = wp_strtotime('+'.$vip_day.' day');
                }
                
            // }
            
            //如果是vip等级变更
            // if($user_vip !== $data['order_key']){
            //     update_user_meta($data['user_id'],'qk_vip',$data['order_key']);
            // }
            
        }else{
            //开通vip
            update_user_meta($data['user_id'],'qk_vip',$data['order_key']);
            if($vip_day == 0 ){
                $end = 0; //开通永久
            }else{
                $end = wp_strtotime('+'.$vip_day.' day');
            }
        }
        
        //更新vip时间
        update_user_meta($data['user_id'],'qk_vip_exp_date',$end);
        
        return apply_filters('qk_callback_vip_goumai', $data['order_key'], $data);
    }
    
    //文章资源下载
    public static function callback_xiazai($data){
        
        //非游客支付
        if($data['user_id']) {
            $buy_data = get_post_meta($data['post_id'],'qk_download_buy',true);
            $buy_data = is_array($buy_data) ? $buy_data : array();
    
            $buy_data[$data['order_key']] = isset($buy_data[$data['order_key']]) && is_array($buy_data[$data['order_key']]) ? $buy_data[$data['order_key']] : array();
            $buy_data[$data['order_key']][] = $data['user_id'];
    
            update_post_meta($data['post_id'],'qk_download_buy',$buy_data);
        }
        
        return apply_filters('qk_order_callback_xiazai', $buy_data, $data);
    }
    
    //视频购买
    public static function callback_video($data){
        
        //非游客支付
        if($data['user_id']) {
            
            if($data['order_key'] !== '') {
                $buy_data = get_post_meta($data['post_id'],'qk_video_buy_group',true);
                $buy_data = is_array($buy_data) ? $buy_data : array();
        
                $buy_data[$data['order_key']] = isset($buy_data[$data['order_key']]) && is_array($buy_data[$data['order_key']]) ? $buy_data[$data['order_key']] : array();
                
                if(!in_array($data['user_id'],$buy_data[$data['order_key']])) {
                    $buy_data[$data['order_key']][] = $data['user_id'];
                    update_post_meta($data['post_id'],'qk_video_buy_group',$buy_data);
                }
            }else {
            
                if(!empty($data['chapter_id'])) {
                    $data['post_id'] = (int)$data['chapter_id'];
                }
                
                $buy_data = get_post_meta($data['post_id'],'qk_video_buy',true);
                $buy_data = is_array($buy_data) ? $buy_data : array();
                
                if(!in_array($data['user_id'],$buy_data)) {
                    $buy_data[] = $data['user_id'];
                    update_post_meta($data['post_id'],'qk_video_buy',$buy_data);
                }
            }
        }
        
        return apply_filters('qk_order_callback_xiazai', $buy_data, $data);
    }
    
    //获取订单类型
    public static function get_order_type($type='') {
        $types = apply_filters('qk_order_type',array(
            'product' => '产品购买',
            //'choujiang' => '抽奖',
            //'duihuan' => '兑换',
            //'goumai' => '购买',
            'post_neigou' => '文章内购',
            //'dashang' => '打赏',
            'xiazai' => '资源下载',
            'money_chongzhi' => '余额充值',
            'vip_goumai' => 'VIP购买',
            'credit_chongzhi' => '积分购买',
            'video' => '视频购买',
            'join_circle' => '支付入圈',
            'verify' => '认证'
        ));
        
        return isset($types[$type]) ? $types[$type] : $types;
    }
    
    public static function get_order_state($state='') {
        $states = array(
            0 => '待支付',
            1 => '已付款未发货',
            2 => '已发货',
            3 => '已完成',
            4 => '已退款',
            5 => '已删除'
        );
        return isset($states[$state]) ? $states[$state] : $states;
    }
    
    //获取用户订单列表数据
    public static function get_user_orders($user_id,$paged,$state){
        $user_id = (int)$user_id;
        $current_user_id = (int)get_current_user_id();
        $state = (int)$state;
        
        if(!$user_id && !$current_user_id){
            return array('error'=>'请先登录');
        }
        
        //if($user_id !== $current_user_id && !user_can($current_user_id, 'administrator' )) return array('error'=>'权限不足查看');
        
        $_state = self::get_order_state($state);
        
        if((!$_state || $state == 4  || $state == 5) && $state != 6 ) return array('error'=>'错误订单状态');
        
        $user_id = $current_user_id;
        
        $size = 3;
        $offset = ($paged-1)*$size;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';

        //全部
        if($state === 6){
            $query .= $wpdb->prepare(" AND order_state != %d AND order_state != %d", 4, 5);
        }else{
            $query .= $wpdb->prepare(" AND order_state = %d", $state);
        }
        
        //获取订单数据
        $orders = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE user_id = %d $query 
                ORDER BY order_date DESC 
                LIMIT %d,%d
            ",
                $user_id,
                $offset,
                $size
            ),ARRAY_A);
        
        $count = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*) FROM $table_name
                WHERE user_id = %d $query
            ",
                $user_id
            ));
        
        $data = array();
        
        foreach ($orders as $key => $value) {
            
            $order_state = self::get_order_state($value['order_state']);
            $order_type = self::get_order_type($value['order_type']);
            
            $product = self::get_order_product($value);
            
            $data[] = array(
                'id' => $value['id'],
                'post_id' => $value['post_id'],
                'user_id' => $value['user_id'],
                'order_id' => $value['order_id'],
                'order_price' => $value['order_price'],
                'order_total' => $value['order_total'],
                'order_count' => $value['order_count'],
                'order_date' => $value['order_date'],
                '_order_state' => $order_state,
                'order_state' => $value['order_state'],
                '_order_type' => $order_type,
                'order_type' => $value['order_type'],
                'pay_type' => $value['pay_type'],
                'product' => $product
            );
        }

        //print_r($orders);
        
        return array(
            'pages' => ceil($count/$size),
            'count' => $count,
            'data' => $data
        );
    }
    
    //获取订单产品信息
    public static function get_order_product($order){
         
        $type = $order['order_type'];
        $post_id = $order['post_id'];
        
        if($type == 'money_chongzhi') {
            return array(
                'name' => '余额充值 '.((int)$order['order_value']?: (int)$order['order_total']).' 元',
                'count' => '',
                'link' => qk_get_account_url('assets'),
                'whisper' => qk_get_custom_page_url('message').'?whisper=1',
                'thumb' => 'https://www.qkua.com/wp-content/uploads/2023/09/余额.svg',
            );
        }
        
        elseif ($type == 'credit_chongzhi') {
            
            
            return array(
                'name' => '购买 '.((int)$order['order_value']?: (int)$order['order_total']).' 积分',
                'count' => ((int)$order['order_value']?: (int)$order['order_total']).' 积分 x 1',
                'link' => qk_get_account_url('assets'),
                'whisper' => qk_get_custom_page_url('message').'?whisper=1',
                'thumb' => 'https://www.qkua.com/wp-content/uploads/2023/09/余额-1.svg',
            );
        }
        
        elseif ($type == 'vip_goumai') {
            $roles = User::get_user_roles();
            
            $vip = $roles[$order['order_key']];
            return array(
                'name' => $vip['name'],
                'count' => (int)$order['order_value'] === 0 ? '永久' : $order['order_value'].'天',
                'link' => qk_get_account_url('vip'),
                'whisper' => qk_get_custom_page_url('message').'?whisper=1',
                'thumb' => $vip['icon']?:$vip['image'],
            );
        }
        elseif ($type == 'join_circle') {
            $circle =  \Qk\Modules\Common\Circle::get_circle_data($post_id);
            return array(
                'name' => '加入'.$circle['name'],
                'count' => (int)$order['order_value'] === 0 ? '永久' : $order['order_value'].'天',
                'link' => $circle['link'],
                'whisper' => qk_get_custom_page_url('message').'?whisper=1',
                'thumb' => $circle['icon'],
            );
        }
        else{
            
            if(!empty($order['chapter_id'])) {
                $post_id = $order['chapter_id'];
            }
            
            $author_id = get_post_field('post_author', $post_id);
            return array(
                'name' => get_the_title($post_id),
                'count' => 'x 1',
                'link' => get_permalink($post_id),
                'whisper' => qk_get_custom_page_url('message').'?whisper='.$author_id,
                'thumb' => qk_get_thumb(array('url'=>\Qk\Modules\Common\Post::get_post_thumb($post_id),'width'=>100,'height'=>100)),
            );
        }
     }
    
}