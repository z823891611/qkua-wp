(function(){
  if (typeof QKUA_CHILD_CFG === 'undefined') return;
  const cfg = QKUA_CHILD_CFG.opts || {};

  function appendHidden(form, name, val){
    if (!form || !name || !val) return;
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = val;
    form.appendChild(input);
  }

  function findPublishForm(){
    var forms = document.querySelectorAll('form');
    for (var i=0;i<forms.length;i++){
      var f = forms[i];
      var txt = (f.innerText || '').trim();
      if (/发布|提交|保存草稿/.test(txt)) return f;
    }
    return forms[0] || null;
  }

  function injectSimpleFields(form){
    if (!form) return;
    var box = document.createElement('div');
    box.style.margin = '12px 0';
    box.innerHTML = [
      (parseInt(cfg.show_video||0,10)===1 ? '<label>视频地址：<input type="url" id="steam_video_url" style="width:320px;"></label>' : ''),
      (parseInt(cfg.show_download||0,10)===1 ? '<label>下载标题：<input type="text" id="steam_download_title" value="学习版" style="width:120px;"></label>' : ''),
      (parseInt(cfg.show_download||0,10)===1 ? '<label>下载地址：<input type="url" id="steam_download_url" style="width:320px;"></label>' : '')
    ].join(' ');
    form.appendChild(box);
  }

  document.addEventListener('DOMContentLoaded', function(){
    var form = findPublishForm();
    if (!form) return;
    injectSimpleFields(form);
    form.addEventListener('submit', function(){
      var vurl = document.getElementById('steam_video_url');
      var dtitle = document.getElementById('steam_download_title');
      var durl = document.getElementById('steam_download_url');
      if (vurl && vurl.value) appendHidden(form, 'steam_video_url', vurl.value);
      if (durl && durl.value) appendHidden(form, 'steam_download_url', durl.value);
      if (dtitle && dtitle.value) appendHidden(form, 'steam_download_title', dtitle.value);
    }, {capture:true});
  });
})();
