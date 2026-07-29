(function ($, wp) {
  if (!wp || !wp.media || !window.tokaiMediaFolders) return;

  const taxonomy = tokaiMediaFolders.taxonomy;
  const options = tokaiMediaFolders.options || [];

  function injectFilter(view) {
    if (!view || !view.$el || view.$el.find('.tokai-media-folder-filter').length) {
      return;
    }

    const $select = $('<select class="tokai-media-folder-filter" style="margin-left:8px;" />');
    options.forEach((opt) => {
      $select.append(
        $('<option />').attr('value', opt.slug || '').text(opt.name)
      );
    });

    const $toolbar = view.$el.find('.media-toolbar-secondary').first();
    if (!$toolbar.length) return;

    $toolbar.append($select);

    if (tokaiMediaFolders.manageUrl) {
      $toolbar.append(
        $('<a class="button" style="margin-left:6px;" />')
          .attr('href', tokaiMediaFolders.manageUrl)
          .attr('target', '_blank')
          .attr('rel', 'noopener')
          .text('フォルダ作成')
      );
    }

    $select.on('change', function () {
      const value = $(this).val() || '';
      const state = view.controller.state();
      const props = state.get('library') || state.props;
      if (!props) return;

      if (value) {
        props.set(taxonomy, value);
      } else {
        props.unset(taxonomy);
      }

      const library = state.get('library');
      if (library && library.props) {
        if (value) {
          library.props.set(taxonomy, value);
        } else {
          library.props.unset(taxonomy);
        }
        library.more(true);
        if (typeof library.reset === 'function') {
          // force refresh
        }
        library.observe && library.observe(props);
        library.fetch && library.fetch({ reset: true });
      }
    });
  }

  // メディアライブラリ（upload.php グリッド）
  if (wp.media.view && wp.media.view.AttachmentsBrowser) {
    const Browser = wp.media.view.AttachmentsBrowser;
    wp.media.view.AttachmentsBrowser = Browser.extend({
      ready: function () {
        if (Browser.prototype.ready) {
          Browser.prototype.ready.apply(this, arguments);
        }
        injectFilter(this);
        return this;
      },
    });
  }
})(jQuery, window.wp);
