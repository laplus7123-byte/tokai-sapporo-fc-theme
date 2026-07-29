<?php
/**
 * メディアライブラリのフォルダ（添付ファイル用タクソノミー）
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_MEDIA_FOLDER_TAX', 'tokai_media_folder');

add_action('init', function () {
    $labels = [
        'name'              => 'メディアフォルダ',
        'singular_name'     => 'フォルダ',
        'search_items'      => 'フォルダを検索',
        'all_items'         => 'すべてのフォルダ',
        'parent_item'       => '親フォルダ',
        'parent_item_colon' => '親フォルダ:',
        'edit_item'         => 'フォルダを編集',
        'update_item'       => 'フォルダを更新',
        'add_new_item'      => '新規フォルダを追加',
        'new_item_name'     => '新しいフォルダ名',
        'menu_name'         => 'フォルダ',
        'not_found'         => 'フォルダがありません',
    ];

    register_taxonomy(TOKAI_MEDIA_FOLDER_TAX, 'attachment', [
        'labels'                => $labels,
        'hierarchical'          => true,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'show_admin_column'     => true,
        'show_in_nav_menus'     => false,
        'show_tagcloud'         => false,
        'show_in_rest'          => true,
        'rest_base'             => 'media-folders',
        'query_var'             => true,
        'rewrite'               => false,
        'update_count_callback' => '_update_generic_term_count',
    ]);
});

/**
 * メディアメニュー配下にフォルダ管理を出す
 */
add_action('admin_menu', function () {
    add_media_page(
        'メディアフォルダ',
        'フォルダ',
        'upload_files',
        'tokai-media-folders',
        'tokai_media_folders_admin_page'
    );
});

function tokai_media_folders_admin_page() {
    if (!current_user_can('upload_files')) {
        wp_die('権限がありません。');
    }

    $taxonomy = TOKAI_MEDIA_FOLDER_TAX;
    $message = '';

    if (
        isset($_POST['tokai_media_folder_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokai_media_folder_nonce'])), 'tokai_media_folder_create')
        && !empty($_POST['folder_name'])
    ) {
        $name = sanitize_text_field(wp_unslash($_POST['folder_name']));
        $parent = isset($_POST['folder_parent']) ? absint($_POST['folder_parent']) : 0;
        $result = wp_insert_term($name, $taxonomy, ['parent' => $parent]);
        if (is_wp_error($result)) {
            $message = '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            $message = '<div class="notice notice-success is-dismissible"><p>フォルダ「' . esc_html($name) . '」を作成しました。</p></div>';
        }
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
    if (is_wp_error($terms)) {
        $terms = [];
    }

    $edit_link = admin_url('edit-tags.php?taxonomy=' . $taxonomy . '&post_type=attachment');
    ?>
    <div class="wrap">
      <h1>メディアフォルダ</h1>
      <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <p>メディアをフォルダ分けできます。作成したフォルダはメディア一覧・詳細から割り当てられます。</p>

      <div class="card" style="max-width:640px;padding:16px 20px;margin:16px 0;">
        <h2 style="margin-top:0;">新規フォルダ</h2>
        <form method="post">
          <?php wp_nonce_field('tokai_media_folder_create', 'tokai_media_folder_nonce'); ?>
          <table class="form-table" role="presentation">
            <tr>
              <th scope="row"><label for="folder_name">フォルダ名</label></th>
              <td><input name="folder_name" id="folder_name" type="text" class="regular-text" required placeholder="例: 2026 / vs札幌第一"></td>
            </tr>
            <tr>
              <th scope="row"><label for="folder_parent">親フォルダ</label></th>
              <td>
                <select name="folder_parent" id="folder_parent">
                  <option value="0">なし（最上位）</option>
                  <?php foreach ($terms as $term) : ?>
                    <option value="<?php echo esc_attr((string) $term->term_id); ?>">
                      <?php echo esc_html($term->name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
          </table>
          <?php submit_button('フォルダを作成', 'primary', 'submit', false); ?>
        </form>
      </div>

      <h2>フォルダ一覧</h2>
      <?php if (empty($terms)) : ?>
        <p>まだフォルダがありません。</p>
      <?php else : ?>
        <table class="wp-list-table widefat fixed striped" style="max-width:720px;">
          <thead>
            <tr>
              <th>フォルダ名</th>
              <th style="width:100px;">件数</th>
              <th style="width:160px;">操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($terms as $term) :
                $filter_url = add_query_arg(
                    [
                        'mode'               => 'list',
                        TOKAI_MEDIA_FOLDER_TAX => $term->slug,
                    ],
                    admin_url('upload.php')
                );
                $term_edit = get_edit_term_link($term, $taxonomy, 'attachment');
                ?>
              <tr>
                <td>
                  <?php
                  $pad = '';
                  if ((int) $term->parent > 0) {
                      $pad = '— ';
                  }
                  echo esc_html($pad . $term->name);
                  ?>
                </td>
                <td><?php echo esc_html((string) $term->count); ?></td>
                <td>
                  <a href="<?php echo esc_url($filter_url); ?>">メディアを見る</a>
                  <?php if ($term_edit) : ?>
                    | <a href="<?php echo esc_url($term_edit); ?>">編集</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <p style="margin-top:16px;">
        <a class="button" href="<?php echo esc_url($edit_link); ?>">詳細なフォルダ管理（並び替え・削除）</a>
      </p>
    </div>
    <?php
}

/**
 * メディア一覧（リスト）のフォルダ絞り込み
 */
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'attachment') {
        return;
    }

    $taxonomy = TOKAI_MEDIA_FOLDER_TAX;
    $selected = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : '';

    wp_dropdown_categories([
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'show_option_all' => 'すべてのフォルダ',
        'hide_empty'      => false,
        'hierarchical'    => true,
        'orderby'         => 'name',
        'value_field'     => 'slug',
        'selected'        => $selected,
    ]);
});

/**
 * メディアグリッド（AJAX）のフォルダ絞り込み
 */
add_filter('ajax_query_attachments_args', function ($query) {
    $taxonomy = TOKAI_MEDIA_FOLDER_TAX;
    if (empty($_REQUEST['query'][$taxonomy])) {
        return $query;
    }

    $slug = sanitize_title(wp_unslash($_REQUEST['query'][$taxonomy]));
    if ($slug === '' || $slug === '0') {
        return $query;
    }

    $tax_query = isset($query['tax_query']) && is_array($query['tax_query']) ? $query['tax_query'] : [];
    $tax_query[] = [
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => [$slug],
    ];
    $query['tax_query'] = $tax_query;
    return $query;
});

/**
 * 添付詳細にフォルダ選択を表示
 */
add_filter('attachment_fields_to_edit', function ($form_fields, $post) {
    $taxonomy = TOKAI_MEDIA_FOLDER_TAX;
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);
    if (is_wp_error($terms)) {
        $terms = [];
    }

    $selected = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);
    if (is_wp_error($selected)) {
        $selected = [];
    }
    $selected_id = !empty($selected) ? (int) $selected[0] : 0;

    $html = '<select name="attachments[' . esc_attr((string) $post->ID) . '][tokai_media_folder]">';
    $html .= '<option value="0">未分類</option>';
    foreach ($terms as $term) {
        $html .= sprintf(
            '<option value="%d"%s>%s</option>',
            (int) $term->term_id,
            selected($selected_id, (int) $term->term_id, false),
            esc_html($term->name)
        );
    }
    $html .= '</select>';
    $html .= '<p class="description"><a href="' . esc_url(admin_url('upload.php?page=tokai-media-folders')) . '">フォルダを作成</a></p>';

    $form_fields['tokai_media_folder'] = [
        'label' => 'フォルダ',
        'input' => 'html',
        'html'  => $html,
    ];

    return $form_fields;
}, 10, 2);

add_filter('attachment_fields_to_save', function ($post, $attachment) {
    if (!isset($attachment['tokai_media_folder'])) {
        return $post;
    }

    $term_id = absint($attachment['tokai_media_folder']);
    if ($term_id > 0) {
        wp_set_object_terms($post['ID'], [$term_id], TOKAI_MEDIA_FOLDER_TAX, false);
    } else {
        wp_set_object_terms($post['ID'], [], TOKAI_MEDIA_FOLDER_TAX, false);
    }

    return $post;
}, 10, 2);

/**
 * メディアライブラリ画面にフォルダ絞り込みUI（グリッド用）
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'upload.php' && $hook !== 'media-new.php') {
        // 投稿編集のメディアモーダルでも使えるよう post.php でも読み込む
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }
    }

    wp_enqueue_script(
        'tokai-media-folders',
        tokai_uri('assets/admin-media-folders.js'),
        ['jquery', 'media-views'],
        TOKAI_THEME_VERSION,
        true
    );

    $terms = get_terms([
        'taxonomy'   => TOKAI_MEDIA_FOLDER_TAX,
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);
    if (is_wp_error($terms)) {
        $terms = [];
    }

    $options = [
        ['slug' => '', 'name' => 'すべてのフォルダ'],
    ];
    foreach ($terms as $term) {
        $options[] = [
            'slug' => $term->slug,
            'name' => $term->name,
        ];
    }

    wp_localize_script('tokai-media-folders', 'tokaiMediaFolders', [
        'taxonomy'  => TOKAI_MEDIA_FOLDER_TAX,
        'options'   => $options,
        'manageUrl' => admin_url('upload.php?page=tokai-media-folders'),
    ]);
});
