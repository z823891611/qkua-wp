/**
 * Qkua Child – Write Integration C
 * - Lock category to given ID
 * - Lock content permission to credit with amount
 * - Tags: support Enter-to-add
 * - Extra fields (video / study / list)
 * - Steam bridge: apply from window.SteamScraper.fill or custom events
 * Does NOT alter homepage.
 */
(function(){
  if (!/\/write(\?|$)/.test(location.pathname)) return;

  function ready(fn){
    if(document.readyState!=='loading'){ fn(); }
    else document.addEventListener('DOMContentLoaded', fn);
  }
  function getVM(){
    var el = document.getElementById('write');
    if (el && el.__vue__) return el.__vue__;
    // fallback: search parent chain
    var cur = el;
    while(cur){
      if(cur.__vue__) return cur.__vue__;
      cur = cur.parentElement;
    }
    return null;
  }
  function uniq(arr){
    return Array.from(new Set((arr||[]).map(x=>String(x).trim()).filter(Boolean)));
  }
  function getEditorHtml(){
    if (window.tinyMCE && tinymce.get('post_content')){
      return tinymce.get('post_content').getContent({format: 'html'}) || '';
    }
    var ta = document.getElementById('post_content');
    return ta ? (ta.value || '') : '';
  }
  function setEditorHtml(html){
    if (window.tinyMCE && tinymce.get('post_content')){
      tinymce.get('post_content').setContent(html || '');
      return;
    }
    var ta = document.getElementById('post_content');
    if (ta){ ta.value = html || ''; ta.dispatchEvent(new Event('input',{bubbles:true})); }
  }

  function ensureExtraPanel(){
    var panel = document.querySelector('#post-setting .qk-steam-extra-panel');
    if (panel) return panel;
    panel = document.createElement('div');
    panel.className = 'mg-b qk-steam-extra-panel';
    panel.innerHTML = ''
      + '<div class="widget-title">Steam 扩展字段（七夸适配）</div>'
      + '<div class="write-select-box"><p>视频地址</p><input class="el-input__inner qk-steam-video" placeholder="视频直链或可嵌入地址"></div>'
      + '<div class="write-select-box"><p>学习版下载地址</p><input class="el-input__inner qk-steam-study" placeholder="仅填下载地址"></div>'
      + '<div class="write-select-box"><p>清单下载地址</p><input class="el-input__inner qk-steam-list"  placeholder="仅填下载地址"></div>';
    var postset = document.querySelector('#post-setting');
    if (postset) postset.appendChild(panel);
    return panel;
  }

  function applyToVM(vm, payload){
    payload = payload || {};

    // title
    if (payload.title) {
      try {
        if (vm.postData && 'writeTitle' in vm.postData) vm.postData.writeTitle = payload.title;
        var t = document.getElementById('write-textarea');
        if (t) { t.value = payload.title; t.dispatchEvent(new Event('input',{bubbles:true})); }
      } catch(e){}
    }

    // content
    if (payload.contentHtml || payload.content){
      var html = payload.contentHtml || payload.content || '';
      if (html) setEditorHtml(html);
    }

    // category lock
    if (window.QKUA_CHILD_CFG && QKUA_CHILD_CFG.fixCategory) {
      var cid = parseInt(QKUA_CHILD_CFG.categoryId || 0);
      if (cid) vm.cats = [cid];
      // disable UI visually
      var elSelect = document.querySelector('#post-setting .write-select-box .el-select');
      if (elSelect) elSelect.classList.add('is-disabled');
    }

    // credit lock
    if (window.QKUA_CHILD_CFG && QKUA_CHILD_CFG.lockCredit){
      vm.role.key = 'credit';
      vm.role.num = parseInt(QKUA_CHILD_CFG.creditAmount || 500) || 500;
    }

    // tags
    if (window.QKUA_CHILD_CFG && QKUA_CHILD_CFG.fillTags && payload.tags){
      var tags = Array.isArray(payload.tags) ? payload.tags : String(payload.tags).split(/[,\s]+/);
      vm.tags = uniq([].concat(vm.tags || [], tags));
    }

    // extra panel values
    ensureExtraPanel();
    if (payload.extra){
      if (payload.extra.video){
        var v = document.querySelector('.qk-steam-video'); if (v) v.value = payload.extra.video;
      }
      if (payload.extra.study){
        var s = document.querySelector('.qk-steam-study'); if (s) s.value = payload.extra.study;
      }
      if (payload.extra.list){
        var l = document.querySelector('.qk-steam-list'); if (l) l.value = payload.extra.list;
      }
    }
  }

  function injectEnterForTags(vm){
    var input = document.querySelector('.write-select-box .el-select .el-input__inner');
    if (!input || input.__qk_enter_bind) return;
    input.addEventListener('keydown', function(e){
      if (e.key === 'Enter'){
        var val = this.value.trim();
        if (val){
          vm.tags = uniq([].concat(vm.tags || [], [val]));
          this.value='';
        }
        e.preventDefault(); e.stopPropagation();
      }
    }, true);
    input.__qk_enter_bind = true;
  }

  function patchSubmit(vm){
    if (!window.writeSetting || !writeSetting.submit || writeSetting.submit.__qk_patched) return;
    var orig = writeSetting.submit;
    writeSetting.submit = function(type){
      try {
        // collect extra fields
        var extra = {
          video: (document.querySelector('.qk-steam-video')||{}).value || '',
          study: (document.querySelector('.qk-steam-study')||{}).value || '',
          list:  (document.querySelector('.qk-steam-list')||{}).value  || ''
        };
        // append hidden block for server to parse and store
        if (extra.video || extra.study || extra.list){
          var content = getEditorHtml();
          var hidden = '<p class="qk-steam-append" style="display:none">STEAM-EXTRA:'+JSON.stringify(extra)+'</p>';
          setEditorHtml(content + hidden);
        }

        // enforce locks before submit
        if (window.QKUA_CHILD_CFG && QKUA_CHILD_CFG.fixCategory){
          var cid = parseInt(QKUA_CHILD_CFG.categoryId || 0);
          if (cid) vm.cats = [cid];
        }
        if (window.QKUA_CHILD_CFG && QKUA_CHILD_CFG.lockCredit){
          vm.role.key = 'credit';
          vm.role.num = parseInt(QKUA_CHILD_CFG.creditAmount || 500) || 500;
        }
      } catch(err){ console.warn('[QKUA_CHILD] submit patch error', err); }
      return orig.apply(this, arguments);
    };
    writeSetting.submit.__qk_patched = true;
  }

  function setupSteamBridge(vm){
    // 1) apply when global var present
    var appliedOnce = false;
    function tryApplyFromGlobal(){
      var s = window.SteamScraper;
      if (s && s.fill && !appliedOnce){
        appliedOnce = true;
        applyToVM(vm, s.fill);
      }
    }
    setInterval(tryApplyFromGlobal, 800);
    tryApplyFromGlobal();

    // 2) listen to custom events
    window.addEventListener('steam:filled', function(e){
      try{ applyToVM(vm, e.detail || {}); }catch(err){}
    });
    window.addEventListener('steam:fill', function(e){
      try{ applyToVM(vm, e.detail || {}); }catch(err){}
    });

    // 3) public API
    window.QkuaChild = window.QkuaChild || {};
    window.QkuaChild.applyFromSteam = function(payload){
      applyToVM(vm, payload || {});
    };
  }

  ready(function(){
    var tries = 0, tm = setInterval(function(){
      tries++;
      var vm = getVM();
      if (vm){
        injectEnterForTags(vm);
        patchSubmit(vm);
        setupSteamBridge(vm);

        // initial locks
        applyToVM(vm, {});
        clearInterval(tm);
      }
      if (tries > 40){ clearInterval(tm); }
    }, 300);
  });
})();
