(function ($) {
  const GRADE_KEYS = {
    '3年生': 'g3',
    '2年生': 'g2',
    '1年生': 'g1',
  };

  function fieldKeyFromName(name) {
    const match = String(name || '').match(/\[([^\]]+)\]$/);
    return match ? match[1] : '';
  }

  function reindexRow($row, key) {
    $row.find('[name]').each(function () {
      const $field = $(this);
      const fieldKey = fieldKeyFromName($field.attr('name'));
      if (!fieldKey) return;
      $field.attr('name', `${key}[${fieldKey}]`);
    });
    $row.find('.tokai-member-image').attr('data-index', key);
  }

  function reindexGrade($tbody) {
    const grade = String($tbody.data('grade') || '');
    const gradeKey = GRADE_KEYS[grade];
    if (!gradeKey) return;

    let index = 0;
    $tbody.find('tr.tokai-member-row').each(function () {
      reindexRow($(this), `tokai_players_${gradeKey}[${index}]`);
      $(this).find(`input[name="tokai_players_${gradeKey}[${index}][grade]"]`).val(grade);
      index += 1;
    });
  }

  function reindexAllPlayers() {
    $('.tokai-sortable[data-type="player"]').each(function () {
      reindexGrade($(this));
    });
  }

  function reindexAllStaff() {
    let index = 0;
    $('.tokai-sortable[data-type="staff"] tr.tokai-member-row').each(function () {
      reindexRow($(this), `tokai_staff[${index}]`);
      index += 1;
    });
  }

  function initSortable() {
    $('.tokai-sortable').sortable({
      handle: '.tokai-sort-handle',
      axis: 'y',
      placeholder: 'tokai-sort-placeholder',
      forcePlaceholderSize: true,
      update: function () {
        const $tbody = $(this);
        if ($tbody.data('type') === 'staff') {
          reindexAllStaff();
        } else {
          reindexGrade($tbody);
        }
      },
    });
  }

  function bindImagePicker($scope) {
    $scope.find('.tokai-pick-image').off('click').on('click', function () {
      const $cell = $(this).closest('.tokai-member-image');
      const frame = wp.media({
        title: 'メンバー画像を選択',
        button: { text: 'この画像を使用' },
        multiple: false,
        library: { type: 'image' },
      });

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        const url = attachment.sizes?.medium?.url || attachment.url;
        $cell.find('.tokai-image-id').val(attachment.id);
        $cell.find('.tokai-image-url').val(url);
        $cell.find('.tokai-member-image__preview img').attr('src', url);
      });

      frame.open();
    });

    $scope.find('.tokai-clear-image').off('click').on('click', function () {
      const $cell = $(this).closest('.tokai-member-image');
      $cell.find('.tokai-image-id').val('');
      $cell.find('.tokai-image-url').val('');
      $cell.find('.tokai-member-image__preview img').attr('src', '');
    });
  }

  function addPlayerRow(grade) {
    const template = document.getElementById('tokai-player-row-template');
    if (!template) return;

    const gradeKey = GRADE_KEYS[grade];
    if (!gradeKey) return;

    const html = template.innerHTML
      .replace(/__GRADE_KEY__/g, gradeKey)
      .replace(/__INDEX__/g, '0')
      .replace(/__GRADE__/g, grade);
    const $tbody = $(`.tokai-sortable[data-type="player"][data-grade="${grade}"]`);
    const $row = $(html);
    $tbody.append($row);
    bindImagePicker($row);
    reindexGrade($tbody);
  }

  function addStaffRow() {
    const template = document.getElementById('tokai-staff-row-template');
    if (!template) return;

    const html = template.innerHTML.replace(/__INDEX__/g, '0');
    const $row = $(html);
    $('.tokai-sortable[data-type="staff"]').append($row);
    bindImagePicker($row);
    reindexAllStaff();
  }

  $(function () {
    reindexAllPlayers();
    reindexAllStaff();
    initSortable();
    bindImagePicker($('.tokai-members-admin'));

    $('.tokai-add-row').on('click', function () {
      const type = $(this).data('type');
      if (type === 'staff') {
        addStaffRow();
      } else {
        addPlayerRow($(this).data('grade'));
      }
    });

    $(document).on('click', '.tokai-remove-row', function () {
      if (!window.confirm('この行を削除しますか？')) return;
      const $tbody = $(this).closest('tbody');
      const type = $tbody.data('type');
      $(this).closest('tr').remove();
      if (type === 'staff') {
        reindexAllStaff();
      } else {
        reindexGrade($tbody);
      }
    });

    $('#tokai-members-form').on('submit', function () {
      reindexAllPlayers();
      reindexAllStaff();
    });
  });
})(jQuery);
