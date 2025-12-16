

function scrollToTopInstant() {
    try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) { window.scrollTo(0, 0); }
  }

  // Scroll to top and call callback when detected at top or after timeout
  function waitForScrollTop(cb) {
    var start = Date.now();
    try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) { window.scrollTo(0,0); }
    var maxMs = 900;
    function check() {
      var y = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
      if (y <= 2) {
        if (typeof cb === 'function') cb();
        return;
      }
      if (Date.now() - start > maxMs) {
        if (typeof cb === 'function') cb();
        return;
      }
      requestAnimationFrame(check);
    }
    requestAnimationFrame(check);
  }

  function showCardById(cardId, cb) {
    var el = document.getElementById(cardId);
    if (!el) { if (typeof cb === 'function') cb(); return; }
    if (el.style.display === 'none' || getComputedStyle(el).display === 'none') {
      el.style.display = 'block';
    }
    requestAnimationFrame(function() {
      el.classList.add('card-panel');
      requestAnimationFrame(function() { el.classList.add('is-showing'); });
    });
    var onEnd = function(e) {
      if (e && e.target !== el) return;
      try { el.removeEventListener('transitionend', onEnd); } catch (err) {}
      if (typeof cb === 'function') cb();
    };
    el.addEventListener('transitionend', onEnd);
    // fallback
    setTimeout(function() { try{ el.removeEventListener('transitionend', onEnd); }catch(e){} if (typeof cb === 'function') cb(); }, 800);
  }

  function hideCardById(cardId, cb) {
    var el = document.getElementById(cardId);
    if (!el) { if (typeof cb === 'function') cb(); return; }
    el.classList.remove('is-showing');
    var onEnd = function(e) {
      if (e && e.target !== el) return;
      el.style.display = 'none';
      try { el.removeEventListener('transitionend', onEnd); } catch (err) {}
      if (typeof cb === 'function') cb();
    };
    el.addEventListener('transitionend', onEnd);
    // fallback in case transitionend doesn't fire
    setTimeout(function() {
      if (getComputedStyle(el).display !== 'none') {
        el.style.display = 'none';
        try { el.removeEventListener('transitionend', onEnd); } catch (e) {}
        if (typeof cb === 'function') cb();
      }
    }, 700);
  }

  function setupDataShowButtons() {
    // Attach click handlers to any button with data-show attribute
    var buttons = document.querySelectorAll('button[data-show]');
    for (var i = 0; i < buttons.length; i++) {
      (function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var targetId = btn.getAttribute('data-show');
          if (!targetId) return;

          // determine current panel (closest ancestor with id starting with 'card-')
          var current = btn.closest('[id^="card-"]');

          var proceedToShow = function() {
            showCardById(targetId, function() {
              // focus first focusable element without scrolling
              var targetEl = document.getElementById(targetId);
              if (!targetEl) return;
              var focusable = targetEl.querySelector('button, a, input, [tabindex]');
              if (focusable) {
                try {
                  focusable.focus({preventScroll: true});
                } catch (err) {
                  try { focusable.focus(); } catch (e) {}
                }
              }
            });
          };

          if (current) {
            // hide current, then scroll, then show target
            hideCardById(current.id, function() {
              waitForScrollTop(proceedToShow);
            });
          } else {
            // no current panel -> just scroll then show
            waitForScrollTop(proceedToShow);
          }
        });
      })(buttons[i]);
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Ensure panels have base class
    var p1 = document.getElementById('card-1');
    var p2 = document.getElementById('card-2');
    if (p1) p1.classList.add('card-panel');
    if (p2) p2.classList.add('card-panel');

    // Initialize visible state
    if (p1 && getComputedStyle(p1).display !== 'none') p1.classList.add('is-showing');
    if (p2 && getComputedStyle(p2).display !== 'none') p2.classList.add('is-showing');

    setupDataShowButtons();

    // Popup: open/close logic
    function openFactionPopup(opts){
        
      var overlay = document.getElementById('faction-popup');
      if(!overlay) return;
      var title = document.getElementById('faction-popup-title');
      var body = document.getElementById('faction-popup-body');
      // populate
      title.textContent = opts.name || 'Faction';
      overlay.style.display = 'flex';
      // small timeout to allow CSS transition (popup-overlay.is-visible sets opacity)
      requestAnimationFrame(function(){ overlay.classList.add('is-visible'); overlay.setAttribute('aria-hidden','false'); });
      // focus join button
      var join = document.getElementById('faction-join-btn'); if(join) join.focus();
        displayGoodIcon(opts.id);
    }
    
    function displayGoodIcon(factionId){
        var iconElement = document.getElementById(`${factionId}-icon`);
        if (!iconElement) return;
        else{
            let icons = document.querySelectorAll('.title-3d');
            icons.forEach(function(icon){   
                icon.style.display = 'none';
            });
            iconElement.style.display = 'block';
        }   
    }
        

    function closeFactionPopup(){
      var overlay = document.getElementById('faction-popup');
      if(!overlay) return;
      overlay.classList.remove('is-visible');
      overlay.setAttribute('aria-hidden','true');
      // wait for transition then hide
      setTimeout(function(){ if(overlay) overlay.style.display='none'; }, 280);
    }

    // attach openers: any element with data-popup-target and data-faction-name
    var popupButtons = document.querySelectorAll('button[data-popup-target]');
    popupButtons.forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var target = btn.getAttribute('data-popup-target');
        if(!target) return;
        var name = btn.getAttribute('data-faction-name') || btn.textContent.trim();
        var id = btn.getAttribute('data-faction-id') || '';
        var desc = btn.getAttribute('data-faction-desc') || 'Description courte de la faction. Remplacez par un texte réel.';
        openFactionPopup({ name: name, id: id, desc: desc });
      });
    });

    // close handlers
    var popupClose = document.getElementById('faction-popup-close');
    if(popupClose) popupClose.addEventListener('click', function(e){ e.preventDefault(); closeFactionPopup(); });
    var popupOverlay = document.getElementById('faction-popup');
    if(popupOverlay) popupOverlay.addEventListener('click', function(e){ if(e.target === popupOverlay) closeFactionPopup(); });

    // join button example handler
    var joinBtn = document.getElementById('faction-join-btn');
    if(joinBtn) joinBtn.addEventListener('click', function(e){
      e.preventDefault();
      // Here you would call your join routine (AJAX or navigate). For now show console and close.
      var title = document.getElementById('faction-popup-title').textContent;
      console.log('Joining faction:', title);
      closeFactionPopup();
    });
  });

