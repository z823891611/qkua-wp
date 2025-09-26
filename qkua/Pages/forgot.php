<?php
/**
 * 忘记密码重设密码
 * 
 * */
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
<body <?php body_class(); ?>>
<div id="page" class="site">
    <div id="content" class="site-content">
        <div class="content-wrapper">
            <div class="oauth-page wrapper">
                <?php echo QK\Modules\Templates\Header::logo() ?>
                <div id="forgot-wrap" class="forgot-wrap" ref="forgotWrap" v-cloak>
                    <div class="forgot-title">
                        <div class="title">
                            <h2>找回账号密码</h2>
                            <p>请输入您账号绑定的手机号码或邮箱地址</p>
                        </div>
                    </div>
                    <div class="form-container">
                        <form @submit.stop.prevent="resetPassword">
                            <label class="form-item">
                                <input type="text" name="username" v-model="data.username" tabindex="1" spellcheck="false" autocomplete="off" placeholder="请输入绑定的手机号或邮箱">
                            </label>
                            <label class="form-item">
                                <input type="text" name="code" v-model="data.code" tabindex="2" spellcheck="false" autocomplete="off" placeholder="请输入验证码">
                                <div class="login-eye text" @click.stop.prevent="countdown == 60 ? getCode() : null">{{countdown < 60 ? countdown + '秒后可重发' : '发送验证码'}}</div>
                            </label>
                            <label class="form-item">
                                <input type="password" name="password" v-model="data.password" tabindex="3" spellcheck="false" autocomplete="off" placeholder="新密码">
                            </label>
                            <label class="form-item">
                                <input type="password" name="repassword" v-model="data.confirmPassword" tabindex="4" spellcheck="false" autocomplete="off" placeholder="确认密码">
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

.forgot-wrap {
    box-shadow: 0 12px 24px 0 rgba(28,31,33,0.10);
    border-radius: 12px;
    padding: 32px 32px 16px;
    background-color: var(--bg-main-color);
    max-width: 384px;
    width: 100%;
    margin: 0 auto;
}

.forgot-title {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    align-items: center;
}

.forgot-title h2 {
    font-size: 18px;
}

.forgot-title p {
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

