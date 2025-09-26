<?php namespace Qk\Modules\Common;
use \Firebase\JWT\JWT;
use Qk\Modules\Common\Login;
use Qk\Modules\Common\Invite;

//社交平台登录
class Oauth{
    public static function init($type,$code){
        
        //聚合登录
        if(strpos($type,'juhe_') !== false){
            //$type = str_replace('juhe_', '', $type);
            return self::callback_juhe($type,$code);
        }

        if(strpos($type,'wx_') !== false){
            $type = 'weixin';
        }
        
        $_type = 'callback_'.$type;

        if(!method_exists(__CLASS__,$_type)) return array('error'=>'数据错误');
        
        return self::$_type($type,$code);
    }
    
    //qq登录
    public static function callback_qq($type,$code){
        
        $open = qk_get_option('oauth_qq_open');
        
        $qq = qk_get_option('oauth_qq');
        
        $args = array(
            'url' => 'https://graph.qq.com/oauth2.0/token',
            'client_id' =>  trim($qq['app_id']),
            'client_secret' => trim($qq['app_secret'])
        );
        
        return self::get_token($args,$type,$code);
    }
    
    //微博登录
    public static function callback_weibo($type,$code){
        
        $weibo = qk_get_option('oauth_weibo');
        
        $args = array(
            'url' => 'https://api.weibo.com/oauth2/access_token',
            'client_id' =>  trim($weibo['app_id']),
            'client_secret' => trim($weibo['app_secret'])
        );
        
        return self::get_token($args,$type,$code);
    }
    
    //聚合登录
    public static function callback_juhe($type,$code){
        $oauths = self::get_enabled_oauths($type);
        
        if(!isset($oauths[$type])) return array('error'=>'参数错误');
        $juhe = qk_get_option('oauth_juhe');
        
        $gateway = rtrim(trim($juhe['gateway'], " \t\n\r\0\x0B\xC2\xA0"),'/');
                
        //构造请求参数
        $params = array(
            'act' => 'callback',
            'appid' => trim($juhe['app_id'], " \t\n\r\0\x0B\xC2\xA0"),
            'appkey' => trim($juhe['app_key'], " \t\n\r\0\x0B\xC2\xA0"),
            'type' => str_replace('juhe_', '', $type),
            'code' => $code
        );
        
        $api = $gateway.'/connect.php?'.http_build_query($params);
        $res = wp_remote_post($api);
        
        if(is_wp_error($res)){
            return array('error'=>'网络错误，请稍后再试');
        }
        
        if($res['response']['code'] == 200){
            $data = json_decode($res['body'],true);

            if(isset($data['code']) && (int)$data['code'] === 0){

                return self::social_login(array(
                    'access_token'=>$data['access_token'],
                    'openid'=>$data['social_uid'],
                    'type'=>$type,
                    'user_info'=>array(
                        'type'=>$type,
                        'nickname' => $data['nickname'],
                        'avatar' => $data['faceimg'],
                        'sex' => $data['gender'] == '男' ? 1 : 0
                    )
                ));
                
            }else {
                return array('error'=>$data['msg']);
            }
        }
        
        return array('error'=>'网络错误，请稍后再试');
    }
    
    public static function generate_token($data) {
        $issuedAt = time();
        $expire = $issuedAt + 600; // 10分钟时效
        $token = array(
            'iss' => QK_HOME_URI,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expire,
            'data' => $data
        );
        return JWT::encode($token, AUTH_KEY);
    }
    
    //社交登录
    public static function social_login($data) {
        return apply_filters('qk_social_login', $data);
    }
        
    //社交登录强制绑定（邮箱，邀请码，手机号）后处理API动作
    public static function binding_login($data) {
        return apply_filters('qk_binding_login', $data);
    }
    
    //创建用户
    public static function create_user($data,$invite = false,$user_data = false){
        
        $password = $user_data && !empty($user_data['password']) ? $user_data['password'] : wp_generate_password();
        $username = $user_data && !empty($user_data['teloremail']) ? $user_data['teloremail'] : (isset($data['unionid']) && !empty($data['unionid']) ? $data['unionid'] : $data['openid']);

        if(is_email($username)){
            $user_id = wp_create_user(md5($username).rand(1,9999), $password);
        }else{
            $user_id = wp_create_user($username, $password);
        }

        if(is_wp_error($user_id)) {
            return array('error'=>$user_id->get_error_message());
        }
        
        //如果是非手机号注册，更换一下用户的登录名
        $rand = rand(100,999);

        if(!Login::is_phone($username)){
            //更换一下用户名
            global $wpdb;
            $wpdb->update($wpdb->users, array('user_login' => 'user'.$user_id.'_'.$rand), array('ID' => $user_id));

        }
        
        $email = is_email($username) ? $username : 'user'.$user_id.'_'.$rand.'@'.get_option('wp_site_domain');
        
        //删除用户默认昵称
        delete_user_meta($user_id,'nickname');

        //昵称过滤掉特殊字符
        $nickname = preg_replace('/\ |\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/','',$data['nickname']);

        //检查昵称是否重复
        global $wpdb;
        $table_name = $wpdb->prefix . 'users';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE display_name = %s", 
            $nickname
        ));

        if($nickname){
            //昵称是否重复
            if($result){
                $arr = array(
                    'display_name'=>$nickname.$user_id,
                    'ID'=>$user_id,
                    'user_email'=>$email
                );
            }else{
                $arr = array(
                    'display_name'=>$nickname,
                    'ID'=>$user_id,
                    'user_email'=>$email
                );
            }
        }else{
            $arr = array(
                'display_name'=>'user'.$user_id,
                'ID'=>$user_id,
                'user_email'=>$email
            );
        }
        
        wp_update_user($arr);

        if($invite){
            //使用邀请码
            Invite::useInviteCode($user_id,$invite['card_code']);
        }

        //绑定用户数据
        self::binding_user($user_id,$data);
        
        do_action('qk_user_regeister',$user_id);

        //返回用户数据
        return Login::login_user($user_id);
    }
    
    // 绑定用户
    public static function binding_user($user_id,$data){
        if(isset($data['avatarUrl'])){
            $data['avatar'] = $data['avatarUrl'];
        }

        if(isset($data['nickName'])){
            $data['nickname'] = $data['nickName'];
        }

        $oauth_data = get_user_meta($user_id,'qk_oauth',true);
        $oauth_data = is_array($oauth_data) ? $oauth_data : array();
        $oauth_data[$data['type']]['avatar_type'] = $data['type'];
        $oauth_data[$data['type']]['nickname'] = $data['nickname'];
        $oauth_data[$data['type']]['avatar'] = $data['avatar'];

        //存入头像
        update_user_meta($user_id,'qk_oauth_avatar',$data['avatar']);
        update_user_meta($user_id,'qk_oauth',$oauth_data);

        //存入id
        if(isset($data['unionid']) && $data['unionid'] != ''){
            update_user_meta($user_id,'qk_oauth_'.$data['type'].'_unionid',$data['unionid']);
        }
        
        update_user_meta($user_id,'qk_oauth_'.$data['type'].'_openid',$data['openid']);

        do_action('qk_social_binding_user',$user_id,$data);
        
        return true;
    }
    
    //检查是否绑定
    public static function check_binding_user_exist($data){
        
        //unionid为社交账号
        if(isset($data['unionid']) && $data['unionid'] != ''){
            $user = get_users(array('meta_key'=>'qk_oauth_'.$data['type'].'_unionid','meta_value'=>$data['unionid']));
        }else{
            if(isset($data['mpweixin']) && $data['mpweixin']){
                $user = get_users(array('meta_key'=>'qk_oauth_mpweixin_openid','meta_value'=>$data['openid']));
            }else{
                $user = get_users(array('meta_key'=>'qk_oauth_'.$data['type'].'_openid','meta_value'=>$data['openid']));
            }
        }

        if(!empty($user)){
            return $user[0]->data;
        }else{
            return false;
        }
    }
    
    //获取社交用户信息
    public static function get_social_user_info($data){
        
        if(strpos($data['type'],'juhe_') !== false){
                
            $data['nickname'] = $data['user_info']['nickname'];
            $data['avatar'] = str_replace('http://','https://',$data['user_info']['avatar']);

            $data['sex'] = isset($data['sex']) ? $data['sex'] : 0;
            unset($data['user_info']);
            
            return $data;
        }
        
        if(!in_array($data['type'],array('qq','weibo','weixin','apple','baidu','toutiao'))) return array('error'=>'参数错误11111');
        
        if(isset($data['user_info']['nickName']) && isset($data['user_info']['avatarUrl'])){
            
            $data['nickname'] = $data['user_info']['nickName'];
            $data['avatar'] = str_replace('http://','https://',$data['user_info']['avatarUrl']);

            $data['sex'] = 0;
            unset($data['user_info']);
        }else{
            switch ($data['type']) {
                case 'qq';
                    
                    $qq = qk_get_option('oauth_qq');
                    
                    $url = 'https://graph.qq.com/user/get_user_info?access_token='.$data['access_token'].'&oauth_consumer_key=' .$qq['app_id']. '&openid='.$data['openid'].'&format=json';
                    $data['nickname'] = 'nickname';
                    $data['avatar'] = 'figureurl_qq_2';
                    $data['avatar1'] = 'figureurl_qq_1';
                    $data['sex'] = 'gender';
                    break;
                case 'weibo':
                    $url = 'https://api.weibo.com/2/users/show.json?uid='.$data['openid'].'&access_token='.$data['access_token'];
                    $data['nickname'] = 'name';
                    $data['avatar'] = 'avatar_large';
                    $data['sex'] = 'gender';
                    break;
                case 'weixin':
                    $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $data['access_token'] . '&openid=' . $data['openid'];
                    $data['nickname'] = 'nickname';
                    $data['avatar'] = 'headimgurl';
                    $data['sex'] = 'sex';
                    break;
            }

            $user = wp_remote_get($url);

            if(is_wp_error($user)){
                return array('error'=>$user->get_error_message());
            }
    
            $user = json_decode($user['body'],true);

            if(isset($user['ret']) && $user['ret'] != 0){
                return array('error'=>sprintf('错误代码：%s；错误信息：%s；请在百度中搜索相关错误代码进行修正。',$user['ret'],$user['msg']));
            }
    
            
            $avatar = $user[$data['avatar']];
            
            if($data['type'] === 'qq' && !$avatar){
                $avatar = $user[$data['avatar1']];
            }

            if($data['type'] === 'weibo' && $data['type'] === 'sina'){
                $avatar = '';
            }
            
            $data['nickname'] = $user[$data['nickname']];

            $data['avatar'] = str_replace('http://','https://',$avatar);

            $data['sex'] = isset($user[$data['sex']]) ? $user[$data['sex']] : 0;
        }
        

        return $data;
    }
    
    public static function get_token($arg,$type,$code){

        $arg['code'] = $code;
        $arg['grant_type'] = 'authorization_code';
        $arg['redirect_uri'] = QK_HOME_URI.'/oauth?type='.$type;

        if($type == 'weixin'){
            $arg['redirect_uri'] = str_replace(array('http://','https://'),'',QK_HOME_URI);
        }

        $res = wp_remote_post($arg['url'], 
            array(
                'method' => 'POST',
                'body' => $arg,
            )
        );

        if(is_wp_error($res)){
            return array('error'=>$res->get_error_message());
        }

        $data = array();
        switch ($type) {
            case 'qq';
                if(strpos($res['body'], 'callback') !== false){
                    $lpos = strpos($res['body'], '(');
                    $rpos = strrpos($res['body'], ')');
                    $res  = substr($res['body'], $lpos + 1, $rpos - $lpos -1);
                    $msg = json_decode($res);
                    if(isset($msg->error)){
                        return array('error'=>sprintf('错误代码：%s；错误信息：%s；请在百度中搜索相关错误代码进行修正。',$msg->error,$msg->error_description));
                    }
                }
                $params = array();
                parse_str($res['body'], $params);

                $res = wp_remote_get('https://graph.qq.com/oauth2.0/me?access_token=' .$params['access_token']);

                if(is_wp_error($res)){
                    return array('error'=>$res->get_error_message());
                }

                $res = $res['body'];

                if (strpos ( $res, 'callback' ) !== false) {
                    $lpos = strpos ( $res, '(' );
                    $rpos = strrpos ( $res, ')' );
                    $res = substr ( $res, $lpos + 1, $rpos - $lpos - 1 );
                }

                $res = json_decode ($res,true);
                if (isset ( $res->error )) {
                    return array('error'=>sprintf('错误代码：%s；错误信息：%s；请在百度中搜索相关错误代码进行修正。',$msg->error,$msg->error_description));
                }

                $data = array(
                    'access_token'=>$params['access_token'],
                    'openid'=>$res['openid'],
                    'type'=>'qq'
                );
                break;
            case 'weibo';
                $msg = json_decode($res['body'],true);
                if(isset($msg['error'])){
                    return array('error'=>sprintf('错误代码：%s；错误信息：%s；请在百度中搜索相关错误代码进行修正。',$msg['error'],$msg['error_description']));
                }
                $data = array(
                    'access_token'=>$msg['access_token'],
                    'openid'=>$msg['uid'],
                    'type'=>'weibo'
                );
                break;
            case 'weixin';
                $msg = json_decode($res['body'],true);
                
                if(isset($msg['errcode'])){
                    return array('error'=>sprintf('错误代码：%s；错误信息：%s；请在百度中搜索相关错误代码进行修正。',$msg['errcode'],$msg['errmsg']));
                }
                $data = array(
                    'access_token'=>$msg['access_token'],
                    'openid'=>$msg['openid'],
                    'unionid'=>isset($msg['unionid']) && $msg['unionid'] != '' ? $msg['unionid'] : '',
                    'type'=>'weixin'
                );
                break;
        }
        
        $data['type'] = $type;

        return self::social_login($data);
    }
    
    //获取已启用的社交登录
    public static function get_enabled_oauths(){
        
        $types = get_oauth_types();
        $options = qk_get_option();
        $data = array();
        //$baseUrl = qk_get_custom_page_url('social-login').'?type=';
        
        $qq_open = isset($options['oauth_qq_open']) && $options['oauth_qq_open'];
        $weixin_open = isset($options['oauth_weixin_open']) && $options['oauth_weixin_open'];
        $weibo_open = isset($options['oauth_weibo_open']) && $options['oauth_weibo_open'];
        $juhe_open = isset($options['oauth_juhe_open']) && $options['oauth_juhe_open'];
        
        if($qq_open && isset($types['qq'])) {
            $data['qq'] = $types['qq'];
            $data['qq']['url'] = self::social_oauth_login('qq')['url'];
        }
    
        if($weixin_open && isset($types['weixin'])) {
            $data['weixin'] = $types['weixin'];
            //$data['weixin']['url'] = $baseUrl.'weixin';
        }
        
        if($weibo_open && isset($types['sina'])) {
            $data['weibo'] = $types['sina'];
            $data['weibo']['url'] = self::social_oauth_login('weibo')['url'];
        }
        
        //聚合
        if($juhe_open) {
            $juhe = $options['oauth_juhe'];
            if(isset($juhe['types']) && !empty($juhe['types']) && is_array($juhe['types'])){
                foreach ($juhe['types'] as $key => $value) {
                    if(!isset($data[$value]) && isset($types[$value])) {
                        $type = 'juhe_'.$value;
                        $data[$type] = $types[$value];
                        //$data[$type]['url'] = $baseUrl.$type;
                    }
                }
            }
        }
        
        return apply_filters('qk_oauth_links_arg', $data);
    }
    
    //社交跳转登录
    public static function social_oauth_login($type){
        // $oauths = self::get_enabled_oauths($type);
        
        // if(!isset($oauths[$type])) return array('error'=>'参数错误');
        $options = qk_get_option();
        
        // $qrcode = '';
        // $url = '';
        $array = array();
        
        //获取聚合登录连接
        if(strpos($type,'juhe_') !== false){
            $juhe = $options['oauth_juhe'];
            
            if($juhe) {
                $gateway = rtrim(trim($juhe['gateway'], " \t\n\r\0\x0B\xC2\xA0"),'/');
                
                //构造请求参数
                $params = array(
                    'act' => 'login',
                    'appid' => trim($juhe['app_id'], " \t\n\r\0\x0B\xC2\xA0"),
                    'appkey' => trim($juhe['app_key'], " \t\n\r\0\x0B\xC2\xA0"),
                    'type' => str_replace('juhe_', '', $type),
                    'redirect_uri' => QK_HOME_URI.'/oauth?_type='.$type,
                    'state' => md5(uniqid(rand(), TRUE))
                );
                
                $api = $gateway.'/connect.php?'.http_build_query($params);
                $res = wp_remote_post($api);
                
                if(is_wp_error($res)){
                    return array('error'=>'网络错误，请稍后再试');
                }
                
                if($res['response']['code'] == 200){
                    $data = json_decode($res['body'],true);
        
                    if(isset($data['code']) && (int)$data['code'] == 0){
                        if(isset($data['qrcode']) && $data['qrcode']){
                            $array['qrcode'] = '';//$data['qrcode'];
                        }
            
                        if(isset($data['url']) && $data['url']){
                            $array['url'] = $data['url'];
                        }
                    }else {
                        return array('error'=>$data['msg']);
                    }
                    
                }
            }
            
        }else {
            if($type == 'qq') {
                $qq = $options['oauth_qq'];
                
                $params = array(
                    'client_id' => trim($qq['app_id'], " \t\n\r\0\x0B\xC2\xA0"),
                    'response_type' => 'code',
                    'redirect_uri' => QK_HOME_URI.'/oauth?type=qq',
                    'state' => md5(uniqid(rand(), TRUE))
                );
                
                $array['url'] = 'https://graph.qq.com/oauth2.0/authorize?'.http_build_query($params);
            }
            //微信
            else if($type == 'weixin') {
                
            }
            //微博
            else if($type == 'weibo') {
                $weibo = $options['oauth_weibo'];
                
                $params = array(
                    'client_id' => trim($weibo['app_id'], " \t\n\r\0\x0B\xC2\xA0"),
                    'response_type' => 'code',
                    'redirect_uri' => QK_HOME_URI.'/oauth?type=weibo',
                );
                
                $array['url'] = 'https://api.weibo.com/oauth2/authorize?'.http_build_query($params);
            }
        }
        
        if(isset($array['url']) || isset($array['qrcode'])){
            return $array;
        }
        
        return array('error'=>'网络错误，请稍后再试');
    }
}