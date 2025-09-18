(function(){
  // Vanilla JS tabs switcher
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.plans-tab');
    if (!btn) return;
    var wrap = btn.closest('.plans-wrap');
    if (!wrap) return;

    var target = btn.getAttribute('data-tab');
    // Toggle tabs
    wrap.querySelectorAll('.plans-tab').forEach(function(b){
      b.classList.toggle('active', b === btn);
      b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
    });
    // Toggle panels
    wrap.querySelectorAll('.plans-panel').forEach(function(panel){
      panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
    });
  }, false);
})();