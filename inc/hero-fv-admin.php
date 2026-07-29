<?php
/**
 * 管理画面 — FV画像（PC 5枚 + SP 5枚）
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'FV画像',
        'FV画像',
        'edit_pages',
        'tokai-fv',
        'tokai_fv_admin_page',
        'dashicons-images-alt2',
        25
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_tokai-fv') {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
        'tokai-admin-fv',
        tokai_uri('assets/admin-fv.css'),
        [],
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

function tokai_fv_admin_collect_from_post() {
    $slides = [];
    $pc_rows = isset($_POST['tokai_fv_pc']) && is_array($_POST['tokai_fv_pc']) ? wp_unslash($_POST['tokai_fv_pc']) : [];
    $sp_rows = isset($_POST['tokai_fv_sp']) && is_array($_POST['tokai_fv_sp']) ? wp_unslash($_POST['tokai_fv_sp']) : [];

    for ($i = 0; $i < TOKAI_FV_MAX_SLIDES; $i++) {
        $pc = is_array($pc_rows[$i] ?? null) ? $pc_rows[$i] : [];
        $sp = is_array($sp_rows[$i] ?? null) ? $sp_rows[$i] : [];
        $slides[] = [
            'pc' => [
                'id'  => absint($pc['id'] ?? 0),
                'url' => esc_url_raw($pc['url'] ?? ''),
            ],
            'sp' => [
                'id'  => absint($sp['id'] ?? 0),
                'url' => esc_url_raw($sp['url'] ?? ''),
            ],
        ];
    }

    return $slides;
}

function tokai_fv_admin_image_cell($slot, $name_prefix, $label) {
    $id  = (int) ($slot['id'] ?? 0);
    $url = $slot['url'] ?? '';
    $preview = $url ?: '';
    ?>
    <div class="tokai-fv-image" data-label="<?php echo esc_attr($label); ?>">
      <div class="tokai-fv-image__preview">
        <?php if ($preview) : ?>
          <img src="<?php echo esc_url($preview); ?>" alt="">
        <?php else : ?>
          <span class="tokai-fv-image__empty">未設定</span>
        <?php endif; ?>
      </div>
      <input type="hidden" class="tokai-fv-image-id" name="<?php echo esc_attr($name_prefix); ?>[id]" value="<?php echo esc_attr((string) $id); ?>">
      <input type="hidden" class="tokai-fv-image-url" name="<?php echo esc_attr($name_prefix); ?>[url]" value="<?php echo esc_attr($url); ?>">
      <div class="tokai-fv-image__actions">
        <button type="button" class="button tokai-fv-pick">画像を選択</button>
        <button type="button" class="button-link tokai-fv-clear">削除</button>
      </div>
    </div>
    <?php
}

function tokai_fv_admin_page() {
    if (!current_user_can('edit_pages')) {
        wp_die('権限がありません。');
    }

    $notice = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tokai_fv_nonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokai_fv_nonce'])), 'tokai_fv_save')) {
            wp_die('不正なリクエストです。');
        }

        $slides = tokai_fv_admin_collect_from_post();
        $has_pc = false;
        foreach ($slides as $slide) {
            if (!empty($slide['pc']['url']) || !empty($slide['pc']['id'])) {
                $has_pc = true;
                break;
            }
        }

        if (!$has_pc) {
            $notice = 'error';
        } else {
            tokai_fv_save_slides($slides);
            $notice = 'saved';
        }
    }

    if (isset($_POST['tokai_fv_reset']) && check_admin_referer('tokai_fv_reset', 'tokai_fv_reset_nonce')) {
        delete_option(TOKAI_FV_OPTION);
        $notice = 'reset';
    }

    $slides = tokai_fv_get_slides_raw();
    // 表示用に5枠そろえる
    while (count($slides) < TOKAI_FV_MAX_SLIDES) {
        $slides[] = [
            'pc' => ['id' => 0, 'url' => ''],
            'sp' => ['id' => 0, 'url' => ''],
        ];
    }
    ?>
    <div class="wrap tokai-fv-admin">
      <h1>FV画像設定</h1>
      <p class="description">
        トップページのファーストビュー（ヒーロー）画像を最大5枚まで設定できます。<br>
        <strong>PC版は必須</strong>（表示したい枠に設定）／ <strong>SP版は任意</strong>。SP未設定の枠はPC版を表示します。
      </p>

      <?php if ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible"><p>FV画像を保存しました。</p></div>
      <?php elseif ($notice === 'reset') : ?>
        <div class="notice notice-warning is-dismissible"><p>初期画像に戻しました。</p></div>
      <?php elseif ($notice === 'error') : ?>
        <div class="notice notice-error is-dismissible"><p>PC版の画像を1枚以上設定してください。</p></div>
      <?php endif; ?>

      <form method="post">
        <?php wp_nonce_field('tokai_fv_save', 'tokai_fv_nonce'); ?>

        <table class="widefat striped tokai-fv-table">
          <thead>
            <tr>
              <th class="tokai-fv-table__no">#</th>
              <th>PC版 <span class="tokai-fv-req">必須</span></th>
              <th>SP版 <span class="tokai-fv-opt">任意（未設定時はPC版）</span></th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < TOKAI_FV_MAX_SLIDES; $i++) : ?>
              <?php $slide = $slides[$i]; ?>
              <tr>
                <td class="tokai-fv-table__no"><strong><?php echo (int) ($i + 1); ?></strong></td>
                <td><?php tokai_fv_admin_image_cell($slide['pc'], "tokai_fv_pc[{$i}]", 'PC'); ?></td>
                <td><?php tokai_fv_admin_image_cell($slide['sp'], "tokai_fv_sp[{$i}]", 'SP'); ?></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>

        <p class="tokai-fv-admin__actions">
          <button type="submit" class="button button-primary button-hero">変更を保存</button>
        </p>
      </form>

      <form method="post" class="tokai-fv-admin__reset" onsubmit="return confirm('初期のテーマ画像に戻します。よろしいですか？');">
        <?php wp_nonce_field('tokai_fv_reset', 'tokai_fv_reset_nonce'); ?>
        <input type="hidden" name="tokai_fv_reset" value="1">
        <button type="submit" class="button">初期画像に戻す</button>
      </form>
    </div>
    <?php
}
