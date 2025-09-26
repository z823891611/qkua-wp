<?php namespace Qk\Modules\Templates;

/***vue相关组件模板****/
class VueTemplates{
    public static function init(){
        add_action( 'wp_enqueue_scripts', array(__CLASS__,'scripts_init'));
    }
    
    public static function scripts_init(){
        // $mark = preg_replace( '/^https?:\/\//', '', $_SERVER['SERVER_NAME'] );
        // $mark = str_replace('.','_',$mark);
        
        $is_singular = is_singular();
        
        //密码验证
        $verification = qk_get_option('password_verify');
        $verification = !empty($verification) ? $verification : array();
        $verification['length'] = !empty($verification['code']) ? qkGetStrLen($verification['code']) : 4;
        unset($verification['code']);
        
        wp_localize_script( 'vue', 'qk_global',apply_filters('qk_global_settings',array(
            'is_home' => is_home(),
            'home_url'=> QK_HOME_URI,
            'rest_url' => get_rest_url(), //api
            'site_name' => get_bloginfo('name'),
            'login' => self::login_templates(),
            'post_id' => $is_singular ? get_the_id() : 0,
            'author_id'=>$is_singular ? get_post_field('post_author') : 0, //文章作者
            //代码高亮主题
            'highlightjs_theme' => $is_singular ? qk_get_option('highlightjs_theme') : null,
            //代码高亮主题
            'highlightjs_show' => $is_singular ? qk_get_option('single_breadcrumb_open') : null,
            //密码验证
            'password_verify' => $is_singular || apply_filters('qk_is_page', 'circle') || is_tax('circle_cat') || is_tax('topic') ? $verification : null,
            'product' => 'qkua',
        )));
        
    }
    
    public static function login_templates(){
        //是否允许注册
        $allow_regeister = qk_get_option('allow_register');
         
        $register_check = qk_get_option('allow_register_check');
        $check_type = $register_check ? qk_get_option('register_check_type') : '';
        
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
        
        //用户协议与隐私政策
        $agreement = qk_get_option('agreement');
        $agreement = $agreement ? $agreement : array();
        
        return '<div class="login-container" v-if="login">
            <div class="container-top">
                <div class="title" v-text="loginTitle"></div>
                <p style=" font-size: 14px; color: var(--color-text-secondary); margin-top: 12px; " v-if="loginType == 2 && login.invite_type != 0 && !invitePass">没有邀请码？<a href="获取邀请码地址" class="active">获取邀请码</a></p>
            </div>
            <div class="container-content form-container">
                <form @submit.stop.prevent="loginSubmit">
                    <div class="invite-box" v-if="loginType == 2 && login.invite_type != 0 && !invitePass">
                        <label class="form-item">
                            <input type="text" name="invite_code" v-model="data.invite_code" tabindex="1" spellcheck="false" autocomplete="off" placeholder="请输入邀请码"> 
                        </label>
                        <div class="form-button">
                            <button>提交</button>
                        </div>
                        <div class="invite-skip" v-if="login.invite_type == 2"><span @click.stop.prevent="invitePass = true;">跳过</span></div>
                    </div>
                    <div class="login-box" v-else>
                        <label class="form-item nickname" v-show="loginType == 2">
                            <input type="text" name="nickname" v-model="data.nickname" tabindex="1" spellcheck="false" autocomplete="off" placeholder="请输入昵称"> 
                        </label>
                        <label class="form-item">
                            <input type="text" name="username" v-model="data.username" tabindex="2" spellcheck="false" autocomplete="off" placeholder="请输入'.$login_text.'"> 
                        </label>
                        <label class="form-item" v-show="loginType == 2 && data.username" v-if="login.check_type">
                            <input type="text" name="code" v-model="data.code" tabindex="3" spellcheck="false" autocomplete="off" placeholder="请输入验证码"> 
                            <div class="login-eye text" @click.stop.prevent="countdown == 60 ? getCode() : null">{{countdown < 60 ? countdown+\'秒后可重发\' : \'发送验证码\'}}</div>
                        </label>
                        <label class="form-item">
                            <input type="password" name="password" v-model="data.password" tabindex="4" autocomplete="off" spellcheck="false" placeholder="请输入密码">
                        </label>
                        <div class="signin-hint">
                            <div class="hint-text" v-if="loginType == 1 && login.allow_register == 1">还没有账号? <span @click="loginType = 2">前往注册</span></div>
                            <div class="hint-text" v-if="loginType == 2">已有帐号? <span @click="loginType = 1">立即登录</span></div>
                            <a href="'.qk_get_custom_page_url('forgot').'" target="_blank" class="forget-password" v-if="loginType == 1">忘记密码？</a>
                        </div>
                        <div class="form-button">
                            <button v-text="buttonText"></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="social-loginbar" v-if="!(loginType == 2 && login.invite_type != 0 && !invitePass)">
                <div class="separator-text" v-if="Object.keys(oauths).length">或</div>
                <div class="other-login" v-if="Object.keys(oauths).length">
                    <a href="javascript:void(0)" class="no-hover" :class="item.type" v-for=" (item,index) in oauths"  @click="socialLogin(index)"  :key="item.type"><i :class="item.icon"></i></a>
                </div>
                <div class="agreement" v-show="loginType == 1">登录表示您已阅读并同意<span><a href="'.$agreement['agreement'].'" target="_blank">用户协议</a></span>和<span><a href="'.$agreement['privacy'].'" target="_blank">隐私政策</a></span></div>
            </div>
        </div>';
    }
}