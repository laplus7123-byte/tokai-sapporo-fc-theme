<?php
/**
 * メンバーデータの保存・REST API・初期インポート
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_MEMBERS_OPTION', 'tokai_members_data');

function tokai_members_default_path() {
    return get_template_directory() . '/inc/default-members.json';
}

function tokai_import_default_members() {
    $path = tokai_members_default_path();
    if (!file_exists($path)) {
        return ['players' => [], 'staff' => []];
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['players' => [], 'staff' => []];
    }

    $stored = tokai_prepare_members_for_storage($data);
    update_option(TOKAI_MEMBERS_OPTION, $stored, false);

    return $stored;
}

function tokai_members_grade_keys() {
    return [
        '3年生' => 'g3',
        '2年生' => 'g2',
        '1年生' => 'g1',
    ];
}

/**
 * 学年欠け・極端な人数減など、破損したメンバーデータかを判定
 */
function tokai_members_data_looks_broken($data) {
    if (!is_array($data)) {
        return true;
    }

    $players = $data['players'] ?? [];
    $player_count = count($players);

    if ($player_count === 0) {
        return true;
    }

    if ($player_count < 5) {
        return true;
    }

    $counts = [
        '3年生' => 0,
        '2年生' => 0,
        '1年生' => 0,
    ];

    foreach ($players as $player) {
        $grade = $player['grade'] ?? '';
        if (isset($counts[$grade])) {
            $counts[$grade]++;
        }
    }

    // いずれかの学年が0人なら、並び替え保存の上書き事故とみなす
    foreach ($counts as $count) {
        if ($count === 0) {
            return true;
        }
    }

    return false;
}

function tokai_get_members_raw() {
    $data = get_option(TOKAI_MEMBERS_OPTION, null);

    if (!is_array($data)) {
        return tokai_import_default_members();
    }

    $player_count = count($data['players'] ?? []);
    $staff_count  = count($data['staff'] ?? []);

    if ($player_count === 0 && $staff_count === 0) {
        return tokai_import_default_members();
    }

    if (tokai_members_data_looks_broken($data)) {
        return tokai_repair_members_from_default();
    }

    $players = $data['players'] ?? [];
    $with_number = 0;
    foreach ($players as $player) {
        if (!empty($player['number'])) {
            $with_number++;
        }
    }

    // 背番号がほぼ無い場合は Football Navi 初期データから補完して保存
    if ($player_count >= 10 && $with_number < max(5, (int) floor($player_count * 0.3))) {
        $enriched = tokai_enrich_players_with_default_details($players);
        $data['players'] = $enriched;
        $data = tokai_prepare_members_for_storage($data);
        update_option(TOKAI_MEMBERS_OPTION, $data, false);
        return $data;
    }

    // ローマ字・前所属だけ欠けている場合も補完
    $missing_detail = 0;
    foreach ($players as $player) {
        if (($player['romaji'] ?? '') === '' || ($player['previous'] ?? '') === '') {
            $missing_detail++;
        }
    }
    if ($player_count >= 10 && $missing_detail > (int) floor($player_count * 0.5)) {
        $enriched = tokai_enrich_players_with_default_details($players);
        $data['players'] = $enriched;
        $data = tokai_prepare_members_for_storage($data);
        update_option(TOKAI_MEMBERS_OPTION, $data, false);
        return $data;
    }

    // 顔位置クロップが無い場合は初期JSONから補完
    $missing_crop = 0;
    foreach ($players as $player) {
        if (!isset($player['cropTy'])) {
            $missing_crop++;
        }
    }
    if ($player_count >= 10 && $missing_crop > (int) floor($player_count * 0.5)) {
        $enriched = tokai_enrich_players_with_default_details($players);
        $data['players'] = $enriched;
        $data = tokai_prepare_members_for_storage($data);
        update_option(TOKAI_MEMBERS_OPTION, $data, false);
        return $data;
    }

    $data['players'] = tokai_sort_players_by_number($players);
    return $data;
}

function tokai_repair_members_from_default() {
    $path = tokai_members_default_path();
    if (!file_exists($path)) {
        return ['players' => [], 'staff' => []];
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['players' => [], 'staff' => []];
    }

    $stored = tokai_prepare_members_for_storage($data);
    update_option(TOKAI_MEMBERS_OPTION, $stored, false);

    return $stored;
}

function tokai_player_number_sort_key($player) {
    $number = preg_replace('/\D+/', '', (string) ($player['number'] ?? ''));
    if ($number !== '') {
        return [(int) $number, $player['name'] ?? ''];
    }
    return [9999, $player['name'] ?? ''];
}

function tokai_sort_players_by_number($players) {
    $players = array_values($players);
    usort($players, function ($a, $b) {
        $ka = tokai_player_number_sort_key($a);
        $kb = tokai_player_number_sort_key($b);
        if ($ka[0] === $kb[0]) {
            return strcmp((string) $ka[1], (string) $kb[1]);
        }
        return $ka[0] <=> $kb[0];
    });
    return $players;
}

/**
 * 既存データに背番号・前所属・ローマ字が無い場合、初期JSONから補完する
 */
function tokai_enrich_players_with_default_details($players) {
    $path = tokai_members_default_path();
    if (!file_exists($path)) {
        return $players;
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['players'])) {
        return $players;
    }

    $by_name = [];
    foreach ($data['players'] as $player) {
        $name = $player['name'] ?? '';
        if ($name !== '') {
            $by_name[$name] = $player;
        }
    }

    $changed = false;
    foreach ($players as &$player) {
        $name = $player['name'] ?? '';
        if ($name === '' || empty($by_name[$name])) {
            continue;
        }
        $src = $by_name[$name];
        foreach (['number', 'previous', 'romaji'] as $key) {
            if (($player[$key] ?? '') === '' && ($src[$key] ?? '') !== '') {
                $player[$key] = $src[$key];
                $changed = true;
            }
        }
        foreach (['cropX', 'cropY', 'cropZoom', 'cropTy'] as $key) {
            if (!isset($player[$key]) && isset($src[$key])) {
                $player[$key] = $src[$key];
                $changed = true;
            }
        }
    }
    unset($player);

    return $players;
}

/**
 * 管理画面保存時に crop 値を既存データから引き継ぐ
 */
function tokai_members_preserve_crop_fields($payload) {
    $existing = get_option(TOKAI_MEMBERS_OPTION, null);
    if (!is_array($existing)) {
        return $payload;
    }

    $by_name = [];
    foreach (array_merge($existing['players'] ?? [], $existing['staff'] ?? []) as $row) {
        $name = $row['name'] ?? '';
        if ($name !== '') {
            $by_name[$name] = tokai_sanitize_member_crop_fields($row);
        }
    }

    foreach (['players', 'staff'] as $group) {
        if (empty($payload[$group]) || !is_array($payload[$group])) {
            continue;
        }
        foreach ($payload[$group] as &$row) {
            $name = $row['name'] ?? '';
            if ($name === '' || empty($by_name[$name])) {
                continue;
            }
            if (!isset($row['cropTy'])) {
                $row = array_merge($row, $by_name[$name]);
            }
        }
        unset($row);
    }

    return $payload;
}

function tokai_resolve_member_image_url($member) {
    if (!empty($member['image_id'])) {
        $url = wp_get_attachment_image_url((int) $member['image_id'], 'large');
        if ($url) {
            return $url;
        }
    }

    if (!empty($member['image'])) {
        return esc_url($member['image']);
    }

    return '';
}

function tokai_member_crop_style_attr($member) {
    $has_crop = isset($member['cropX']) || isset($member['cropY']) || isset($member['cropZoom']) || isset($member['cropTy']);
    if (!$has_crop) {
        return '';
    }

    $x  = isset($member['cropX']) ? (float) $member['cropX'] : 50.0;
    $y  = isset($member['cropY']) ? (float) $member['cropY'] : 34.0;
    $z  = isset($member['cropZoom']) ? (float) $member['cropZoom'] : 1.08;
    $ty = isset($member['cropTy']) ? (float) $member['cropTy'] : 0.0;

    return sprintf(
        '--crop-x: %.1f%%; --crop-y: %.1f%%; --crop-zoom: %.2f; --crop-ty: %.1f;',
        $x,
        $y,
        $z,
        $ty
    );
}

function tokai_sanitize_member_crop_fields($member) {
    $out = [];
    foreach (['cropX', 'cropY', 'cropZoom', 'cropTy'] as $key) {
        if (!isset($member[$key]) || $member[$key] === '' || $member[$key] === null) {
            continue;
        }
        $out[$key] = round((float) $member[$key], 2);
    }
    return $out;
}

function tokai_render_member_card($member, $subtitle_key = '') {
    $subtitle = $subtitle_key ? ($member[$subtitle_key] ?? '') : '';
    $image    = tokai_resolve_member_image_url($member);
    $name     = $member['name'] ?? '';
    $number   = $member['number'] ?? '';
    $romaji   = $member['romaji'] ?? '';
    $previous = $member['previous'] ?? '';
    $crop_style = tokai_member_crop_style_attr($member);

    $overlay_parts = array_filter([$number, $romaji], function ($v) {
        return $v !== '' && $v !== null;
    });
    $overlay_text = implode(' ', $overlay_parts);

    ob_start();
    ?>
    <div class="member-card">
      <div class="member-card__photo">
        <?php if ($image) : ?>
          <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy"<?php echo $crop_style !== '' ? ' style="' . esc_attr($crop_style) . '"' : ''; ?>>
        <?php else : ?>
          <span class="member-card__placeholder" aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($overlay_text !== '') : ?>
          <div class="member-card__photo-shade">
            <span class="member-card__photo-label"><?php echo esc_html($overlay_text); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <p class="member-card__name"><?php echo esc_html($name); ?></p>
      <?php if ($previous) : ?>
        <div class="member-card__meta">
          <p class="member-card__meta-item"><span>前所属</span><?php echo esc_html($previous); ?></p>
        </div>
      <?php endif; ?>
      <?php if ($subtitle) : ?>
        <p class="member-card__position"><?php echo esc_html($subtitle); ?></p>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function tokai_render_members_html($data = null) {
    $data   = $data ?? tokai_get_members_public();
    $grades = ['3年生', '2年生', '1年生'];

    ob_start();
    ?>
    <nav class="members-nav" aria-label="メンバーカテゴリー">
      <button type="button" class="members-nav__btn is-active" data-target="members-grade-3">3年生</button>
      <button type="button" class="members-nav__btn" data-target="members-grade-2">2年生</button>
      <button type="button" class="members-nav__btn" data-target="members-grade-1">1年生</button>
      <button type="button" class="members-nav__btn" data-target="members-staff">スタッフ</button>
    </nav>
    <?php
    foreach ($grades as $grade) {
        $players = array_values(array_filter($data['players'] ?? [], function ($player) use ($grade) {
            return ($player['grade'] ?? '') === $grade;
        }));
        $players = tokai_sort_players_by_number($players);

        if (empty($players)) {
            continue;
        }

        $section_id = 'members-grade-' . mb_substr($grade, 0, 1);
        ?>
        <div class="members-category" id="<?php echo esc_attr($section_id); ?>">
          <h2 class="members-category__title"><?php echo esc_html($grade); ?></h2>
          <div class="members-grid">
            <?php foreach ($players as $player) : ?>
              <?php echo tokai_render_member_card($player); ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php
    }

    $staff = $data['staff'] ?? [];
    if (!empty($staff)) {
        ?>
        <div class="members-category" id="members-staff">
          <h2 class="members-category__title">Staff</h2>
          <div class="members-grid">
            <?php foreach ($staff as $member) : ?>
              <?php echo tokai_render_member_card($member, 'role'); ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php
    }

    $html = ob_get_clean();

    if (trim($html) === '') {
        return '<p class="members-empty">メンバー情報がありません。</p>';
    }

    return $html;
}

function tokai_prepare_members_for_storage($data) {
    $players = [];
    foreach ($data['players'] ?? [] as $player) {
        $name = sanitize_text_field($player['name'] ?? '');
        if ($name === '') {
            continue;
        }

        $grade = sanitize_text_field($player['grade'] ?? '3年生');
        if (!in_array($grade, ['3年生', '2年生', '1年生'], true)) {
            $grade = '3年生';
        }

        $number = sanitize_text_field((string) ($player['number'] ?? ''));
        $number = preg_replace('/\D+/', '', $number) ?: '';

        $row = [
            'name'     => $name,
            'romaji'   => sanitize_text_field($player['romaji'] ?? ''),
            'grade'    => $grade,
            'number'   => $number,
            'previous' => sanitize_text_field($player['previous'] ?? ''),
            'image_id' => absint($player['image_id'] ?? 0),
            'image'    => esc_url_raw($player['image'] ?? ''),
        ];
        $players[] = array_merge($row, tokai_sanitize_member_crop_fields($player));
    }

    $players = tokai_sort_players_by_number($players);

    $staff = [];
    foreach ($data['staff'] ?? [] as $member) {
        $name = sanitize_text_field($member['name'] ?? '');
        if ($name === '') {
            continue;
        }

        $row = [
            'name'     => $name,
            'role'     => sanitize_text_field($member['role'] ?? ''),
            'image_id' => absint($member['image_id'] ?? 0),
            'image'    => esc_url_raw($member['image'] ?? ''),
        ];
        $staff[] = array_merge($row, tokai_sanitize_member_crop_fields($member));
    }

    return [
        'players' => $players,
        'staff'   => $staff,
    ];
}

function tokai_get_members_public() {
    $raw = tokai_get_members_raw();

    $players = array_map(function ($player) {
        $row = [
            'name'     => $player['name'],
            'romaji'   => $player['romaji'] ?? '',
            'grade'    => $player['grade'],
            'number'   => $player['number'] ?? '',
            'previous' => $player['previous'] ?? '',
            'image'    => tokai_resolve_member_image_url($player),
        ];
        return array_merge($row, tokai_sanitize_member_crop_fields($player));
    }, $raw['players'] ?? []);

    $players = tokai_sort_players_by_number($players);

    $staff = array_map(function ($member) {
        $row = [
            'name'  => $member['name'],
            'role'  => $member['role'] ?? '',
            'image' => tokai_resolve_member_image_url($member),
        ];
        return array_merge($row, tokai_sanitize_member_crop_fields($member));
    }, $raw['staff'] ?? []);

    return [
        'source'  => home_url('/'),
        'players' => $players,
        'staff'   => $staff,
    ];
}

function tokai_save_members_raw($data, $args = []) {
    $stored = tokai_prepare_members_for_storage($data);
    $force  = !empty($args['force']);

    $existing = get_option(TOKAI_MEMBERS_OPTION, null);
    if (!$force && is_array($existing)) {
        $existing_count = count($existing['players'] ?? []);
        $new_count      = count($stored['players']);

        // 既存の大半が消える保存は拒否（並び替え事故の再発防止）
        if ($existing_count >= 10 && $new_count < max(5, (int) floor($existing_count * 0.4))) {
            return new WP_Error(
                'tokai_members_too_few',
                sprintf(
                    '保存を中止しました。選手が %d 人から %d 人に減ってしまうためです。「メンバーデータを修復」を押すか、全学年の選手が表示されていることを確認してから保存してください。',
                    $existing_count,
                    $new_count
                )
            );
        }

        if (!$force && tokai_members_data_looks_broken($stored) && $existing_count > $new_count) {
            return new WP_Error(
                'tokai_members_missing_grade',
                '保存を中止しました。3年生・2年生・1年生のいずれかが空のまま保存されそうです。「メンバーデータを修復」を押してください。'
            );
        }
    }

    update_option(TOKAI_MEMBERS_OPTION, $stored, false);
    return $stored;
}

function tokai_is_external_member_image($url) {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $home = wp_parse_url(home_url(), PHP_URL_HOST);
    $host = wp_parse_url($url, PHP_URL_HOST);

    return $host && $host !== $home;
}

function tokai_find_member_attachment_by_source($source_url) {
    $attachments = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'meta_key'       => '_tokai_member_source_url',
        'meta_value'     => $source_url,
        'fields'         => 'ids',
    ]);

    return !empty($attachments) ? (int) $attachments[0] : 0;
}

function tokai_sideload_member_image($source_url, $title) {
    if (!$source_url || !tokai_is_external_member_image($source_url)) {
        return 0;
    }

    $existing = tokai_find_member_attachment_by_source($source_url);
    if ($existing) {
        return $existing;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($source_url, 30);
    if (is_wp_error($tmp)) {
        return 0;
    }

    $path     = wp_parse_url($source_url, PHP_URL_PATH);
    $basename = $path ? basename($path) : '';
    $ext      = pathinfo($basename, PATHINFO_EXTENSION);
    if (!$ext) {
        $ext = 'jpg';
    }

    $file_array = [
        'name'     => sanitize_file_name(($title ?: 'member') . '.' . $ext),
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file_array, 0, $title ?: '選手画像');
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }

    update_post_meta($attachment_id, '_tokai_member_source_url', esc_url_raw($source_url));
    wp_update_post([
        'ID'         => $attachment_id,
        'post_title' => $title ?: '選手画像',
    ]);

    return (int) $attachment_id;
}

function tokai_import_member_images_to_media() {
    $raw     = tokai_get_members_raw();
    $updated = ['players' => [], 'staff' => []];
    $imported = 0;
    $failed   = 0;

    foreach ($raw['players'] ?? [] as $player) {
        $image_id  = (int) ($player['image_id'] ?? 0);
        $image_url = $player['image'] ?? '';

        if (!$image_id && $image_url && tokai_is_external_member_image($image_url)) {
            $new_id = tokai_sideload_member_image($image_url, $player['name'] ?? '選手画像');
            if ($new_id) {
                $image_id = $new_id;
                $image_url = wp_get_attachment_url($new_id) ?: '';
                $imported++;
            } else {
                $failed++;
            }
        }

        $updated['players'][] = [
            'name'     => $player['name'] ?? '',
            'romaji'   => $player['romaji'] ?? '',
            'grade'    => $player['grade'] ?? '3年生',
            'number'   => $player['number'] ?? '',
            'previous' => $player['previous'] ?? '',
            'image_id' => $image_id,
            'image'    => $image_url,
        ];
    }

    foreach ($raw['staff'] ?? [] as $member) {
        $image_id  = (int) ($member['image_id'] ?? 0);
        $image_url = $member['image'] ?? '';

        if (!$image_id && $image_url && tokai_is_external_member_image($image_url)) {
            $new_id = tokai_sideload_member_image($image_url, $member['name'] ?? 'スタッフ画像');
            if ($new_id) {
                $image_id = $new_id;
                $image_url = wp_get_attachment_url($new_id) ?: '';
                $imported++;
            } else {
                $failed++;
            }
        }

        $updated['staff'][] = [
            'name'     => $member['name'] ?? '',
            'role'     => $member['role'] ?? '',
            'image_id' => $image_id,
            'image'    => $image_url,
        ];
    }

    tokai_save_members_raw($updated);

    return [
        'imported' => $imported,
        'failed'   => $failed,
    ];
}

add_action('rest_api_init', function () {
    register_rest_route('tokai/v1', '/members', [
        'methods'             => 'GET',
        'callback'            => function () {
            return rest_ensure_response(tokai_get_members_public());
        },
        'permission_callback' => '__return_true',
    ]);
});

add_action('after_switch_theme', function () {
    if (get_option(TOKAI_MEMBERS_OPTION) === false) {
        tokai_import_default_members();
    }
}, 20);
