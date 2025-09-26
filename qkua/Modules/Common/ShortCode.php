<?php namespace Qk\Modules\Common;

class ShortCode{
    public function init(){
        if(!is_admin()){
            //文件下载
            //add_shortcode( 'qk_file', array(__CLASS__,'file_down'));

            //隐藏内容
            add_shortcode('content_hide',array(__CLASS__,'content_hide'));
        }
    }
    
    public static function content_hide($atts,$content = null){
        
        $user_id = get_current_user_id();
        $post_id = get_the_id();

        $role = self::get_content_hide_arg($post_id,$user_id);
        
        if(!$role || is_array($role)){
            $str = preg_replace('/^<\/p>/', '', do_shortcode(wpautop($content))); // 去掉前面的</p>
            $str = preg_replace('/<p>$/', '', $str); // 去掉后面的<p>
        }else{
            $str = '<div class="content-show-roles">'.$role.'</div>';
                
        }
        
        return '<div class="content-hidden">
            <div class="content-hidden-info">
                '.$str.'
            </div>
        </div>';
    }
    
    public static function get_content_hide_arg($post_id,$user_id){
        //检查用户的权限
        $role = apply_filters('check_reading_hide_content_role',$post_id,$user_id);
        
        if(!is_array($role)) return '';
        
        if(isset($role['error']) || !$role['authority'] || $role['allow'] === true) return '';
        
        //登录可见
        if($role['authority'] === 'login' && !$user_id){
            return '
                <div class="left">
                    <div class="role-title"><i class="ri-lock-2-line"></i>内容已隐藏，请登录后查看</div>
                </div>
                <div class="rigth">'.self::login_button().'</div>
            ';
        }
        
        //密码可见
        if($role['authority'] === 'password' && $role['allow'] === false){

            return '
                <div class="left">
                    <div class="role-title"><i class="ri-lock-2-line"></i>内容已隐藏，请输入密码查看</div>
                </div>
                <div class="rigth">
                    <button onclick="passwordVerify('.$post_id.')">输入密码</button>
                </div>
            ';;
        }
        
        //评论可见
        if($role['authority'] === 'comment' && $role['allow'] === false){
            return '
                <div class="left">
                    <div class="role-title"><i class="ri-lock-2-line"></i>内容已隐藏，请评论后查看</div>
                </div>
                <div class="rigth">'.(!$user_id ? self::login_button() : '').'</div>
            ';
        }
        
        //限制等级可见
        if($role['authority'] === 'roles' && $role['allow'] === false){
            
            $li = '';
            foreach ($role['roles'] as $value) {
                $li .= '
                <li>'.(!empty($value['image']) ?
                    '<div class="lv-icon"><img src="'.$value['image'].'" alt="'.$value['name'].'"></div>':
                    '<div class="lv-name">'.$value['name'].'</div>').'
                </li>';
            }
            
            return '
                <div class="left">
                    <div class="role-title"><i class="ri-lock-2-line"></i>内容已隐藏，以下等级可查看</div>
                    <div class="post-roles">
                        <ul class="roles-list">'.$li.'</ul>
                    </div>
                </div>
                <div class="rigth">'.(!$user_id ? self::login_button() : '').'</div>
            ';
        }
        
        //支付可见
        if(($role['authority'] === 'money' || $role['authority'] === 'credit') && $role['allow'] === false && $role['value']){
            $data = array(
                'order_price'=>$role['value'],
                'order_type'=>'post_neigou',
                'post_id'=>$post_id,
                'title'=>wptexturize(qk_get_desc(0,200,get_the_title($post_id))),
                'type' => $role['authority'],
                'tag' => '隐藏内容'
            );

            return '
                <div class="left">
                    <div class="role-title"><i class="ri-lock-2-line"></i>内容已隐藏，请付费后查看</div>
                    <div class="role-price"><span>'.($role['authority'] === 'money' ? '￥': '<i class="ri-coin-line"></i>').'</span>'.$role['value'].'</div>
                </div>
                <div class="rigth">'.(!$user_id && ($role['authority'] === 'credit' || ( $role['authority'] === 'money' && !$role['not_login_pay']))?
                    self::login_button()
                    : '<button class="pay" onclick=\'qkpay('.json_encode($data,true).',"'.$role['authority'].'")\'>立即购买</button>').'
                </div>
            ';
        }
        
        $arg = array();
        $pattern = get_shortcode_regex();

        if (   preg_match_all( '/'. $pattern .'/s',get_post_field('post_content',$post_id) , $matches )
            && array_key_exists( 2, $matches )
            && in_array( 'content_hide', $matches[2] )
            && !empty($matches[0]))
        {
            foreach ($matches[0] as $k => $v) {
                if(strpos($v,'content_hide') !== false && strpos($v,'_content_hide') === false){
                    $content = str_replace(array('[content_hide]','[/content_hide]'),'',$v);
                    $content = str_replace( ']]>', ']]&gt;', $content);
                    $arg[] = do_shortcode(wpautop($content));
                }
            }
        }

        return $arg;
    }
    
    public static function login_button(){

        //是否允许注册
        $allow_register = qk_get_option('allow_register');

        $login = '
            <button class="login" onclick="createModal(\'login\')">登录</button>
            <button class="register" onclick="createModal(\'login\')">注册</button>
        ';

        return $login;
    }
    
    public static function  get_shortcode_content($content,$shortcode_name) {
        $pattern = get_shortcode_regex(array($shortcode_name)); 
        preg_match_all('/' . $pattern . '/', $content, $matches);
        
        $shortcode_content = '';
        
        if (!empty($matches[5])) {
            $shortcode_content = $matches[5][0];
            $content = str_replace($matches[0][0], '', $content);
        }

        
        $result = array(
            'content' => $content,
            'shortcode_content' => $shortcode_content
        );
        
        return $result;
    }
}