<?php
use Qk\Modules\Common\Oauth;

/**
 * 社交登录回调地址
 * 
 * */

if(!empty($_POST)){
    $data = $_POST;
}elseif(!empty($_GET)){
    $data = $_GET;
}else{
    $data = file_get_contents('php://input');
}

$code = isset($data['code']) ? $data['code'] : '';
$state = isset($data['state']) ? $data['state'] : '';
$type = isset($data['type']) ? $data['type'] : '';
$type = isset($data['_type']) && !empty($data['_type']) ? $data['_type'] : $type;

$res = Oauth::init($type,$code);

if(isset($res['error']) || empty($res)){
    wp_die($res['error'],'QK主题提示');
}else if ($res === true || (is_array($res) && count($res) === 1 && isset($res['token']))) {
    // 数组只包含token
    $referer_url = qk_getcookie('qk_referer_url');
    $referer_url = !empty($referer_url) ? $referer_url : QK_HOME_URI;
    
    //跳转到来路地址
    header('location:' . $referer_url);
    exit;
}

//print_r($res);
?>

<!doctype html>
<html <?php language_attributes(); ?> class="avgrund-ready">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Cache-Control" content="no-transform" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <meta name="renderer" content="webkit"/>
    <meta name="force-rendering" content="webkit"/>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1"/>
    <link rel="profile" href="http://gmpg.org/xfn/11">
        
    <?php wp_head();?>

</head>
<?php
wp_localize_script( 'qk-main', 'qk_social',$res);
?>
<body <?php body_class(); ?>>

<div id="page" class="site">
    <div id="content" class="site-content">
        <div class="content-wrapper">
            <div class="oauth-page wrapper">
                <?php echo QK\Modules\Templates\Header::logo() ?>
                <div id="social-wrap" class="social-wrap" ref="socialWrap" v-cloak>
                    <div class="social-invite" v-if="!invitePass && social.invite_type != '0'">
                        <div class="social-title">
                            <div class="title">
                                <h2>绑定邀请码</h2>
                                <p v-html="social.invite_url"></p>
                            </div>
                            <div class="user-avatar" v-if="social.social_user">
                                <img :src="social.social_user.avatar" width="48" height="48" alt="头像" title="头像" class="avatar-face">
                            </div>
                        </div>
                        <div class="form-container">
                            <form @submit.stop.prevent="checkInviteCode">
                                <label class="form-item">
                                    <input type="text" name="invite_code" v-model="data.invite_code" tabindex="1" spellcheck="false" autocomplete="off" placeholder="请输入邀请码">
                                </label>
                                <div class="form-button">
                                    <button>提交</button>
                                </div>
                            </form>
                        </div>
                        <div class="invite-skip" v-if="social.invite_type == 2"><span @click.stop="skipInvite">跳过</span></div>
                    </div>
                    <div class="social-binding-login" v-else-if="social.binding_type && (showBinding || social.invite_type == '0')">
                        <div class="social-title">
                            <div class="title">
                                <h2>绑定{{bindingTypeName}}</h2>
                                <p>通过绑定{{bindingTypeName}}提高账号安全</p>
                            </div>
                            <div class="user-avatar" v-if="social.social_user">
                                <img :src="social.social_user.avatar" width="48" height="48" alt="头像" title="头像" class="avatar-face">
                            </div>
                        </div>
                        <div class="form-container">
                            <form  @submit.stop.prevent="bindingLogin">
                                <label class="form-item">
                                    <input type="text" name="teloremail" v-model="data.teloremail" tabindex="1" spellcheck="false" autocomplete="off" :placeholder="'请输入'+bindingTypeName">
                                </label>
                                <label class="form-item">
                                    <input type="text" name="code" v-model="data.code" tabindex="2" spellcheck="false" autocomplete="off" placeholder="请输入验证码">
                                    <div class="login-eye text" @click.stop.prevent="countdown == 60 ? getCode() : null">{{countdown < 60 ? countdown+'秒后可重发' : '发送验证码'}}</div>
                                </label>
                                <div class="form-button">
                                    <button>提交</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.header-logo {
    padding: 47px;
    display: flex;
    justify-content: center;
}

.logo {
    display: block;
}

.header-logo img {
    height: calc(var(--top-menu-height) - 10px);
}

.header-logo .site-title {
    font-size: 26px;
    letter-spacing: 3px;
    font-weight: 600;
    color: var(--color-primary);
    line-height: 1;
}

.social-wrap {
    box-shadow: 0 12px 24px 0 rgba(28,31,33,0.10);
    border-radius: 12px;
    padding: 32px 32px 16px;
    background-color: var(--bg-main-color);
    max-width: 384px;
    width: 100%;
    margin: 0 auto;
}

.social-title {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    align-items: center;
}

.social-title h2 {
    font-size: 18px;
}

.social-title p {
    font-size: 14px;
    color: var(--color-text-secondary);
}

.user-avatar {--avatar-size: 48px;}

.invite-skip {
    text-align: center;
    color: var(--color-primary);
    padding-bottom: 8px;
}

.invite-skip span {
    cursor: pointer;
}

</style>
<?php wp_footer(); ?>
</body>
</html>

