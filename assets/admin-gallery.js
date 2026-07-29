(function ($) {
  function markTouched() {
    $('#tokai-gallery-touched').val('1');
  }

  function readIds() {
    const raw = $('#tokai-gallery-ids').val() || '';
    return raw
      .split(',')
      .map((v) => parseInt(v, 10))
      .filter((v) => v > 0);
  }

  function writeIds(ids) {
    $('#tokai-gallery-ids').val(ids.join(','));
    markTouched();
  }

  function syncFromDom() {
    const ids = [];
    $('#tokai-gallery-list .tokai-gallery-box__item').each(function () {
      const id = parseInt($(this).data('id'), 10);
      if (id > 0) ids.push(id);
    });
    writeIds(ids);
  }

  function addItems(attachments) {
    const ids = readIds();
    const $list = $('#tokai-gallery-list');

    attachments.forEach((att) => {
      const id = att.id;
      if (!id || ids.indexOf(id) !== -1) return;
      ids.push(id);
      const thumb =
        (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) || att.url;
      $list.append(
        '<li class="tokai-gallery-box__item" data-id="' +
          id +
          '"><img src="' +
          thumb +
          '" alt=""><button type="button" class="tokai-gallery-box__remove" aria-label="削除">&times;</button></li>'
      );
    });

    writeIds(ids);
  }

  $(document).on('click', '#tokai-gallery-add', function (e) {
    e.preventDefault();
    const frame = wp.media({
      title: '試合ギャラリー画像を選択',
      button: { text: '追加する' },
      multiple: true,
      library: { type: 'image' },
    });

    frame.on('select', function () {
      const selection = frame.state().get('selection').toJSON();
      addItems(selection);
    });

    frame.open();
  });

  $(document).on('click', '.tokai-gallery-box__remove', function (e) {
    e.preventDefault();
    $(this).closest('.tokai-gallery-box__item').remove();
    syncFromDom();
  });

  $(document).on('click', '#tokai-gallery-clear', function (e) {
    e.preventDefault();
    if (!window.confirm('ギャラリー画像をすべて削除しますか？')) return;
    $('#tokai-gallery-list').empty();
    writeIds([]);
  });

  if ($.fn.sortable) {
    $('#tokai-gallery-list').sortable({
      update: syncFromDom,
    });
  }
})(jQuery);
