<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\User;
use Qk\Modules\Common\Orders;

class Card{
    public static function card_pay($code){
        $user_id = get_current_user_id();
        $code = trim($code, " \t\n\r\0\x0B\xC2\xA0");
        
        if(!$user_id) return array('error'=>'请先登录');
        if(!$code) return array('error'=>'激活码未填写');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_card';

        $res = $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE card_code=%s
                ",
                $code
        ),ARRAY_A);
        
        if(empty($res) || !$res) {
            
            return array('error'=>'激活码不存在');
            
        }else if ((int)$res['status'] === 1) {
            
            return array('error'=>'激活码已被使用');
             
        }else if (empty($res['type']) || !isset($res['type']) || !$res['type'] || $res['type'] == 'invite') {
            
            return array('error'=>'激活码类型错误');
            
        }
        
        if($wpdb->update(
            $table_name, 
            array( 
                'status' => 1,
                'user_id' => $user_id
            ), 
            array( 'id' => $res['id'] ),
            array( 
                '%d',
                '%d'
            ), 
            array( '%d' ) 
        )){
            if($res['value'] <= 0) return array('error'=> '金额错误');
            
            $type = 'callback_'.$res['type'];
        
            if(!method_exists(__CLASS__,$type)) return do_action($type,$res);
            
            return self::$type($res);
        }
        
        return array('error'=>'网络错误，请稍后重试');

    }
    
    //余额充值回调
    public static function callback_money($data){
        
        if(isset($data['value']) && is_numeric($data['value'])) {
            //创建订单
            $order_res = Orders::build_order(array(
                'order_price'=> (int)$data['value'],
                'order_type'=>'money_chongzhi',
                'title'=>'余额充值',
                'pay_type'=>'card',
            ));
            
            if(isset($order_res['error'])) return $order_res;
            
            $order_confirm = Orders::order_confirm($order_res['order_id'],(int)$data['value']);
            
            if(isset($order_confirm['error'])) return $order_confirm;
            
            return array('msg' => "您已成功使用激活码充值余额 {$data['value']} 元~");
        }else{
            return array('error'=>'网络错误，请稍后重试');
        }
        
        return apply_filters('qk_card_callback_money', $data);
    }
    
    //积分充值回调
    public static function callback_credit($data){
        
        if(isset($data['value']) && is_numeric($data['value'])) {
            //创建订单
            $order_res = Orders::build_order(array(
                'order_price'=> (int)$data['value'],
                'order_type'=>'credit_chongzhi',
                'title'=>'积分充值',
                'pay_type'=>'card',
            ));
            
            if(isset($order_res['error'])) return $order_res;
            
            $order_confirm = Orders::order_confirm($order_res['order_id'],(int)$data['value']);
            
            if(isset($order_confirm['error'])) return $order_confirm;
            
            return array('msg' => "您已成功使用激活码充值积分 {$data['value']} 元~");
            
        }else{
            return array('error'=>'网络错误，请稍后重试');
        }

        return apply_filters('qk_card_callback_credit', $data);
    }
    
    //VIP购买
    public static function callback_vip($data){
        
        if(isset($data['card_key']) && $data['card_key'] && is_numeric($data['card_value'])) {
            //创建订单
            $order_res = Orders::build_order(array(
                'order_price'=> (int)$data['value'],
                'order_type'=>'vip_goumai',
                'order_key'=> $data['card_key'],
                'order_value'=> (int)$data['card_value'],
                'title'=>'开通会员',
                'pay_type'=>'card',
            ));
            
            if(isset($order_res['error'])) return $order_res;
            
            $order_confirm = Orders::order_confirm($order_res['order_id'],(int)$data['value']);
            
            if(isset($order_confirm['error'])) return $order_confirm;
            
            $roles = User::get_user_roles();
            
            $vip = '';
            if(isset($roles[$data['card_key']])) {
                $vip = $roles[$data['card_key']]['name'];
            }
            
            $day = (int)$data['card_value'] === 0 ? '永久' : $data['card_value'].'天';
            
            return array('msg' => "您已成功激活{$day}{$vip}~");
            
        }else{
            return array('error' => '网络错误，请稍后重试');
        }
        
        return apply_filters('qk_card_callback_vip', $data);
    }
}