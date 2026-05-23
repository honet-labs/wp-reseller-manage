(function(){
  'use strict';

  function ensureModal(){
    var m = document.getElementById('wrpm-modal');
    if(m) return m;
    m = document.createElement('div');
    m.id = 'wrpm-modal';
    m.className = 'wrpm-modal';
    m.innerHTML = '' +
      '<div class="wrpm-modal-backdrop" data-wrpm-close="1"></div>' +
      '<div class="wrpm-modal-card" role="dialog" aria-modal="true">' +
        '<div class="wrpm-modal-head">' +
          '<div class="wrpm-modal-title">Preview</div>' +
          '<button type="button" class="button" data-wrpm-close="1">Close</button>' +
        '</div>' +
        '<div class="wrpm-modal-body"></div>' +
      '</div>';
    document.body.appendChild(m);
    m.addEventListener('click', function(e){
      if(e.target && e.target.getAttribute && e.target.getAttribute('data-wrpm-close')){
        e.preventDefault();
        m.classList.remove('open');
      }
    });
    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') m.classList.remove('open');
    });
    return m;
  }

  function openModal(title, node){
    var m = ensureModal();
    var t = m.querySelector('.wrpm-modal-title');
    var b = m.querySelector('.wrpm-modal-body');
    if(t) t.textContent = title || 'Preview';
    if(b){
      b.innerHTML = '';
      if(node) b.appendChild(node);
    }
    m.classList.add('open');
  }

  function flToast(msg){
    try{
      var div = document.createElement('div');
      div.className = 'fl-toast';
      div.textContent = msg;
      document.body.appendChild(div);
      setTimeout(function(){ div.classList.add('show'); }, 10);
      setTimeout(function(){ div.classList.remove('show'); }, 2200);
      setTimeout(function(){ if(div && div.parentNode) div.parentNode.removeChild(div); }, 2600);
    }catch(e){}
  }

  function copyText(text){
    if(navigator && navigator.clipboard && navigator.clipboard.writeText){
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function(resolve){
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly','');
      ta.style.position = 'absolute';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try{ document.execCommand('copy'); }catch(e){}
      document.body.removeChild(ta);
      resolve();
    });
  }

  function closeMenus(){
    document.querySelectorAll('.fl-menu').forEach(function(m){
      m.hidden = true;
      m.classList.remove('fl-menu-fixed');
      m.style.position = '';
      m.style.left = '';
      m.style.top = '';
      m.style.right = '';
      m.style.bottom = '';
      m.style.visibility = '';
    });
  }

  document.addEventListener('click', function(e){
    var btn = e.target.closest ? e.target.closest('.fl-kebab') : null;
    var infoBtn = e.target.closest ? e.target.closest('.wrpm-info-btn') : null;

    var item = e.target.closest ? e.target.closest('.fl-copy-shortcode') : null;
    var wa = e.target.closest ? e.target.closest('.wrpm-copy-wa') : null;

    if(wa){
      e.preventDefault();
      var t = wa.getAttribute('data-text') || '';
      copyText(t).then(function(){ flToast('WhatsApp message copied'); });
      closeMenus();
      return;
    }
    if(infoBtn){
      e.preventDefault();
      var wrapInfo = infoBtn.parentElement ? infoBtn.parentElement.querySelector('.wrpm-page-info') : null;
      if(wrapInfo){
        var box = document.createElement('div');
        box.className = 'wrpm-info-box';
        box.innerHTML = wrapInfo.innerHTML;
        openModal('Info', box);
      }
      closeMenus();
      return;
    }


    var vjson = e.target.closest ? e.target.closest('.wrpm-view-json') : null;
    if(vjson){
      e.preventDefault();
      var title = vjson.getAttribute('data-title') || 'JSON';
      var raw = vjson.getAttribute('data-json') || '{}';
      var pretty = raw;
      try{ pretty = JSON.stringify(JSON.parse(raw), null, 2); }catch(err){}
      var pre = document.createElement('pre');
      pre.className = 'wrpm-pre';
      pre.textContent = pretty;
      openModal(title, pre);
      closeMenus();
      return;
    }

    var vtext = e.target.closest ? e.target.closest('.wrpm-view-text') : null;
    if(vtext){
      e.preventDefault();
      var t2 = vtext.getAttribute('data-title') || 'Text';
      var txt = vtext.getAttribute('data-text') || '';
      var pre2 = document.createElement('pre');
      pre2.className = 'wrpm-pre';
      pre2.textContent = txt;
      openModal(t2, pre2);
      closeMenus();
      return;
    }

    var vimg = e.target.closest ? e.target.closest('.wrpm-view-images') : null;
    if(vimg){
      e.preventDefault();
      var t3 = vimg.getAttribute('data-title') || 'Images';
      var rawu = vimg.getAttribute('data-urls') || '[]';
      var urls = [];
      try{ urls = JSON.parse(rawu) || []; }catch(err){}
      var wrap = document.createElement('div');
      wrap.className = 'wrpm-img-grid';
      urls.forEach(function(u){
        if(!u) return;
        var a = document.createElement('a');
        a.href = u;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        var img = document.createElement('img');
        img.src = u;
        img.alt = 'attachment';
        a.appendChild(img);
        wrap.appendChild(a);
      });
      if(!urls.length){
        var em = document.createElement('div');
        em.textContent = 'Tidak ada gambar.';
        wrap.appendChild(em);
      }
      openModal(t3, wrap);
      closeMenus();
      return;
    }

    if(item){
      e.preventDefault();
      var sc = item.getAttribute('data-shortcode') || '';
      copyText(sc).then(function(){ flToast('Shortcode copied'); });
      closeMenus();
      return;
    }

    if(btn){
      e.preventDefault();
      var menu = btn.parentElement ? btn.parentElement.querySelector('.fl-menu') : null;
      if(!menu) return;
      var willShow = menu.hidden;
      closeMenus();

      if(willShow){
        menu.hidden = false;
        var r = btn.getBoundingClientRect();
        menu.classList.add('fl-menu-fixed');
        menu.style.position = 'fixed';
        menu.style.right = 'auto';
        menu.style.visibility = 'hidden';
        menu.style.left = '8px';
        menu.style.top = '8px';

        var mw = menu.offsetWidth || 180;
        var mh = menu.offsetHeight || 120;
        var vw = window.innerWidth || document.documentElement.clientWidth || 1024;
        var vh = window.innerHeight || document.documentElement.clientHeight || 768;

        var left = r.left;
        var top = r.bottom + 8;

        if(left + mw > vw - 8) left = vw - mw - 8;
        if(left < 8) left = 8;
        if(top + mh > vh - 8) top = r.top - mh - 8;
        if(top < 8) top = 8;

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        menu.style.visibility = '';
      }

      menu.hidden = !willShow;
      return;
    }

    // Click outside closes.
    if(!e.target.closest || !e.target.closest('.fl-head-actions')){
      closeMenus();
    }
  });

  // Extend duration popup
  document.addEventListener('click', function(e){
    var b = e.target.closest ? e.target.closest('.wrpm-extend-btn') : null;
    if(!b) return;
    e.preventDefault();
    var id = b.getAttribute('data-id') || '';
    var nonce = b.getAttribute('data-nonce') || '';
    if(!id) return;
    var days = window.prompt('Tambah durasi (hari):', '30');
    if(days === null) return;
    days = String(days).trim();
    if(!days || isNaN(Number(days)) || Number(days) <= 0){
      alert('Durasi harus berupa angka > 0');
      return;
    }

    var f = document.getElementById('wrpm-extend-form');
    if(!f) return;
    f.querySelector('input[name=id]').value = id;
    f.querySelector('input[name=days]').value = String(parseInt(days,10));
    f.querySelector('input[name=_wpnonce]').value = nonce;
    f.submit();
  });

})();
