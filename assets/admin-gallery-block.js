(function (wp) {
  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editPost || wp.editor || {};
  const { useSelect, useDispatch } = wp.data;
  const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
  const { Button, Spinner, Notice } = wp.components;
  const { createElement: el, Fragment, useMemo } = wp.element;

  if (!PluginDocumentSettingPanel) {
    return;
  }

  function normalizeIds(value) {
    if (!Array.isArray(value)) return [];
    return value.map((id) => parseInt(id, 10)).filter((id) => id > 0);
  }

  function GalleryPanel() {
    const ids = useSelect((select) => {
      const meta = select('core/editor').getEditedPostAttribute('meta') || {};
      return normalizeIds(meta.match_gallery);
    }, []);

    const mediaMap = useSelect(
      (select) => {
        const map = {};
        ids.forEach((id) => {
          map[id] = select('core').getMedia(id);
        });
        return map;
      },
      [ids.join(',')]
    );

    const { editPost } = useDispatch('core/editor');

    const setIds = (next) => {
      const currentMeta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
      editPost({
        meta: {
          ...currentMeta,
          match_gallery: normalizeIds(next),
        },
      });
    };

    const items = useMemo(() => {
      return ids.map((id) => {
        const media = mediaMap[id];
        const thumb =
          (media &&
            media.media_details &&
            media.media_details.sizes &&
            media.media_details.sizes.thumbnail &&
            media.media_details.sizes.thumbnail.source_url) ||
          (media && media.source_url) ||
          '';
        return { id, thumb, ready: !!media };
      });
    }, [ids, mediaMap]);

    return el(
      PluginDocumentSettingPanel,
      {
        name: 'tokai-match-gallery',
        title: '試合ギャラリー',
        className: 'tokai-match-gallery-panel',
      },
      el(
        Notice,
        { status: 'info', isDismissible: false },
        '右サイド「試合ギャラリー」で追加した写真が Gallery ページに出ます。追加後は「更新」を押してください。'
      ),
      el(
        'p',
        { className: 'tokai-match-gallery-panel__help' },
        '試合写真を複数枚追加できます。'
      ),
      el(
        'div',
        { className: 'tokai-match-gallery-panel__list' },
        items.map((item) =>
          el(
            'div',
            { className: 'tokai-match-gallery-panel__item', key: item.id },
            item.ready
              ? el('img', { src: item.thumb, alt: '' })
              : el(Spinner),
            el(
              Button,
              {
                isSmall: true,
                variant: 'secondary',
                className: 'tokai-match-gallery-panel__remove',
                onClick: () => setIds(ids.filter((id) => id !== item.id)),
                label: '削除',
              },
              '×'
            )
          )
        )
      ),
      el(
        MediaUploadCheck,
        null,
        el(MediaUpload, {
          onSelect: (selection) => {
            const selected = (Array.isArray(selection) ? selection : [selection])
              .map((att) => att.id)
              .filter(Boolean);
            const merged = ids.slice();
            selected.forEach((id) => {
              if (merged.indexOf(id) === -1) merged.push(id);
            });
            setIds(merged);
          },
          allowedTypes: ['image'],
          multiple: true,
          value: ids,
          render: ({ open }) =>
            el(
              Fragment,
              null,
              el(
                Button,
                {
                  variant: 'primary',
                  onClick: open,
                  style: { width: '100%', justifyContent: 'center', marginTop: '8px' },
                },
                '画像を追加（複数可）'
              ),
              ids.length
                ? el(
                    Button,
                    {
                      variant: 'link',
                      isDestructive: true,
                      onClick: () => {
                        if (window.confirm('ギャラリー画像をすべて削除しますか？')) {
                          setIds([]);
                        }
                      },
                      style: { marginTop: '8px' },
                    },
                    'すべて削除'
                  )
                : null
            ),
        })
      )
    );
  }

  registerPlugin('tokai-match-gallery', {
    render: GalleryPanel,
    icon: 'format-gallery',
  });
})(window.wp);
