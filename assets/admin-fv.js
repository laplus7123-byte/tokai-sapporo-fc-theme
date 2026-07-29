(function ($) {
  function setPreview($wrap, url) {
    const $preview = $wrap.find('.tokai-fv-image__preview');
    if (url) {
      $preview.html('<img src="' + url + '" alt="">');
    } else {
      $preview.html('<span class="tokai-fv-image__empty">未設定</span>');
    }
  }

  $(document).on('click', '.tokai-fv-pick', function (e) {
    e.preventDefault();
    const $wrap = $(this).closest('.tokai-fv-image');
    const label = $wrap.data('label') || '画像';
    const frame = wp.media({
      title: label + '版FV画像を選択',
      button: { text: 'この画像を使用' },
      multiple: false,
      library: { type: 'image' },
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      const url = attachment.url;
      $wrap.find('.tokai-fv-image-id').val(attachment.id);
      $wrap.find('.tokai-fv-image-url').val(url);
      setPreview($wrap, url);
    });

    frame.open();
  });

  $(document).on('click', '.tokai-fv-clear', function (e) {
    e.preventDefault();
    const $wrap = $(this).closest('.tokai-fv-image');
    $wrap.find('.tokai-fv-image-id').val('0');
    $wrap.find('.tokai-fv-image-url').val('');
    setPreview($wrap, '');
  });
})(jQuery);
