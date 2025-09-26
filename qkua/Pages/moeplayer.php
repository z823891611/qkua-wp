<?php
/**
 * MoePlayer
 */
get_header();

?>
<div class="moe-single-content wrapper">
    <div id="primary-home" class="content-area" style="flex: 1;">
        <h1 id="main-title" style=" margin: 1.8rem auto; font-size: 3rem; font-weight: 600; line-height: 1.25; text-align: center; "> MoePlayer 功能开发中.... </h1>
        <div style="position: relative; height: 0; padding-top: calc(56.25% + 46px); background-color: #262626;">
            <div id="moeplayer"></div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://www.qkua.com/wp-content/themes/moe/Assets/fontend/moeplayer/index.css">
<script type='text/javascript' src='https://www.qkua.com/wp-content/themes/moe/Assets/fontend/moeplayer/lottie.min.js'></script>
<script type='text/javascript' src='https://www.qkua.com/wp-content/themes/moe/Assets/fontend/moeplayer/hls.min.js'></script>
<script type='text/javascript' src='https://www.qkua.com/wp-content/themes/moe/Assets/fontend/moeplayer/MoePlayer.js' id='moe-js-moeplayer-js'></script>
<script type='text/javascript' src='https://www.qkua.com/wp-content/themes/moe/Assets/fontend/moeplayer/CommentCoreLibrary.min.js' id='moe-js-moeplayer-js'></script>
<style>
    #moeplayer {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    width: 100%; 
    height: 100%;
}
</style>
<script>
     const mp = new MoePlayer({
            container: document.getElementById('moeplayer'),
            autoplay:true,
            autonext:true,
            theme:"#ff6d6d",
            videoList:[
                {
                    pic:"https://www.qkua.com/wp-content/uploads/2023/02/1abc48d65bf3ef_1_post.jpg",
                    url:'https://hls.kawayi.cc/Yjd8/0846014a3cf11d68d85968fcedbd1799.m3u8',
                    title:'第1话 or not to [B]e',
                },
                {
                    pic:"https://www.qkua.com/wp-content/uploads/2023/02/1846b8cff733be_1_post.jpg",
                    url:'https://hls.kawayi.cc//Yjd8/351ad11a9497c9426ccffd60ece3d81b.m3u8',
                    title:'第2话 city e[S]cape',
                },
                {
                    //pic:"https://www.qkua.com/wp-content/uploads/2022/07/0064OotGgy1h3varyl93aj318g0p0dpi.jpg",
                    url:'https://kh4.psdcat.com/playm3u8/1673341409_aabbcc05_wanshiwdzt01.m3u8',
                    title:'第3话 break ti[M]e',
                },
                {
                    pic:"https://www.qkua.com/wp-content/uploads/2022/08/0064OotGgy1h3vasg39owj318g0p0dlc.jpg",
                    url:'https://kh4.psdcat.com/playm3u8/1676737874_aabbcc05_nierjxjy04.m3u8',
                    title:'第4话 mountain too [H]igh',
                },
            ],
            videoIndex:0,
            danmaku: {
                id: '9E2E3368B56CDBB4',
                sendApi: 'https://www.qkua.com/wp-json/moe/v1/sendDanmaku',
                getApi: 'https://www.qkua.com/wp-json/moe/v1/getDanmaku',
            },
        });
</script>

<?php

get_footer();