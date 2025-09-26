jQuery(document).ready(function($){
  // 防重复绑定
  $(document).off('click.steam', '#steam-scraper-btn');

  function escapeHtml(unsafe){
    if (unsafe === undefined || unsafe === null) return '';
    return String(unsafe).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
  }

  // 绑定按钮
  $(document).on('click.steam', '#steam-scraper-btn', function(e){
    e.preventDefault();
    var input = prompt('请输入 Steam 游戏URL或AppID（仅数字也可）：');
    if (!input) return;

    var $btn = $(this);
    $btn.prop('disabled', true).text('采集中…');

    $.post(SteamScraper.ajaxUrl, {
      action: 'steam_scraper_fetch_game',
      nonce: SteamScraper.nonce,
      steam_input: input
    }).done(function(res){
      $btn.prop('disabled', false).text('采集Steam游戏信息');
      if (!res || !res.success) {
        alert((res && res.data && res.data.msg) ? res.data.msg : '采集失败');
        return;
      }

      var g = res.data;

      // === 自动写标题（经典编辑器 + Gutenberg，带重试） ===
      (function setPostTitle(){
        try{
          var appid = (g.appid || '').toString();
          var cn = g.name_cn || g.title || '';
          var en = g.name_en || '';
          var dateIso = g.release_date_iso || '';
          cn = cn ? cn.replace(/\s+/g,' ').trim() : '';
          en = en ? en.replace(/\s+/g,' ').trim() : '';
          var parts = [];
          if (appid) parts.push('【' + appid + '】');
          if (cn && en && cn.toLowerCase() !== en.toLowerCase()) parts.push(cn + '/' + en);
          else parts.push(cn || en);
          if (dateIso) parts.push('「' + dateIso + '发行」');
          var postTitle = parts.join('');

          var attempts = 0;
          function trySet(){
            attempts++;
            var $classic = jQuery('#titlewrap #title, #title');
            var $gb = jQuery('input.editor-post-title__input');
            if ($classic.length){
              $classic.val(postTitle).trigger('input').trigger('change');
              return true;
            } else if ($gb.length){
              $gb.val(postTitle).trigger('input').trigger('change');
              return true;
            }
            if (attempts < 10){
              setTimeout(trySet, 200);
              return false;
            }
            return false;
          }
          trySet();
        }catch(e){}
      })();

      // === 将采集到的标签写入 WP 标签（经典编辑器） ===
      (function setPostTags(){
        try{
          if (!Array.isArray(g.tags) || !g.tags.length) return;
          var $box = jQuery('#tagsdiv-post_tag');
          var $input = jQuery('#new-tag-post_tag');
          var $add = $box.find('.tagadd');
          if ($box.length && $input.length && $add.length){
            var i = 0;
            function addNext(){
              if (i >= g.tags.length) return;
              var t = (g.tags[i] || '').toString().trim();
              i++;
              if (!t) { addNext(); return; }
              $input.val(t);
              $add.trigger('click');
              setTimeout(addNext, 150);
            }
            addNext();
          } else if ($input.length){
            $input.val(g.tags.join(','));
          }
        }catch(e){}
      })();

      // === 构建卡片HTML（单次插入/替换正文） ===
      var html =
        '<div class="steam-post-content">'
      +   '<div class="steam-db-links">'
      +     '<a class="steamdb-btn" href="steam://store/' + encodeURIComponent(g.appid) + '" target="_blank">游戏商店</a>'
      +     '<a class="steamdb-btn" href="https://steamdb.info/app/' + encodeURIComponent(g.appid) + '/dlc/" target="_blank">查看DLC</a>'
      +     '<a class="steamdb-btn" href="https://steamdb.info/app/' + encodeURIComponent(g.appid) + '/depots/" target="_blank">查看内容</a>'
      +     '<a class="steamdb-btn" href="steam://openurl/https://store.steampowered.com/account/remotestorageapp/?appid=' + encodeURIComponent(g.appid) + '" target="_blank">查看存档</a>'
      +     '<a class="steamdb-btn" href="https://steamdb.info/app/' + encodeURIComponent(g.appid) + '/ufs/" target="_blank">查看存档位置</a>'
      +     '<a class="steamdb-btn" href="https://steamdb.info/app/' + encodeURIComponent(g.appid) + '" target="_blank">查看全球价格</a>'
      +     '<a class="steamdb-btn" href="https://steamdb.info/app/' + encodeURIComponent(g.appid) + '/patchnotes/" target="_blank">查看更新记录</a>'
      +   '</div>'
      +   '<div class="steam-card-display">'
      +     '<div class="steam-card-inner">'
      +       '<div class="steam-card-left"><img src="' + escapeHtml(g.cover) + '" alt="' + escapeHtml(g.title) + '"/></div>'
      +       '<div class="steam-card-right">'
      +         '<h3 class="steam-card-title">购买 <strong>' + escapeHtml(g.title) + '</strong> 就在 Steam</h3>'
      +         '<p class="steam-card-desc">' + escapeHtml(g.desc) + '</p>'
      +         '<div class="steam-card-meta">'
      +           '<span class="steam-card-price">' + escapeHtml(g.price) + '</span>'
      +           '<a class="steam-buy-btn" href="' + escapeHtml(g.store_url) + '" target="_blank">在 Steam 上购买</a>'
      +         '</div>'
      +       '</div>'
      +     '</div>'
      +   '</div>'
      + '</div>';

      if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
        tinymce.activeEditor.setContent(html);
      } else {
        var $ta = $('#content, textarea[name="content"], .wp-editor-area').first();
        if ($ta.length) {
          $ta.val(html).trigger('input');
        } else {
          alert('未找到编辑器，请手动粘贴。');
        }
      }
    }).fail(function(){
      $btn.prop('disabled', false).text('采集Steam游戏信息');
      alert('网络错误，采集失败');
    });
  });
});