<?php namespace Qk\Modules\Common;
use \Firebase\JWT\JWT;
use Qk\Modules\Common\Sms;
use Qk\Modules\Common\Oauth;
use Qk\Modules\Common\Invite;
use Qk\Modules\Common\IpLocation;

/******登录与注册相关*******/

class Login{

    public function init(){
        //过滤/wp-json/jwt-auth/v1/token 请求地址返回参数
        add_filter('jwt_auth_token_before_dispatch', array($this,'rebulid_jwt_token'),10,3);
        
        // Hooks  Jwt效期时间。
        add_filter( 'jwt_auth_expire', array($this,'jwt_auth_expire'));
        
        //邮件支持html
        add_filter( 'wp_mail_content_type',array($this,'mail_content_type'));
        
        //WP自带用户注册钩子
        // add_action( 'user_register', array($this,'user_register'),10,1);
        
        // Hooks  过滤身份验证 cookie 有效期的持续时间。
        add_filter('auth_cookie_expiration', function ($expiration, $user_id = 0, $remember = true) {
            if($remember) {
                $expiration = (int)qk_get_option('login_time') * DAY_IN_SECONDS;
            }
            return $expiration;
        }, 99, 3);
        
        //在设置当前用户后触发。
        add_action( 'set_current_user',array($this,'qk_auto_set_token'));
        
        //在后台退出登录后触发
        add_action( 'wp_logout',function () {
            qk_deletecookie('qk_token');
        });
        
        add_action('qk_user_login', array($this,'insert_last_login')); 
    }
    
    // 创建新字段存储用户登录IP属地
    public function insert_last_login($user){
        
        $ip = qk_get_user_ip();  
        //端口
        $data = IpLocation::get($ip);
        
        if(isset($data['error'])) return;
        $data['date'] = current_time( 'mysql' );
        
        update_user_meta( $user->data->ID, 'qk_login_ip_location',$data);  
    }
    
    // public function user_register($user_id){

        
    // }

    public function mail_content_type(){
        return "text/html";
    }

    public function jwt_auth_expire( $issuedAt ) {
        return $issuedAt + (int)qk_get_option('login_time') * DAY_IN_SECONDS; //7 * 86400
    }

    //过滤/wp-json/jwt-auth/v1/token 请求地址返回参数
    public function rebulid_jwt_token($data,$user,$request){
        
        //检查是否允许登录
        if(!qk_get_option('allow_login')){
            // return array('error'=>__('登录已关闭','qk'));
            wp_die(__('登录已关闭','qk'),'',array('response' => 400));
        }

        //执行人机验证
        $slider_captcha = qk_get_option('allow_slider_captcha');

        // if(!!$slider_captcha) {
        //     $check_captcha = self::check_slider_captcha($request['captcha']);
        //     if(isset($check_captcha['error'])){
        //         wp_die(__($check_captcha['error'],'qk'),'',array('response' => 400));
        //     }
        // }

        //登录过期时间
        $expiration = (int)qk_get_option('login_time') * DAY_IN_SECONDS; //7 * 86400
        
        //设置jwt token并设置过期时间
        qk_setcookie('qk_token',$data['token'],$expiration);

        //pc端设置cookie
        $allow_cookie = apply_filters('qk_login_cookie', qk_get_option('allow_cookie'));
        if((string)$allow_cookie === '1'){
            //wp设置cookie
            wp_set_auth_cookie($user->data->ID,true);

        }

        // $_data = apply_filters('qk_current_user_data', $user->data->ID);

        $_data['token'] = $data['token'];
        
        // $issuedAt = time();
        // $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
        // $expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);

        // $_data['exp'] = $expire;
        do_action('qk_user_login',$user);
        
        do_action('wp_login',$user->user_login,$user);
        
        return $_data;
    }
    
    //退出登录
    public static function login_out(){
        
        //wp_verify_nonce( $_GET["_wpnonce"], 'wp-rest' );
        
        //如何使用 Wordpress Rest Api 获取当前登录用户？
        //https://stackoverflow.com/questions/42381521/how-to-get-current-logged-in-user-using-wordpress-rest-api
        //关于 Rest Api 无法获取到用户ID 返回 0 的问题，原因是需要携带nonce  wp_create_nonce( 'wp_rest' );
        //身份验证  https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/
        //https://cloud.tencent.com/developer/ask/sof/1468970/answer/2011223
        $user_id = get_current_user_id();
        
        $allow_cookie = apply_filters('qk_login_cookie', qk_get_option('allow_cookie'));
        
        //开启cookie兼容模式后
        if((string)$allow_cookie === '1'){
            wp_logout();
        }
        
        //删除jwt token
        qk_deletecookie('qk_token');

        // wp_cache_delete('qk_user_'.$user_id,'ser_data');
        // wp_cache_delete('qk_user_'.$user_id,'user_custom_data');

        do_action('qk_login_out', $allow_cookie);

        return true;
    }
    
    //接收用户ID进行登录
    public static function login_user($user_id) {
        
        $token = apply_filters('qk_login_user', $user_id);
        
        if(!isset($token['error'])) {
            do_action('qk_user_social_login', $user_id);
        }
        
        return $token;
    }
    
    /**
     * 重设密码
     *
     * @param object $request
     *
     * @return void
     * @author
     * @version 1.0.0
     * @since 2023
     */
    public static function rest_password($request){
        
        if(trim($request['password']) == '') return array('error'=>'请输入密码');

        if(trim($request['confirmPassword']) == '') return array('error'=>'请输入确认密码');

        if($request['password'] !== $request['confirmPassword']){
            return array('error'=>'两次密码不同，请重新输入');
        }

        if(strlen($request['password']) < 6){
            return array('error'=>'密码必须大于6位');
        }

        //检查验证码
        $res = self::code_check($request);
        if(isset($res['error'])){
            return $res;
        }
        
        if(is_email($request['username']) && email_exists($request['username'])){
            $user = get_user_by( 'email', $request['username']);
            $user_id = $user->ID;
        }
        
        if(self::is_phone($request['username']) && username_exists($request['username'])){
            $user = get_user_by('login',$request['username']);
            $user_id = $user->ID;
        }
        
        if($user_id) {
            wp_set_password($request['confirmPassword'], $user_id );
            return true;
        }
        
        return array('error'=>'绑定的账号不存在');
    }
    
    /**
     * 新用户注册验证
     *
     * @param object $request
     *
     * @return void
     * @author
     * @version 1.0.0
     * @since 2023
     */
    public static function regeister($request){
        
        //检查是否允许注册
        if(!qk_get_option('allow_register')){
            return array('error'=>__('注册已关闭','qk'));
        }
        
        //检查邀请码
        $invite = Invite::checkInviteCode($request['invite_code']);
        if(isset($invite['error'])) {
            return $invite;
        }
        
        //检查昵称
        $nickname = self::check_nickname($request['nickname']);
        if(isset($nickname['error'])){
            return $nickname;
        }
        
        //检查用户名
        $username = self::check_username($request['username']);
        if(isset($username['error'])){
            return $username;
        }
        
        //检查密码
        if(strlen($request['password']) < 6){
            return array('error'=>__('密码必须大于6位','qk'));
        }
        
        
        //执行人机验证
        $slider_captcha = qk_get_option('allow_slider_captcha');

        if(!!$slider_captcha) {
            $check_captcha = self::check_slider_captcha($request['captcha']);
            if(isset($check_captcha['error'])) return $check_captcha;
        }
        
        //检查是否允许验证
        $register_check = qk_get_option('allow_register_check');
        
        if($register_check) {
            
            //验证方式
            $check_type= qk_get_option('register_check_type');
            
            $check_type = self::check_str_type($request['username'],$check_type);
            
            if(isset($check_type['error'])){
                return $check_type;
            }
            
            //检查验证码
            $res = self::code_check($request);
            if(isset($res['error'])){
                return $res;
            }
        }
        
        return self::regeister_action($request,$check_type,$invite);
    }
    
    /**
     * 开始注册添加新用户
     *
     * @param [type] $data
     *
     * @return void
     * @author
     * @version 1.0.0
     * @since 2023
     */
    public static function regeister_action($data,$check_type,$invite){

        // $count = qk_get_option('register_count');

        // $ip = qk_get_user_ip();

        // $has_register_count = (int)wp_cache_get('register_limit_'.md5($ip),'register_limit');

        // if($has_register_count >= $count) return array('error'=>__('非法操作','qk'));

        if(is_email($data['username'])){
            $user_id = wp_create_user(md5($data['username']).rand(1,9999), $data['password']);
        }else{
            $user_id = wp_create_user($data['username'], $data['password']);
        }

        // wp_cache_set('register_limit_'.md5($ip),($has_register_count + 1),'register_limit',HOUR_IN_SECONDS*3);

        if (is_wp_error($user_id)) {
            return array('error'=>$user_id->get_error_message());
        }

        //如果是邮箱注册，更换一下用户的登录名
        $rand = rand(100,999);
        $email = '';
        if(is_email($data['username'])){
            
            $email = $data['username'];
            
            global $wpdb;
            $wpdb->update($wpdb->users, array('user_login' => 'user'.$user_id.'_'.$rand), array('ID' => (int)$user_id));
            $data['username'] = 'user'.$user_id.'_'.$rand;
        }

        //删除用户默认昵称
        delete_user_meta($user_id,'nickname');

        //更新昵称和邮箱
        $arr = array(
            'display_name'=>$data['nickname'],
            'ID'=>$user_id,
            'user_email'=>is_email($email) ? $email : $data['username'].'@'.get_option('wp_site_domain')
        );
        wp_update_user($arr);

        //获取 token
        $token = '';
        if(class_exists('Jwt_Auth_Public')){
            $request = new \WP_REST_Request( 'POST','/wp-json/jwt-auth/v1/token');
            $request->set_query_params(array(
                'username' => $data['username'],
                'password' => $data['password']
            ));
            
            $JWT = new \Jwt_Auth_Public('jwt-auth', '1.1.0');
            $token = $JWT->generate_token($request);
            unset($request);
            if(is_wp_error($token)){
                return array('error'=>__('注册成功，登录失败，请重新登录','qk'));
            }
        }

        // //缓存token，防止重复注册
        // if(wp_using_ext_object_cache() && $data['token']){
        //     wp_cache_add(md5($data['token']),'1','',300);
        // }

        if($invite){
            //使用邀请码
            Invite::useInviteCode($user_id,$invite['card_code']);
        }

        do_action('qk_user_regeister',$user_id);

        if($token){
            return '注册成功，欢迎您'.$arr['display_name']; //apply_filters('qk_regeister', $token,array('user_id'=>$user_id,'invitation_id'=>$check_invitation));
        }else{
            return array('error'=>__('登陆失败','qk'));
        }
    }
    
    //发送验证码
    public static function send_code($request){

        //将 token 存入缓存，防止重复提交，
        // if(wp_using_ext_object_cache() && $request['token']){
        //     $isset_token = wp_cache_get(md5($request['token'].'1'));
        //     if($isset_token) return array('error'=>__('请不要重复提交','qk'));
        // }
        
        $register_check = qk_get_option('allow_register_check');
        
        if(!$register_check) return array('error' => '验证方式未开启');
        
        //验证方式
        $check_type = qk_get_option('register_check_type');
        
        $type = self::check_str_type($request['username'],$check_type);
        
        if(isset($type['error'])) return $type;
        
        //执行人机验证 或验证码
        
        //ajax_man_machine_verification('img_yz_signin_captcha');

        //if(($check_type == 'text' || $check_type == 'luo') && $request['loginType'] != 3) return true;
        
        //找回密码验证
        if(isset($request['type']) && $request['type'] == 'forgot'){
            if(!email_exists($request['username']) && !username_exists($request['username'])){
                return array('error'=>'不存在此邮箱或手机号码，请重新输入');
            }
        }else{
            //检查用户名
            $username = self::check_username($request['username']);
            if(isset($username['error'])){
                return $username;
            }
        }
        
        // //检查验证码
        // $res = self::code_check($request);
        // if(isset($res['error'])){
        //     return $res;
        // }
        
        // //缓存token，防止重复注册
        // if(wp_using_ext_object_cache() && $request['token']){
        //     wp_cache_add(md5($request['token'].'1'),'1','',300);
        // }
        
        if(is_email($request['username'])){
            return self::send_email_code(rand(100000,999999),$request['username']);
        }

        if(self::is_phone($request['username'])){
            return self::send_sms_code(rand(100000,999999),$request['username']);
        }

    }
    
    //邮箱发送验证码
    public static function send_email_code($code,$email){
        
        //session 开启
        @session_start();
        
        /**保存验证码到缓存 $_SESSION*/
        $_SESSION['qk_captcha_code'] = $code;
        $_SESSION['qk_verification'] = $email;
        
        if (!empty($_SESSION['qk_captcha_time'])) {
            $time_x = wp_strtotime(current_time('mysql')) - wp_strtotime($_SESSION['qk_captcha_time']);
            if ($time_x < 60) {
                //剩余时间
                return array('error' => (60 - $time_x) . '秒后可重新发送');
            }
        }
    
        //验证码过期时间
        $_SESSION['qk_captcha_time'] = current_time('mysql');
        
        $blog_name = get_bloginfo('name');
        
        if (is_email($email)) {
            $title   = '[' . $blog_name . ']' . '收到验证码';
            $message = '<div style="width:700px;background-color:#fff;margin:0 auto;border: 1px solid #ccc;">
            <div style="height:64px;margin:0;padding:0;width:100%;">
                <a href="'.QK_HOME_URI.'" style="display:block;padding: 12px 30px;text-decoration: none;font-size: 24px;letter-spacing: 3px;border-bottom: 1px solid #ccc;" rel="noopener" target="_blank">
                    '.$blog_name.'
                </a>
            </div>
            <div style="padding: 30px;margin:0;">
                <p style="font-size:14px;color:#333;">
                    '.__('您的邮箱为：','qk').'<span style="font-size:14px;color:#333;"><a href="'.$email.'" rel="noopener" target="_blank">'.$email.'</a></span>'.__('，验证码为：','qk').'
                </p>
                <p style="font-size:34px;color: green;">'.$code.'</p>
                <p style="font-size:14px;color:#333;">'.__('验证码的有效期为5分钟，请在有效期内输入！','qk').'</p>
                <p style="font-size:14px;color: #999;">— '.$blog_name.'</p>
                <p style="font-size:12px;color:#999;border-top:1px dotted #E3E3E3;margin-top:30px;padding-top:30px;">
                    '.__('本邮件为系统邮件不能回复，请勿回复。','qk').'
                </p>
            </div>
        </div>';
            
            $send = wp_mail($email, $title, $message,array('Content-Type: text/html; charset=UTF-8'));
        }
        
        if (!$send){
            return array('error' => '验证码发送失败');;
        }else {
            return '验证码已发送至您的邮箱，注意查收';
        }
    }
    
    //手机短信验证码
    public static function send_sms_code($code,$phone){
        //session 开启
        @session_start();
        
        /**保存验证码到缓存 $_SESSION*/
        $_SESSION['qk_captcha_code'] = $code;
        $_SESSION['qk_verification'] = $phone;
        
        if (!empty($_SESSION['qk_captcha_time'])) {
            $time_x = wp_strtotime(current_time('mysql')) - wp_strtotime($_SESSION['qk_captcha_time']);
            if ($time_x < 60) {
                //剩余时间
                return array('error' => (60 - $time_x) . '秒后可重新发送');
            }
        }
    
        //验证码过期时间
        $_SESSION['qk_captcha_time'] = current_time('mysql');
        
        return Sms::send($phone,$code);
    }
    
    //验证码效验
    public static function code_check($request){
        @session_start();

        if(!isset($request['username']) || !isset($request['code'])) return array('error'=>__('请输入验证码','qk'));
        if(!$request['username'] || !$request['code']) return array('error'=>__('请输入验证码','qk'));

        if (empty($_SESSION['qk_captcha_code']) || $_SESSION['qk_captcha_code'] != $request['code'] 
            || empty($_SESSION['qk_verification']) || $_SESSION['qk_verification'] != $request['username']) {
            
            return array('error' => $msg . '验证码错误');
        } else {
            if (!empty($_SESSION['qk_captcha_time'])) {
                $time_x = wp_strtotime(current_time('mysql')) - wp_strtotime($_SESSION['qk_captcha_time']);
                //30分钟有效
                if ($time_x > 1800) {
                    //关闭会话
                    session_destroy();
                    return array('error' => $msg . '验证码已过期');
                }
            }
            
            //关闭会话
            session_destroy();
            return  $msg . '验证码效验成功';
        }
    }
    
    //检查是邮箱还是手机号码
    public static function check_str_type($str,$type = 'email'){
        if(!$str) {
            return  array('error'=>__('请输入邮箱或手机号'));
        }
        
        if($type == 'email') {
            if(!is_email($str)) {
                return array('error'=>__('邮箱格式错误'));
            }
            
        }else if ($type == 'tel') {
            if (!self::is_phone($str)) {
                return  array('error'=>__('手机号码格式有误'));
            }
        }else {
            if(is_email($str)) {
                return  'email';
            }else if (self::is_phone($str)) {
                return  'tel';
            }else {
                return  array('error'=>__('手机号或邮箱格式错误'));
            }
        }
        
    }
    
    //拼图滑块验证校验
    public static function check_slider_captcha($captcha) {
        if (!is_array($captcha)) {
            return array('error' => '请完成滑块验证');
        }
        
        $sum = array_sum($captcha);
        $avg = $sum / count($captcha);
        $stddev = sqrt(array_sum(array_map(function ($val) use ($avg) {
            return pow($val - $avg, 2);
        }, $captcha)) / count($captcha));
    
        if ($stddev == 0) {
            return array('error' => '图形验证码错误');
        }
        
        return true;
    }
    
    //检查昵称 显示的名称
    public static function check_nickname($nickname){

        $nickname = trim($nickname, " \t\n\r\0\x0B\xC2\xA0");

        if(!$nickname){
            return array('error'=>__('请输入昵称','qk'));
        }

        $nickname = sanitize_text_field($nickname);
        
        //用户敏感词检查
        // $censor = apply_filters('qk_text_censor', $nickname);
        // if(isset($censor['error'])) return $censor;
        
        //检查昵称是否有特殊字符
        if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9]+$/u", $nickname)) {
            return array('error'=>__('昵称中包含特殊字符，请重新填写','qk'));
        }

        $nickname = str_replace(array('{{','}}'),'',wp_strip_all_tags($nickname));

        if(self::strLength($nickname) > 8) return array('error'=>__('昵称太长了！最多8个字符','qk'));

        //检查昵称是否重复
        global $wpdb;
        $table_name = $wpdb->prefix . 'users';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE display_name = %s", 
            $nickname
        ));

        if($result && (int)$result->ID !== (int)get_current_user_id()){
            unset($result);
            return array('error'=>__('昵称已存在，请换一个试试','qk'));
        }
        unset($result);
        return $nickname;
    }
    
    //检查登录用户名username
    public static function check_username($username){
        if($username == '') return array('error'=>__('请输入邮箱或手机号码','qk'));
        
        //是否允许验证
        $register_check = qk_get_option('allow_register_check');
        
        if($register_check) {
        
            //验证方式
            $check_type = qk_get_option('register_check_type');
    
            switch ($check_type) {
                case 'email':
                    if(!is_email($username)){
                        return array('error'=>__('您输入的不是邮箱','qk'));
                    }
                    break;
                case 'tel':
                    if(!self::is_phone($username)){
                        return array('error'=>__('您输入的不是手机号码','qk'));
                    }
                    break;
                case 'telandemail':
                    if(!is_email($username) && !self::is_phone($username)){
                        return array('error'=>__('您输入的不是邮箱或手机号码','qk'));
                    }
                    break;
            }
            
            if(is_email($username) && email_exists($username)){
                return array('error'=>__('该邮箱已被注册','qk'));
            }
            
            if(self::is_phone($username) && username_exists($username)){
                return array('error'=>__('该手机号码已被注册','qk'));
            }

        }else{
            if (!preg_match("/^[a-z\d]*$/i",$username) && !is_email($username)) {
                return array('error'=>__('用户名只能使用字母和（或）数字','qk'));
            }
        }
        
        if(username_exists($username)){
            return array('error'=>__('该用户名已被注册','qk'));
        }

        return str_replace(array('{{','}}'),'',$username);
    }
    
    public static function is_phone($mobile) {
        return preg_match("/^1[3456789]{1}\d{9}$/", $mobile) ? true : false;
    }
    
    public static function strLength($str, $charset = 'utf-8') {
        if ($charset == 'utf-8')
          $str = iconv ( 'utf-8', 'gb2312', $str );
        $num = strlen ( $str );
        $cnNum = 0;
        for($i = 0; $i < $num; $i ++) {
          if (ord ( substr ( $str, $i + 1, 1 ) ) > 127) {
            $cnNum ++;
            $i ++;
          }
        }
        $enNum = $num - ($cnNum * 2);
        $number = ($enNum / 2) + $cnNum;
        return ceil ( $number );
    }
    
    //在设置当前用户后触发。
    public static function qk_auto_set_token(){
    	$user_id = get_current_user_id();
    	if(!$user_id) return;
        
        //是否允许cookie
    	//if(!qk_get_option('allow_cookie')) return;
    
    	$token = qk_getcookie('qk_token');
    	if(!$token){
    	    if(class_exists('Jwt_Auth_Public')){
        		$user_data = get_user_by('id',$user_id);
        		
        		if(!isset($user_data->user_login)) return;
        
        		$request = new \WP_REST_Request( 'POST','/wp-json/jwt-auth/v1/token');
        		$request->set_query_params(array(
        			'username' => $user_data->user_login,
        			'password' => $user_data->user_pass
        		));
        
        		$JWT = new \Jwt_Auth_Public('jwt-auth', '1.1.0');
        		$token = $JWT->generate_token($request);
        		
        		if(is_wp_error( $token ) ) return;
    
        		if(!isset($token['token'])) return;
        
        		qk_setcookie('qk_token',$token['token']);
    	    }else{
                return array('error'=>__('请安装 JWT Authentication for WP-API 插件','qk'));
            }
    	}
    }
    
    /**
     * 检查是否需要强制绑定用户信息
     *
     * @param [int] $current_user_id 当前用户ID
     *
     * @return void
     * @version 1.1
     * @since 2023
     */
    public static function check_force_binding($user_id){
        $user_id = $user_id?:get_current_user_id();
        $bind = qk_get_option('force_binding');
        
        if(empty($bind) || !$bind) return false;
        
        //是否允许验证
        $register_check = qk_get_option('allow_register_check');
        
        if(!$register_check) return false;
        
        //验证方式
        $check_type = qk_get_option('register_check_type');
        
        //管理员不强制绑定
        if(user_can($user_id, 'administrator')) return false;
        
        $user_data = get_userdata($user_id);

        //检查是否绑定手机号码
        if($check_type === 'tel'){
            //检查登录用户名是否手机号，如果是则已经绑定
            if(!self::is_phone($user_data->data->user_login)) {
                return 'tel';
            };
        }

        //检查是否绑定了邮箱
        if($check_type === 'email'){
            $domain = get_option('wp_site_domain');
            if(empty($user_data->data->user_email) || strpos($user_data->data->user_email,$domain) !== false){
                return 'email';
            };
        }
        
        if($check_type === 'telandemail'){
            if((empty($user_data->data->user_email) || strpos($user_data->data->user_email,$domain) !== false) && !self::is_phone($user_data->data->user_login)) { return 'telandemail';
            }
        }
        
        return false;
    }
    
    //获取登录设置
    public static function get_login_settings(){
        //是否允许注册
        $allow_regeister = qk_get_option('allow_register');
         
        $register_check = qk_get_option('allow_register_check');
        $check_type = $register_check ? qk_get_option('register_check_type') : false;
        
        // 滑块验证
        $slider_captcha = !!qk_get_option('allow_slider_captcha');
        
        $login_text = '手机号或邮箱';

        switch ($check_type) {
            case 'tel':
                $login_text = '手机号';
                break;
            case 'email':
                $login_text = '邮箱';
                break;
            case 'telandemail':
                $login_text = '手机号或邮箱';
                break;
            default:
                $login_text = '用户名';
                break;
        }
        
        //邀请码
        $invite_type = qk_get_option('invite_code_type');
        $invite_url = qk_get_option('invite_code_url');
        
        $oauths = Oauth::get_enabled_oauths();
        return array(
            'check_type' => $check_type, //注册验证类型
            'login_text' => $login_text, 
            'allow_register' => $allow_regeister, //是否允许注册
            'allow_slider_captcha' => $slider_captcha, // 允许滑块验证
            'invite_type' => $invite_type,
            'invite_url' => $invite_url,
            'oauths' => $oauths
        );
    }
}