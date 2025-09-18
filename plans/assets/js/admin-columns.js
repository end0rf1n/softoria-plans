(function($){
  $(document).on('click', '.plans-toggle', function(e){
    e.preventDefault();
    var $btn = $(this);
    var postId = $btn.data('post');
    var key = $btn.data('key');
    var status = $btn.data('status'); // 'on' or 'off'

    $btn.prop('disabled', true);

    $.post(PlansAdmin.ajaxUrl, {
      action: 'plans_toggle_meta',
      nonce: PlansAdmin.nonce,
      post_id: postId,
      meta_key: key
    }).done(function(resp){
      if (resp && resp.success) {
        var isOn = !!resp.data.new;
        $btn.data('status', isOn ? 'on' : 'off');
        $btn.text(resp.data.label + ': ' + (isOn ? 'On' : 'Off'));
      } else {
        alert('Failed to toggle. Try again.');
      }
    }).fail(function(){
      alert('Request failed.');
    }).always(function(){
      $btn.prop('disabled', false);
    });
  });
})(jQuery);