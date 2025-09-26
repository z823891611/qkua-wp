<?php
$user_id =  get_current_user_id();
$user_data = \Qk\Modules\Common\User::get_user_public_data($user_id);
$user_vip = $user_data['vip'];
?>
<div class="vip-page box qk-radius" style=" padding: 16px; " ref="vipPage">
    <div class="vip-header">
        <?php echo $user_data['avatar_html'];?>
        <div class="user-info">
            <div class="user-name"><?php echo $user_data['name'];?></div>
            <div class="vip-info"><?php echo (!$user_vip ? '未开通' : ( $user_vip['name'].'：'.($user_vip['time'] == 0 ? '永久' : $user_vip['date'].'到期'))) ?></div>
        </div>
    </div>
    <div class="vip-rights">
        <div class="section-title">会员权益<span style="
    font-size: 12px;
    color: var(--color-text-secondary);
    margin-left: 8px;
">每日凌晨12点刷新次数</span></div>
        <div class="rights-list">
            <div class="rights-item" style="
    opacity: 1;
">
                <div class="icon"><i class="ri-advertisement-fill"></i></div>
                <div class="info">
                    <div class="title">屏蔽广告</div>
                    
                </div>
            </div>
            <div class="rights-item">
                <div class="icon"><i class="ri-gift-2-fill"></i></div>
                <div class="info">
                    <div class="title">额外奖励</div>
                    <div class="count">已获得20积分，30经验</div>
                </div>
            </div>
            <div class="rights-item">
                <div class="icon"><i class="ri-vimeo-fill"></i></div>
                <div class="info">
                    <div class="title">观看付费视频</div>
                    <div class="count">剩余 5 次</div>
                </div>
            </div>
            <div class="rights-item">
                <div class="icon"><i class="ri-download-cloud-fill"></i></div>
                <div class="info">
                    <div class="title">下载所有资源</div>
                    <div class="count">剩余 5 次</div>
                </div>
            </div>
        <div class="rights-item">
                <div class="icon"><i class="ri-eye-2-fill"></i></div>
                <div class="info">
                    <div class="title">查看隐藏内容</div><div class="count">剩余 5 次</div>
                    
                </div>
            </div>
        </div>
        <p class="more-pls" style="
    color: var(--color-primary);
    text-align: center;
    margin: 16px 0;
">更多权益接入中…</p>
    </div>
</div>

<style>
    .vip-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--bg-text-color);
    border-radius: 12px;
    height: 180px;
    background-size: 100% 100%;
    /* width: 80%; */
    /* margin: 0 auto; */
    /* margin-top: 40px; */
    color: var(--color-white);
    background: url(https://cdn.dancf.com/fe-assets/20230312/Desktop/310f784b444a8d37d64ce94ce1f14cdb.png) 100% 100% / contain no-repeat;
    background-color: rgba(56, 88, 246, 0.7);
    justify-content: center;
    text-align: center;
}

.vip-header .user-avatar {
    width: 90px;
    height: 90px;
    border: 5px solid var(--bg-main-color);
    border-radius: 100%;
    --avatar-size: 65px;
}

.vip-header .user-name {
    font-size: 18px;
    margin: 0;
}

.user-info {
    margin-top: 8px;
}


.vip-rights .section-title {
    margin-bottom: 16px;
    margin-top: 24px;
    font-size: 18px;
}
.rights-item {
    background: var(--bg-muted-color);
    border-radius: 12px;
    padding: 24px 0;
    width: calc(20% - 13px);
    min-width: 154px;
    /* border: 1px solid var(--border-color-base); */
    text-align: center;
    /* opacity: .4; */
}

.rights-list {
    display: flex;
    flex-wrap: wrap;
    grid-gap: 16px;
    justify-content: center;
}

.rights-item .icon i {
    font-size: 40px;
    color: var(--color-primary);
}

.info .title {
    margin-bottom: 4px;
    font-size: 14px;
}

.info {
    margin-top: 8px;
}

.count {
    font-size: 12px;
    color: var(--color-text-secondary);
}

</style>