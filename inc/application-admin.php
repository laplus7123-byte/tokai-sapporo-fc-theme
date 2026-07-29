<?php
/**
 * 管理画面 — アプリ紹介（Overview / Feature画像）
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'アプリ紹介',
        'アプリ紹介',
        'edit_pages',
        'tokai-application',
        'tokai_application_admin_page',
        'dashicons-smartphone',
        25.5
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_tokai-application') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style(
        'tokai-admin-fv',
        tokai_uri('assets/admin-fv.css'),
        [],
        TOKAI_THEME_VERSION
    );
    wp_enqueue_style(
        'tokai-admin-application',
        tokai_uri('assets/admin-application.css'),
        ['tokai-admin-fv'],
        TOKAI_THEME_VERSION
    );
    wp_enqueue_script(
        'tokai-admin-fv',
        tokai_uri('assets/admin-fv.js'),
        ['jquery'],
        TOKAI_THEME_VERSION,
        true
    );
});

function tokai_application_admin_page() {
    if (!current_user_can('edit_pages')) {
        wp_die('権限がありません。');
    }

    $notice = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tokai_app_nonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokai_app_nonce'])), 'tokai_app_save')) {
            wp_die('不正なリクエストです。');
        }

        $lead = isset($_POST['tokai_app_overview_lead'])
            ? wp_unslash($_POST['tokai_app_overview_lead'])
            : '';
        tokai_app_save_overview_lead($lead);

        $rows = isset($_POST['tokai_app_feature']) && is_array($_POST['tokai_app_feature'])
            ? wp_unslash($_POST['tokai_app_feature'])
            : [];

        $features = [];
        for ($i = 0; $i < 3; $i++) {
            $row = is_array($rows[$i] ?? null) ? $rows[$i] : [];
            $features[] = [
                'title' => $row['title'] ?? '',
                'text'  => $row['text'] ?? '',
                'image' => [
                    'id'  => $row['image']['id'] ?? 0,
                    'url' => $row['image']['url'] ?? '',
                ],
            ];
        }
        tokai_app_save_features($features);
        $notice = 'saved';
    }

    $lead     = tokai_app_get_overview_lead();
    $features = tokai_app_get_features();
    ?>
    <div class="wrap tokai-app-admin">
      <h1>アプリ紹介</h1>
      <p class="description">Applicationページの Overview 文言と、「アプリでできること」3項目の画像・見出し・本文を編集できます。画像は番号と見出しのあいだに表示されます。</p>

      <?php if ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible"><p>アプリ紹介を保存しました。</p></div>
      <?php endif; ?>

      <form method="post">
        <?php wp_nonce_field('tokai_app_save', 'tokai_app_nonce'); ?>

        <div class="tokai-app-panel">
          <h2>Overview</h2>
          <p>
            <label for="tokai_app_overview_lead"><strong>リード文</strong></label>
          </p>
          <textarea class="large-text" rows="3" id="tokai_app_overview_lead" name="tokai_app_overview_lead"><?php echo esc_textarea($lead); ?></textarea>
        </div>

        <?php foreach ($features as $i => $feature) : ?>
          <?php $num = sprintf('%02d', $i + 1); ?>
          <div class="tokai-app-panel">
            <h2>Feature <?php echo esc_html($num); ?></h2>
            <div class="tokai-app-feature-grid">
              <div class="tokai-app-feature-fields">
                <p>
                  <label><strong>見出し</strong></label><br>
                  <input type="text" class="regular-text" name="tokai_app_feature[<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr($feature['title']); ?>">
                </p>
                <p>
                  <label><strong>本文</strong></label><br>
                  <textarea class="large-text" rows="4" name="tokai_app_feature[<?php echo (int) $i; ?>][text]"><?php echo esc_textarea($feature['text']); ?></textarea>
                </p>
              </div>
              <div class="tokai-app-feature-image">
                <p><strong>画像</strong>（番号と見出しのあいだ・任意）</p>
                <?php
                tokai_fv_admin_image_cell(
                    $feature['image'],
                    "tokai_app_feature[{$i}][image]",
                    "Feature {$num}"
                );
                ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <p>
          <button type="submit" class="button button-primary button-hero">変更を保存</button>
        </p>
      </form>
    </div>
    <?php
}
