<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Injects small helper panel and JS on write page.
 */
add_action('wp_footer', function (){
    if ( ! is_page('write') ) return;
    ?>
    <style id="qkua-steam-front-css">
      #steam-extra .el-input__inner{margin:8px 0}
    </style>
    <script id="qkua-steam-front-inline">
    (function(){
      function wait(cb){
        var t = setInterval(function(){
          if (document.querySelector('#write') && window.Vue) {
            clearInterval(t); cb();
          }
        }, 300);
        setTimeout(function(){clearInterval(t)}, 20000);
      }
      function htmlEscape(s){return (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
      wait(function(){
        try{
          var write = document.getElementById('post-setting');
          if (write && !document.getElementById('steam-extra')){
            var box = document.createElement('div');
            box.className='box';
            box.innerHTML = '<div class="widget-title">Steam 扩展字段</div>'+
                            '<div id="steam-extra" class="write-select-box">'+
                            '<input class="el-input__inner" id="qkua_steam_video" placeholder="视频地址（可选）">'+
                            '<input class="el-input__inner" id="qkua_steam_dl1" placeholder="学习版 下载地址">'+
                            '<input class="el-input__inner" id="qkua_steam_dl2" placeholder="清单 下载地址">'+
                            '</div>';
            write.appendChild(box);
          }
        }catch(e){}
        // Try default role/category
        try{
          var vm = document.querySelector('#write').__vue__;
          // 固定分类
          if (SteamScraper && SteamScraper.cfg && SteamScraper.cfg.fixCategory){
            var id = parseInt(SteamScraper.cfg.categoryId || 0);
            if (id && Array.isArray(vm.cats)) vm.cats = [id];
          }
          // 锁定为积分支付
          if (SteamScraper && SteamScraper.cfg && SteamScraper.cfg.lockCredit){
            vm.role.key = 'credit';
            vm.role.num = parseInt(SteamScraper.cfg.creditAmount || 500);
          }
        }catch(e){}

        function inject(){
          var v = document.getElementById('qkua_steam_video')?.value.trim();
          var d1= document.getElementById('qkua_steam_dl1')?.value.trim();
          var d2= document.getElementById('qkua_steam_dl2')?.value.trim();
          if (!v && !d1 && !d2) return;
          var extra='';
          if (v)  extra += '<p>[video]'+htmlEscape(v)+'[/video]</p>';
          if (d1) extra += '<p>学习版: '+htmlEscape(d1)+'</p>';
          if (d2) extra += '<p>清单: '+htmlEscape(d2)+'</p>';
          // TinyMCE 优先
          if (window.tinymce && tinymce.get('post_content')){
            var ed = tinymce.get('post_content');
            ed.setContent((ed.getContent()||'') + extra);
          }else{
            var ta = document.getElementById('post_content');
            if (ta) ta.value = (ta.value||'') + extra;
          }
        }

        var publish = document.querySelector('.write-footer .publish');
        var draft   = document.querySelector('.write-footer .draft');
        publish && publish.addEventListener('click', inject);
        draft   && draft.addEventListener('click', inject);
      });
    })();
    </script>
    <?php
}, 999);
