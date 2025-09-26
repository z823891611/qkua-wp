<?php
use Qk\Modules\Common\Login;
use Qk\Modules\Common\User;

$user_id =  get_current_user_id();
$user_data = get_userdata($user_id);

$security_info = User::get_account_security_info();

$oauth = User::get_user_oauth_info($user_id);

?>
<div class="secure-page right-wrap">
    <div class="secure-header">
        <div class="secure-info">
            <span class="secure-shield"><?php echo $security_info['score'] ?></span>
            <span class="secure-desc no-risk"><?php echo $security_info['status'] ?></span>
            <p class="secure-suggest">为了更好的保障您账号的安全，请您继续完善：<span><?php echo $security_info['suggest'] ?></span></p>
        </div>
    </div>
    <div class="secure-content">
        <div class="section-title">账号设置</div>
        <div class="secure-setting-list">
            <ul>
                <li class="setting-item">
                    <div class="left">
                        <div class="title">密码</div>
                        <div class="desc">安全性高的密码可以使帐号更安全</div>
                    </div>
                    <div class="right" @click="BindType('password')">修改密码</div>
                </li>
                <li class="setting-item">
                    <div class="left">
                        <div class="title">邮箱</div>
                        <div class="desc"><?php echo $user_data->user_email ?: '未绑定邮箱，绑定邮箱后可以通过邮箱登录账户和找回密码。'?></div>
                    </div>
                    <div class="right" @click="BindType('email')"><?php echo $user_data->user_email ? '更换' : '绑定'?>邮箱</div>
                </li>
                <li class="setting-item">
                    <div class="left">
                        <div class="title">手机</div>
                        <div class="desc"><?php echo Login::is_phone($user_data->user_login) ? $user_data->user_login : '您还没有绑定手机'?></div>
                    </div>
                    <div class="right" @click="BindType('phone')"><?php echo Login::is_phone($user_data->user_login) ? '更换' : '绑定'?>手机</div>
                </li>
            </ul>
        </div>
    </div>
    <div class="secure-account-binding">
        <div class="section-title">社交账号绑定</div>
        <div class="secure-setting-list">
            <ul>
                
                <?php foreach ($oauth as $key => $value) { ?>
                    <li class="setting-item">
                        <div class="left">
                            <div class="title"><?php echo $value['name'] ?></div>
                            <div class="desc"><?php echo $value['is_binding'] ? $value['user_name']?:'已绑定' : '未绑定' ?></div>
                        </div>
                        <div class="right">
                            <?php if($value['is_binding']) { ?>
                                <span @click="unBinding('<?php echo $key ?>')">解绑</span>
                            <?php }else{ ?>
                                <span @click="binding('<?php echo $key ?>')">绑定</span>
                            <?php } ?>
                        </div>
                    </li>
                <?php } ?>
                
                <!--<li class="setting-item">-->
                <!--    <div class="left">-->
                <!--        <div class="title">微信</div>-->
                <!--        <div class="desc">未绑定</div>-->
                <!--    </div>-->
                <!--    <div class="right">绑定</div>-->
                <!--</li>-->
            </ul>
        </div>
    </div>
</div>