<?php
/**
 * WordPress 管理画面 — メンバー一括管理
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'メンバー管理',
        'メンバー管理',
        'edit_pages',
        'tokai-members',
        'tokai_members_admin_page',
        'dashicons-groups',
        26
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_tokai-members') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_style(
        'tokai-members-admin',
        tokai_uri('assets/admin-members.css'),
        [],
        TOKAI_THEME_VERSION
    );

    wp_enqueue_script(
        'tokai-members-admin',
        tokai_uri('assets/admin-members.js'),
        ['jquery', 'jquery-ui-sortable'],
        TOKAI_THEME_VERSION,
        true
    );
});

/**
 * POST から学年別キーの選手データをフラットな配列へ復元
 */
function tokai_members_collect_players_from_post() {
    $players    = [];
    $grade_keys = tokai_members_grade_keys();

    foreach ($grade_keys as $grade => $key) {
        $field = 'tokai_players_' . $key;
        if (empty($_POST[$field]) || !is_array($_POST[$field])) {
            continue;
        }

        foreach ($_POST[$field] as $player) {
            if (!is_array($player)) {
                continue;
            }

            $players[] = [
                'name'     => sanitize_text_field(wp_unslash($player['name'] ?? '')),
                'romaji'   => sanitize_text_field(wp_unslash($player['romaji'] ?? '')),
                'grade'    => $grade,
                'number'   => sanitize_text_field(wp_unslash($player['number'] ?? '')),
                'previous' => sanitize_text_field(wp_unslash($player['previous'] ?? '')),
                'image_id' => absint($player['image_id'] ?? 0),
                'image'    => esc_url_raw(wp_unslash($player['image'] ?? '')),
            ];
        }
    }

    // 旧形式（学年横断の tokai_players[N]）も後方互換で受け付ける
    if (empty($players) && !empty($_POST['tokai_players']) && is_array($_POST['tokai_players'])) {
        foreach ($_POST['tokai_players'] as $player) {
            if (!is_array($player)) {
                continue;
            }

            $players[] = [
                'name'     => sanitize_text_field(wp_unslash($player['name'] ?? '')),
                'romaji'   => sanitize_text_field(wp_unslash($player['romaji'] ?? '')),
                'grade'    => sanitize_text_field(wp_unslash($player['grade'] ?? '')),
                'number'   => sanitize_text_field(wp_unslash($player['number'] ?? '')),
                'previous' => sanitize_text_field(wp_unslash($player['previous'] ?? '')),
                'image_id' => absint($player['image_id'] ?? 0),
                'image'    => esc_url_raw(wp_unslash($player['image'] ?? '')),
            ];
        }
    }

    return tokai_sort_players_by_number($players);
}

function tokai_members_admin_page() {
    if (!current_user_can('edit_pages')) {
        wp_die('権限がありません。');
    }

    $notice        = '';
    $notice_error  = '';
    $import_result = null;
    $was_repaired  = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tokai_members_nonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokai_members_nonce'])), 'tokai_members_save')) {
            wp_die('不正なリクエストです。');
        }

        $payload = [
            'players' => tokai_members_collect_players_from_post(),
            'staff'   => [],
        ];

        if (!empty($_POST['tokai_staff']) && is_array($_POST['tokai_staff'])) {
            foreach ($_POST['tokai_staff'] as $member) {
                if (!is_array($member)) {
                    continue;
                }

                $payload['staff'][] = [
                    'name'     => sanitize_text_field(wp_unslash($member['name'] ?? '')),
                    'role'     => sanitize_text_field(wp_unslash($member['role'] ?? '')),
                    'image_id' => absint($member['image_id'] ?? 0),
                    'image'    => esc_url_raw(wp_unslash($member['image'] ?? '')),
                ];
            }
        }

        // 管理画面フォームに無い crop 値は既存データから引き継ぐ
        $payload = tokai_members_preserve_crop_fields($payload);

        $saved = tokai_save_members_raw($payload);
        if (is_wp_error($saved)) {
            $notice_error = $saved->get_error_message();
        } else {
            $notice = 'saved';
        }
    }

    if (isset($_POST['tokai_members_repair']) && check_admin_referer('tokai_members_repair', 'tokai_members_repair_nonce')) {
        tokai_repair_members_from_default();
        $notice = 'repair';
    }

    if (isset($_POST['tokai_members_reset']) && check_admin_referer('tokai_members_reset', 'tokai_members_reset_nonce')) {
        delete_option(TOKAI_MEMBERS_OPTION);
        tokai_import_default_members();
        $notice = 'reset';
    }

    if (isset($_POST['tokai_members_import_images']) && check_admin_referer('tokai_members_import_images', 'tokai_members_import_images_nonce')) {
        $result = tokai_import_member_images_to_media();
        $notice = 'imported';
        $import_result = $result;
    }

    // 破損検知用に option を直接読む（自動修復前の状態）
    $stored_raw = get_option(TOKAI_MEMBERS_OPTION, null);
    if (is_array($stored_raw) && tokai_members_data_looks_broken($stored_raw)) {
        $was_repaired = true;
    }

    $raw     = tokai_get_members_raw();
    $players = $raw['players'] ?? [];
    $staff   = $raw['staff'] ?? [];
    $grade_keys = tokai_members_grade_keys();
    $grades     = array_keys($grade_keys);

    $counts = ['3年生' => 0, '2年生' => 0, '1年生' => 0];
    foreach ($players as $player) {
        $g = $player['grade'] ?? '';
        if (isset($counts[$g])) {
            $counts[$g]++;
        }
    }
    ?>
    <div class="wrap tokai-members-admin">
      <h1>メンバー管理</h1>
      <p class="description">画像と選手名を学年・スタッフごとに一括で編集できます。並び順はドラッグで変更できます。</p>

      <?php if ($notice_error) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($notice_error); ?></p></div>
      <?php elseif ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible"><p>メンバー情報を保存しました。</p></div>
      <?php elseif ($notice === 'repair') : ?>
        <div class="notice notice-success is-dismissible"><p>メンバーデータを修復しました。3年生・2年生・1年生が復帰しています。</p></div>
      <?php elseif ($notice === 'reset') : ?>
        <div class="notice notice-warning is-dismissible"><p>初期データに戻しました。</p></div>
      <?php elseif ($notice === 'imported' && !empty($import_result)) : ?>
        <div class="notice notice-success is-dismissible">
          <p>選手画像をメディアに取り込みました（成功 <?php echo esc_html((string) $import_result['imported']); ?> 件<?php if (!empty($import_result['failed'])) : ?> / 失敗 <?php echo esc_html((string) $import_result['failed']); ?> 件<?php endif; ?>）。</p>
        </div>
      <?php endif; ?>

      <?php if ($was_repaired && $notice !== 'repair') : ?>
        <div class="notice notice-warning">
          <p><strong>3年生または2年生のデータが欠けていたため、初期メンバー一覧から自動修復しました。</strong></p>
          <p>内容を確認し、問題なければ下の「変更を保存」は押さず、このまま公開ページを確認してください。並び替えや画像変更をしたあとで保存してください。</p>
        </div>
      <?php endif; ?>

      <div class="tokai-members-admin__toolbar tokai-members-admin__toolbar--top">
        <form method="post" class="tokai-members-import" style="display:inline-block;margin-right:8px;">
          <?php wp_nonce_field('tokai_members_repair', 'tokai_members_repair_nonce'); ?>
          <input type="hidden" name="tokai_members_repair" value="1">
          <button type="submit" class="button button-primary">メンバーデータを修復（学年を戻す）</button>
        </form>
        <form method="post" class="tokai-members-import" style="display:inline-block;">
          <?php wp_nonce_field('tokai_members_import_images', 'tokai_members_import_images_nonce'); ?>
          <input type="hidden" name="tokai_members_import_images" value="1">
          <button type="submit" class="button button-secondary">外部URLの画像をメディアに取り込む</button>
        </form>
      </div>

      <p class="description">
        現在の人数：3年生 <?php echo esc_html((string) $counts['3年生']); ?>人 /
        2年生 <?php echo esc_html((string) $counts['2年生']); ?>人 /
        1年生 <?php echo esc_html((string) $counts['1年生']); ?>人
      </p>

      <form method="post" id="tokai-members-form">
        <?php wp_nonce_field('tokai_members_save', 'tokai_members_nonce'); ?>

        <div class="tokai-members-admin__toolbar">
          <button type="submit" class="button button-primary button-hero">変更を保存</button>
          <a href="<?php echo esc_url(tokai_page_url('members')); ?>" class="button" target="_blank" rel="noopener">公開ページを確認</a>
        </div>

        <?php foreach ($grades as $grade) :
            $grade_key = $grade_keys[$grade];
            $grade_players = tokai_sort_players_by_number(array_values(array_filter($players, function ($p) use ($grade) {
                return ($p['grade'] ?? '') === $grade;
            })));
            ?>
          <section class="tokai-members-panel" data-grade="<?php echo esc_attr($grade); ?>">
            <div class="tokai-members-panel__head">
              <h2><?php echo esc_html($grade); ?>（<?php echo esc_html((string) count($grade_players)); ?>人）</h2>
              <button type="button" class="button tokai-add-row" data-type="player" data-grade="<?php echo esc_attr($grade); ?>">＋ 選手を追加</button>
            </div>
            <table class="widefat fixed striped tokai-members-table">
              <thead>
                <tr>
                  <th class="col-sort"></th>
                  <th class="col-image">画像</th>
                  <th class="col-number">背番号</th>
                  <th class="col-name">選手名</th>
                  <th class="col-romaji">ローマ字</th>
                  <th class="col-previous">前所属</th>
                  <th class="col-action"></th>
                </tr>
              </thead>
              <tbody class="tokai-sortable" data-type="player" data-grade="<?php echo esc_attr($grade); ?>">
                <?php foreach ($grade_players as $i => $player) :
                    tokai_members_admin_render_player_row($player, $grade, $grade_key, $i);
                endforeach; ?>
              </tbody>
            </table>
          </section>
        <?php endforeach; ?>

        <section class="tokai-members-panel" data-grade="staff">
          <div class="tokai-members-panel__head">
            <h2>スタッフ</h2>
            <button type="button" class="button tokai-add-row" data-type="staff">＋ スタッフを追加</button>
          </div>
          <table class="widefat fixed striped tokai-members-table">
            <thead>
              <tr>
                <th class="col-sort"></th>
                <th class="col-image">画像</th>
                <th class="col-name">名前</th>
                <th class="col-role">役職</th>
                <th class="col-action"></th>
              </tr>
            </thead>
            <tbody class="tokai-sortable" data-type="staff">
              <?php foreach ($staff as $i => $member) :
                  tokai_members_admin_render_staff_row($member, $i);
              endforeach; ?>
            </tbody>
          </table>
        </section>

        <div class="tokai-members-admin__toolbar tokai-members-admin__toolbar--bottom">
          <button type="submit" class="button button-primary button-hero">変更を保存</button>
        </div>
      </form>

      <form method="post" class="tokai-members-reset" onsubmit="return confirm('初期データに戻します。よろしいですか？');">
        <?php wp_nonce_field('tokai_members_reset', 'tokai_members_reset_nonce'); ?>
        <input type="hidden" name="tokai_members_reset" value="1">
        <button type="submit" class="button-link-delete">初期データに戻す</button>
      </form>

      <template id="tokai-player-row-template">
        <?php tokai_members_admin_render_player_row(
            ['name' => '', 'romaji' => '', 'grade' => '__GRADE__', 'number' => '', 'previous' => '', 'image_id' => 0, 'image' => ''],
            '__GRADE__',
            '__GRADE_KEY__',
            '__INDEX__'
        ); ?>
      </template>
      <template id="tokai-staff-row-template">
        <?php tokai_members_admin_render_staff_row(['name' => '', 'role' => '', 'image_id' => 0, 'image' => ''], '__INDEX__'); ?>
      </template>
    </div>
    <?php
}

function tokai_members_admin_image_cell($image_id, $image_url, $index_key) {
    $preview = $image_url ?: tokai_asset('team-logo.png');
    ?>
    <div class="tokai-member-image" data-index="<?php echo esc_attr($index_key); ?>">
      <div class="tokai-member-image__preview">
        <img src="<?php echo esc_url($preview); ?>" alt="">
      </div>
      <input type="hidden" class="tokai-image-id" name="<?php echo esc_attr($index_key); ?>[image_id]" value="<?php echo esc_attr((string) $image_id); ?>">
      <input type="hidden" class="tokai-image-url" name="<?php echo esc_attr($index_key); ?>[image]" value="<?php echo esc_attr($image_url); ?>">
      <button type="button" class="button tokai-pick-image">画像を選択</button>
      <button type="button" class="button-link tokai-clear-image">削除</button>
    </div>
    <?php
}

function tokai_members_admin_render_player_row($player, $grade, $grade_key, $index = 0) {
    $idx = 'tokai_players_' . $grade_key . '[' . $index . ']';
    $image_id  = (int) ($player['image_id'] ?? 0);
    $image_url = $image_id ? (wp_get_attachment_image_url($image_id, 'medium') ?: '') : ($player['image'] ?? '');
    ?>
    <tr class="tokai-member-row">
      <td class="col-sort"><span class="tokai-sort-handle dashicons dashicons-menu" aria-hidden="true"></span></td>
      <td class="col-image"><?php tokai_members_admin_image_cell($image_id, $image_url, $idx); ?></td>
      <td class="col-number">
        <input type="text" class="small-text" name="<?php echo esc_attr($idx); ?>[number]" value="<?php echo esc_attr($player['number'] ?? ''); ?>" placeholder="10" inputmode="numeric">
      </td>
      <td class="col-name">
        <input type="text" class="regular-text" name="<?php echo esc_attr($idx); ?>[name]" value="<?php echo esc_attr($player['name'] ?? ''); ?>" placeholder="山田 太郎">
        <input type="hidden" name="<?php echo esc_attr($idx); ?>[grade]" value="<?php echo esc_attr($grade); ?>">
      </td>
      <td class="col-romaji">
        <input type="text" class="regular-text" name="<?php echo esc_attr($idx); ?>[romaji]" value="<?php echo esc_attr($player['romaji'] ?? ''); ?>" placeholder="Taro.Y">
      </td>
      <td class="col-previous">
        <input type="text" class="regular-text" name="<?php echo esc_attr($idx); ?>[previous]" value="<?php echo esc_attr($player['previous'] ?? ''); ?>" placeholder="前所属クラブ">
      </td>
      <td class="col-action"><button type="button" class="button-link-delete tokai-remove-row">削除</button></td>
    </tr>
    <?php
}

function tokai_members_admin_render_staff_row($member, $index = 0) {
    $idx = 'tokai_staff[' . $index . ']';
    $image_id  = (int) ($member['image_id'] ?? 0);
    $image_url = $image_id ? (wp_get_attachment_image_url($image_id, 'medium') ?: '') : ($member['image'] ?? '');
    ?>
    <tr class="tokai-member-row">
      <td class="col-sort"><span class="tokai-sort-handle dashicons dashicons-menu" aria-hidden="true"></span></td>
      <td class="col-image"><?php tokai_members_admin_image_cell($image_id, $image_url, $idx); ?></td>
      <td class="col-name">
        <input type="text" class="regular-text" name="<?php echo esc_attr($idx); ?>[name]" value="<?php echo esc_attr($member['name'] ?? ''); ?>" placeholder="山田 太郎">
      </td>
      <td class="col-role">
        <input type="text" class="regular-text" name="<?php echo esc_attr($idx); ?>[role]" value="<?php echo esc_attr($member['role'] ?? ''); ?>" placeholder="監督">
      </td>
      <td class="col-action"><button type="button" class="button-link-delete tokai-remove-row">削除</button></td>
    </tr>
    <?php
}
