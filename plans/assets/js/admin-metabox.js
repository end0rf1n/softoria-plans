(function(){
  document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'plans-add-feature') {
      e.preventDefault();
      var wrap = document.getElementById('plans-features-wrap');
      var row = document.createElement('div');
      row.className = 'plans-feature-row';
      row.innerHTML = '<input type="text" name="plans_features[]" value="" placeholder="Feature text" />' +
        ' <button type="button" class="button plans-remove-feature">Remove</button>';
      wrap.appendChild(row);
    }

    if (e.target && e.target.classList.contains('plans-remove-feature')) {
      e.preventDefault();
      var row = e.target.closest('.plans-feature-row');
      if (row) row.remove();
    }
  }, false);
})();

// Validate before submit: if Button text filled -> Button link must be valid URL
(function(){
  var form = document.getElementById('post');
  if (!form) return;

  form.addEventListener('submit', function(e){
    var text = (document.getElementById('plans_button_text') || {}).value || '';
    var link = (document.getElementById('plans_button_link') || {}).value || '';

    // Only check when text is provided
    if (text.trim() !== '') {
      try {
        // URL constructor throws if invalid
        new URL(link);
      } catch(err) {
        e.preventDefault();
        alert('Please provide a valid URL for "Button link" when "Button text" is filled.');
        (document.getElementById('plans_button_link') || {}).focus?.();
      }
    }
  }, false);
})();