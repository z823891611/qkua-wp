<?php namespace Qk\Modules\Common;

class Pay{

    //检查目前使用的是什么支付平台的
    public static function pay_type($type){

        $allowed_types = array(
            //'alipay_normal',
            'xunhu',
            'xunhu_hupijiao',
            //'wecatpay_normal',
            'balance',
            'credit',
            'card',
            'yipay',
            'alipay',
            'wecatpay',
            
        );

        if(!in_array($type, $allowed_types)){
            return array('error' => __('支付类型错误！', 'qk'));
        }
    
        if(strpos($type, 'alipay') !== false){
            $alipay_type = qk_get_option('pay_alipay');
            
            if(!$alipay_type){
                return array('error' => __('未启用支付宝', 'qk'));
            }
            
            return array(
                'pick' => 'alipay',
                'type' => $alipay_type
            );
        }elseif(strpos($type, 'wecatpay') !== false){
            $wecatpay_type = qk_get_option('pay_wechat');
            if(!$wecatpay_type){
                return array('error' => __('未启用微信支付', 'qk'));
            }
            return array(
                'pick' => 'wecatpay',
                'type' => $wecatpay_type
            );
        }elseif($type === 'balance'){
            return array(
                'pick' => 'balance',
                'type' => 'balance'
            );
        }elseif($type === 'credit'){
            return array(
                'pick' => 'credit',
                'type' => 'credit'
            );
        }elseif($type === 'card'){
            return array(
                'pick' => 'card',
                'type' => 'card'
            );
        }
    
        return array('error' => __('未知的支付类型', 'qk'));
    }
    
    //获取当前平台，允许使用的支付方式
    public static function allow_pay_type($order_type){

        $user_id = get_current_user_id();
        $is_mobile = wp_is_mobile();

        
        $allows = array(
            'wecatpay' => true,
            'alipay' => true,
            'balancepay' => $user_id ? true : false,
            'cardpay' => false
        );

        //获取当前的支付方式
        $alipay_type = qk_get_option('pay_alipay');
        $wecatpay_type = qk_get_option('pay_wechat');
        
        //是否启用支付宝
        if(!$alipay_type) {
            $allows['alipay'] = false;
        }
        
        //是否启用微信
        if(!$wecatpay_type) {
            $allows['wecatpay'] = false;
        }
        
        //余额充值
        if($order_type == 'money_chongzhi'){
            $allows['balancepay'] = false;
            $allows['cardpay'] = true;
        }
        
        //积分充值
        if($order_type == 'credit_chongzhi'){
            $allows['cardpay'] = true;
        }
        
        if($order_type == 'vip_goumai'){
            $allows['cardpay'] = false;
        }
        
        $credit = get_user_meta($user_id,'qk_credit',true);
        $allows['credit']  = $credit ? (int)$credit : 0;
        
        $money = get_user_meta($user_id,'qk_money',true);
        $allows['money']  = $money ? $money : 0;
        return $allows;
    }
    
    //选择支付平台
    public static function pay($data){
        
        $data = apply_filters('qk_pay_before', $data);
        
        if(isset($data['error'])) return $data;

        if(isset($data['pay_type'])){
            $pay_type = $data['pay_type'];
            $data['title'] = str_replace(array('&','=',' '),'',$data['title']);
            
            return self::$pay_type($data);
        }
    }
    
    //积分支付
    public static function credit($data){
        
        if(!$data['user_id']) return array('error'=>__('请先登录','qk'));
        
        if($data['order_type'] === 'choujiang'){
            return self::credit_pay($data['order_id']);
        }
        
        return array(
            'order_id'=> $data['order_id'],
            'qrcode'=> null,
            'url' => null,
            'pay_type' => 'credit'
        );
    }
    
    public static function credit_pay($order_id){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';

        $data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE `order_id`=%s",$order_id),
            ARRAY_A
        );

        if(empty($data)) return array('error'=>__('支付信息错误','qk'));
        
        // 判断是否等待支付
        if((int)$data['order_state'] !== 0) return array('error'=>__('订单已支付','qk'));

        if($data['pay_type'] !== 'credit') return array('error'=>__('支付类型错误','qk'));

        if(!$data['user_id']) return array('error'=>__('请先登录','qk'));
        
        $credit = User::credit_change($data['user_id'],-$data['order_total']);

        if($credit === false){
            return array('error'=>__('积分余额不足','qk'));
        }

        $data = apply_filters('qk_credit_pay_after', $data,$credit);
        
        //支付成功回调
        return Orders::order_confirm($data['order_id'],$data['order_total']);
    }
    
    //余额支付
    public static function balance($data){

        if(!$data['user_id']) return array('error'=>__('请先登录','qk'));

        return array(
            'order_id'=> $data['order_id'],
            'qrcode'=> null,
            'url' => null,
            'pay_type' => 'balance'
        );
        
    }

    public static function balance_pay($order_id){

        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';

        $data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE `order_id`=%s",$order_id),
            ARRAY_A
        );

        if(empty($data)) return array('error'=>__('支付信息错误','qk'));

        // 判断是否等待支付
        if((int)$data['order_state'] !== 0) return array('error'=>__('订单已支付','qk'));

        if($data['pay_type'] !== 'balance') return array('error'=>__('支付类型错误','qk'));

        if(!$data['user_id']) return array('error'=>__('请先登录','qk'));

        $money = User::money_change($data['user_id'],-$data['order_total']);

        if($money === false){
            return array('error'=>sprintf(__('%s不足','qk'),'余额'));
        }

        $data = apply_filters('qk_balance_pay_after', $data,$money);

        //if(isset($data['error'])) return $data;
 
        return Orders::order_confirm($data['order_id'],$data['order_total']);
    }
    
    //卡密支付
    public static function card($data){

        if(!$data['user_id']) return array('error'=>__('请先登录','qk'));

        return array(
            'order_id'=> $data['order_id'],
            'qrcode'=> null,
            'url' => null,
            'pay_type' => 'card'
        );
        
    }

    // public static function card_pay($order_id){

    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'qk_order';

    //     $data = $wpdb->get_row(
    //         $wpdb->prepare("SELECT * FROM $table_name WHERE `order_id`=%s",$order_id),
    //         ARRAY_A
    //     );

    //     if(empty($data)) return array('error'=>__('支付信息错误','qk'));

    //     // 判断是否等待支付
    //     if((int)$data['order_state'] !== 0) return array('error'=>__('订单已支付','qk'));

    //     if($data['pay_type'] !== 'card') return array('error'=>__('支付类型错误','qk'));

    //     if(!$data['user_id']) return array('error'=>__('请先登录','qk'));

    //     $data = apply_filters('qk_card_pay_after', $data,$money);

    //     //if(isset($data['error'])) return $data;
 
    //     return Orders::order_confirm($data['order_id'],$data['order_total']);
    // }
    
    /*-----------------------------------支付宝官方----------------------------------------*/
    /**
     * 支付宝官方设置项
     * 
     * @version 1.0.3
     * @since 2023/9/3
     */
    public static function alipay_settings(){
        
        $alipay = qk_get_option('alipay');
        
        return array(
            // 沙箱模式
            'debug'       => false,
            'sign_type'   => "RSA2",
            'appid'       => trim($alipay['appid']),
            'public_key'  => trim($alipay['public_key']),
            'private_key' => trim($alipay['private_key']),
            'notify_url'  => qk_get_custom_page_url('notify'),
            'return_url'  => home_url()
        );
    }
    
    public static function alipay($data){
        $alipay = qk_get_option('alipay');
        
        $alipay_type = $alipay['alipay_type'];
        
        $config = self::alipay_settings();
        
        $is_mobile = wp_is_mobile();
        
        try {
            //企业支付 跳转支付
            if($alipay_type == 'normal') {
                $config['return_url'] = $data['redirect_url'];
                //移动端
                if($is_mobile) {
                    //$config['return_url'] = $data['redirect_url'];
                    $config['passback_params'] = urlencode($data['redirect_url']);
                    $pay = \We::AliPayWap($config);
                }
                //pc
                else{
                    $pay = \We::AliPayWeb($config);
                }
            }
            
            //当面付
            if($alipay_type == 'scan') {
                $pay = \We::AliPayScan($config);
            }
            
            $result = $pay->apply([
                'out_trade_no' => $data['order_id'], // 商户订单号
                'total_amount' => $data['order_total'], // 支付金额
                'subject'      => $data['title']
            ]);
            
            //企业跳转支付
            if($alipay_type =='normal'){
                return array(
                    'order_id'=>$data['order_id'],
                    'url'=>$result
                );
            }
            
            //当面付移动端跳转支付
            if($alipay_type =='scan' && $is_mobile) {
                return array(
                    'order_id'=>$data['order_id'],
                    'url'=>$result['qr_code']
                ); 
            }
            
            //扫码支付
            return array(
                'is_weixin'=>$is_weixin,
                'is_mobile'=>$is_mobile,
                'order_id'=>$data['order_id'],
                'qrcode'=>$result['qr_code']
            );
           
        } catch (\Exception $e) {
            return array('error'=>$e->getMessage());
        }
    }
    

    /*-----------------------------------迅虎支付与虎皮椒----------------------------------------*/
    //迅虎支付
    public static function xunhu($data){
        
        $is_mobile = wp_is_mobile();
        //付款方式
        $payment_method = $data['payment_method'] == 'alipay' ? 'alipay' : 'wechat';
        $xunhu = qk_get_option('xunhu');
        
        $param = array(
            'out_trade_no'    => $data['order_id'],
            'type'          => $payment_method,
            'total_fee'     => $data['order_total']*100,
            'body'          => $data['title'],
            'notify_url'    => qk_get_custom_page_url('notify'),
            'nonce_str'     => str_shuffle(time())
        );
        
        require QK_THEME_DIR.'/Library/xunhu/xunhu.php';
        
        $xunhupay = new \XunHu($xunhu);
        
        //移动端浏览器
        if($is_mobile) {
            $param['redirect_url'] = $data['redirect_url'];
            
            $res = '';
            
            //微信支付
            if ($payment_method === 'wechat') {
                
                $param['trade_type'] = "WAP";
                $param['wap_url']    = $http_type.$_SERVER['SERVER_NAME'];//h5支付域名必须备案，然后找服务商绑定
                $param['wap_name']   = get_bloginfo('name');
                
                $res = $xunhupay->wechat_h5($param);

            }
            
            //支付宝
            if ($payment_method === 'alipay') {
                
                // if(isset($xunhu['xunhu_alipay_v2']) && $xunhu['xunhu_alipay_v2']) {
                //     $param['trade_type'] = "WAP";
                //     $res = $xunhupay->alipay_v2($param);
                // }else{
                    //收银台
                    $pay_url = $xunhupay->cashier($param);
                    
                    return array(
                        'url'=>htmlspecialchars_decode($pay_url,ENT_NOQUOTES),
                        'order_id'=>$data['order_id'],
                    );
                // }
                
            }
            
            if(!$res){
                return array('error'=>'Internal server error');
            }
            
            if($res['return_code'] != 'SUCCESS'){
                return array('error'=>sprintf('错误代码：%s。错误信息：%s',$res['err_code'],$res['err_msg']));
            }
            
            $sign = $xunhupay->sign($res);
    
            if(!isset($res['sign']) || $sign != $res['sign']){
                return array('error'=>'Invalid sign!');
            }
            
            $pay_url =$result['mweb_url'].'&redirect_url='.$param['redirect_url'];
            
            return array(
                'order_id'=>$data['order_id'],
                'url'=>array(
                    'url'=>$result['mweb_url'],
                    'data'=>$param
                )
            );
        }
        
        //扫码支付
        $res = $xunhupay->qrcode($param);

        if(!$res){
            return array('error'=>'Internal server error');
        }
        
        if($res['return_code'] != 'SUCCESS'){
            return array('error'=>sprintf('错误代码：%s。错误信息：%s',$res['err_code'],$res['err_msg']));
        }
        
        $sign = $xunhupay->sign($res);

        if(!isset($res['sign']) || $sign != $res['sign']){
            return array('error'=>'Invalid sign!');
        }
        
        return array(
            'is_weixin'=>$is_weixin,
            'is_mobile'=>$is_mobile,
            'order_id'=> $data['order_id'],
            'qrcode'=> $res['code_url']
        );
    }
    
    //虎皮椒
    public static function xunhu_hupijiao($data){
        $is_mobile = wp_is_mobile();
        $payment_method = $data['payment_method'] == 'alipay' ? 'alipay' : 'wechat';
        $hupijiao = qk_get_option('xunhu_hupijiao');
        
        if (empty($hupijiao[$payment_method.'_appid']) || empty($hupijiao[$payment_method.'_appsecret'])) {
            return array('error' => $payment_method.'未设置appid或者appsecret');
        }

        $param = array(
            'version'        => '1.1', //固定值，api 版本，目前暂时是1.1
            'lang'           => 'zh-cn', //必须的，zh-cn或en-us 或其他，根据语言显示页面
            'trade_order_id' => $data['order_id'],
            'total_fee'     => $data['order_total'],
            'title'          => $data['title'],
            'time'          => time(),
            'notify_url'    => qk_get_custom_page_url('notify'), //通知回调网址
            'return_url'    => $data['redirect_url'], //用户支付成功后，我们会让用户浏览器自动跳转到这个网址
            'callback_url'    => home_url(), //用户取消支付后，我们可能引导用户跳转到这个网址上重新进行支付
            'nonce_str'     => str_shuffle(time())
        );
        
        if($payment_method == 'alipay'){
            $param['appid'] = $hupijiao['alipay_appid'];
            $appsecret = $hupijiao['alipay_appsecret'];
            $param['plugins'] = $param['payment'] = 'alipay';//必须的，支付接口标识：wechat(微信接口)|alipay(支付宝接口)
        }else{
            $param['appid'] = $hupijiao['wechat_appid'];
            $appsecret = $hupijiao['wechat_appsecret'];
            $param['plugins'] = $param['payment'] = 'wechat';
        }
        
        if($is_mobile && $payment_method == 'wechat'){
            $param['type'] = 'WAP';
            $param['wap_url']    = $http_type.$_SERVER['SERVER_NAME'];//h5支付域名必须备案，然后找服务商绑定
            $param['wap_name']   = get_bloginfo('name');
        }
        
        $hashkey  = $appsecret;
        
        require QK_THEME_DIR.'/Library/xunhu_hupijiao/xunhu_hupijiao.php';
        
        $param['hash'] = \XH_Payment_Api::generate_xh_hash($param, $hashkey);
        
        $url = 'https://api.xunhupay.com/payment/do.html';
        if (!empty($hupijiao['hupijiao_gateway'])) {
            $url = $hupijiao['hupijiao_gateway'];
        }

        try {
            $response     = \XH_Payment_Api::http_post($url, json_encode($param));

            $result       = $response ? json_decode($response,true) : null;
            
            if(!$result){
                return array('error'=>'Internal server error');
            }

            $hash         = \XH_Payment_Api::generate_xh_hash($result,$hashkey);
            if(!isset( $result['hash']) || $hash != $result['hash']){
                return array('error' => 'Invalid sign!');
            }

            if( $result['errcode'] !=0 ){
                return array('error' => $result['errmsg']);
            }

            $pay_url = $result['url'];
            
            return array(
                'order_id'=> $data['order_id'],
                'qrcode'=> $res['url_qrcode'],
                'url'=> $pay_url
            );

        } catch (\Exception $e) {
            return array('error'=>$e->getMessage());
        }
    }
    
    /*-----------------------------------易支付（跳转支付）----------------------------------------*/
    public static function yipay($data){
        $payment_method = $data['payment_method'] == 'alipay' ? 'alipay' : 'wxpay';
        $yipay= qk_get_option('yipay');
        
        if(empty($yipay['yipay_id']) || empty($yipay['yipay_key']) || empty($yipay['yipay_gateway'])) {
            return array('error' => '请检查易支付设置，缺失参数');
        }
        
        //准备参数
        $param = array(
            'pid'           => trim($yipay['yipay_id'], " \t\n\r\0\x0B\xC2\xA0"),
            'type'          => $payment_method,
            'sitename'      => get_bloginfo('name'),
            'out_trade_no'  => $data['order_id'],
            'notify_url'    => qk_get_custom_page_url('notify'),
            'return_url'    => $data['redirect_url'],
            'name'          => $data['title'],
            'money'         => $data['order_total'],
            'sign_type'     => 'MD5'
        );
        
        ksort($param);
        reset($param);

        $sign = '';
        $urls = '';

        foreach ($param as $key => $val) {
            if ($val == '' || $key == 'sign' || $key == 'sign_type') continue;
            if ($sign != '') {
                $sign .= "&";
                $urls .= "&";
            }
            $sign .= "$key=$val";
            $urls .= "$key=" . urlencode($val);
        }
        
        $query = $urls . '&sign=' . md5($sign.trim($yipay['yipay_key'], " \t\n\r\0\x0B\xC2\xA0")).'&sign_type=MD5';
        $url = rtrim($yipay['yipay_gateway'], '/');
        $url = $url.'/submit.php?'.$query;
        
        return array(
            'order_id'=> $data['order_id'],
            'url'=> $url
        );
    }
    
    /****************************************回调通知**********************************************/
    //回调通知
    public static function pay_notify($method,$post){

        $post = apply_filters('qk_pay_notify_action', $post);
        
        $hupijiao = isset($post['hash']) && isset($post['trade_order_id']);
        $xunhupay = isset($post['mchid']) && isset($post['out_trade_no']) && isset($post['order_id']);

        $order_id = '';
        
        //迅虎 易支付 支付宝
        if(isset($post['out_trade_no'])){
            $order_id = $post['out_trade_no'];
        }
        
        //虎皮椒
        if($hupijiao){
            $order_id = $post['trade_order_id'];
        }
        
        if(!$order_id) return array('error'=>__('订单获取失败','qk'));

        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';
        $order = $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE order_id LIKE %s 
                ",
                '%'.$order_id.'%'
            )
        ,ARRAY_A);

        if(!$order) return '';

        $type = apply_filters('qk_order_check_action', array('order'=>$order,'hupijiao'=>$hupijiao,'xunhupay'=>$xunhupay));

        if(!$type) return '';

        if(isset($type['error'])) return $type;

        if($type === 'xunhu') return self::xunhu_notify($post,$order);
        
        $type = $type.'_notify';
        
        $_POST = $post;
        $_GET = $post;
        
        if(!method_exists(__CLASS__,$type)) return '';

        return self::$type($method,$post);
    }
    
    /**
     * 支付宝回调通知
     * 
     * @version 1.0.3
     * @since 2023/9/3
     */
    public static function alipay_notify($method,$post){
        
        $config = self::alipay_settings();
        if(isset($post['passback_params'])){
            $config['return_url'] =  $post['passback_params'];
        }
        
        try {
            $pay = \AliPay\App::instance($config);
            $data = $pay->notify();
            
            if($method == 'get'){
                if($post['sign'] === $data['sign']){
                    return true;
                }else{
                    return false;
                }
            }else{
                if($post['sign'] !== $data['sign']) return array('error'=>__('签名错误','qk'));

                if (in_array($data['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
                    $res = Orders::order_confirm($data['out_trade_no'],$data['total_amount']);
                    if(isset($res['error'])){
                        return $res;
                    }else{
                        return 'success';
                    }
                } else {
                    return array('error'=>'支付回调错误');
                }
            }
        } catch (\Exception $e) {
            return array('error'=>$e->getMessage());
        }
    }
    
    //迅虎回调通知
    public static function xunhu_notify($post,$order){
        require QK_THEME_DIR.'/Library/xunhu/xunhu.php';
        require QK_THEME_DIR.'/Library/xunhu_hupijiao/xunhu_hupijiao.php';
         
        $type = $order['pay_type'];
        
        if($type == 'xunhu'){
        
            $xunhu = qk_get_option('xunhu');
            $xunhupay = new \XunHu($xunhu);
    
            $sign = $xunhupay->sign($post);
    
            if($post['sign'] != $sign){
                //签名验证失败
                return array('error'=>__('签名错误','qk'));
            }
    
            if($post['status'] == 'complete'){
                $res = Orders::order_confirm($post['out_trade_no'],$post['total_fee']/100);
                return 'success';
            }
        }else{
            
            $pay_type = isset($post['plugins']) ? $post['plugins'] : ''; //支付接口标识：wechat(微信接口)|alipay(支付宝接口)
            $hupijiao = qk_get_option('xunhu_hupijiao');
            $appsecret = isset($hupijiao[$pay_type.'_appsecret']) ? $hupijiao[$pay_type.'_appsecret'] : '';
            
            if(!$appsecret) return array('error'=>__('回调错误','qk'));
            
            $hash = \XH_Payment_Api::generate_xh_hash($post,$appsecret);

            if($post['hash'] !== $hash){
                return array('error'=>__('签名错误','qk'));
            }

            if($post['status'] == 'OD'){
                $res = Orders::order_confirm($post['trade_order_id'],$post['total_fee']);
                return 'success';
            }
        }
        
        return array('error'=>'回调失败');
    }
    
    //易支付回调通知
    public static function yipay_notify($method,$data){
        $yipay= qk_get_option('yipay');
        
        if(isset($data['trade_status']) && $data['trade_status'] === 'TRADE_SUCCESS' && !empty($data['sign'])){
            ksort($data);
            reset($data);
    
            $sign = '';
    
            foreach ($data as $key => $val) {
                if ($val == '' || $key == 'sign' || $key == 'sign_type') continue;
                if ($sign != '') {
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val";
            }

            $sign = md5($sign .trim($yipay['yipay_key'], " \t\n\r\0\x0B\xC2\xA0"));

            if(!$sign) return array('error'=>'签名错误');

            if($sign === $data['sign']){
                $res = Orders::order_confirm($data['out_trade_no'],false);
                if(isset($res['error'])) return $res;
                return 'success';
            }
        }

        return array('error'=>'支付回调错误');
    }
    
    //ajax检查支付结果
    public static function pay_check($order_id){
        $res = apply_filters('qk_pay_check',$order_id);
        return $res;
    }
}