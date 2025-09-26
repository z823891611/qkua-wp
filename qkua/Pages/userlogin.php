<?php
/**
 * 登录与注册
 */
get_header();

?>

    <button onclick="app.addhtml('aaaaaaaaa')"> addhtml</button>
    <button onclick="app.addComponent('bcomponent')"> addComponent</button>
    <div id="modal" class="modal" @click="close" ref="modal">
          <div class="modal-dialog">
                <div class="modal-content" v-if="html" v-html="html" @click.stop=""></div>
                <component :is="component" v-else></component>
          </div>
    </div>

<div class="modal" style="display: none;">
    <div class="modal-dialog">
        <div class="login-container box">
            <div class="container-top">
                <div class="title">注册</div>
                <div class="login-tip">没有帐号？立即注册</div>
            </div>
            <div class="container-content">
                <form>
                    <label class="form-item">
                        <input type="text" name="nickname" tabindex="1" spellcheck="false" autocomplete="off"> 
                        <span>可爱的昵称</span>
                    </label>
                    <label class="form-item">
                        <input type="text" name="username" tabindex="2" spellcheck="false" autocomplete="off"> 
                        <span>登录手机号</span>
                    </label>
                    <label class="form-item">
                        <input type="text" name="checkCode" tabindex="3" spellcheck="false" autocomplete="off"> 
                        <span>验证码</span> 
                        <div class="login-eye button text">发送验证码</div>
                    </label>
                    <label class="form-item">
                        <input name="password" tabindex="4" autocomplete="off" spellcheck="false" type="password">
                        <span>密码</span>
                    </label>
                </form>
                <div class="login-button">
                    <button>快速登录</button>
                </div>
            </div>
            <div class="social-loginbar">
                <div class="social-separator-text">社交帐号登录</div>
            </div>
        </div>
        <!---#login-container--->
    </div>
</div>

<?php

get_footer();