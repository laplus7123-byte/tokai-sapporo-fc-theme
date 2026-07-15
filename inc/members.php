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

function tokai_render_member_card($member, $subtitle_key = '') {
    $subtitle = $subtitle_key ? ($member[$subtitle_key] ?? '') : '';
    $image    = tokai_resolve_member_image_url($member);
    $name     = $member['name'] ?? '';

    ob_start();
    ?>
    <div class="member-card">
      <div class="member-card__photo">
        <?php if ($image) : ?>
          <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else : ?>
          <span class="member-card__placeholder" aria-hidden="true"></span>
        <?php endif; ?>
      </div>
      <p class="member-card__name"><?php echo esc_html($name); ?></p>
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

        $players[] = [
            'name'     => $name,
            'grade'    => $grade,
            'image_id' => absint($player['image_id'] ?? 0),
            'image'    => esc_url_raw($player['image'] ?? ''),
        ];
    }

    $staff = [];
    foreach ($data['staff'] ?? [] as $member) {
        $name = sanitize_text_field($member['name'] ?? '');
        if ($name === '') {
            continue;
        }

        $staff[] = [
            'name'     => $name,
            'role'     => sanitize_text_field($member['role'] ?? ''),
            'image_id' => absint($member['image_id'] ?? 0),
            'image'    => esc_url_raw($member['image'] ?? ''),
        ];
    }

    return [
        'players' => $players,
        'staff'   => $staff,
    ];
}

function tokai_get_members_public() {
    $raw = tokai_get_members_raw();

    $players = array_map(function ($player) {
        return [
            'name'  => $player['name'],
            'grade' => $player['grade'],
            'image' => tokai_resolve_member_image_url($player),
        ];
    }, $raw['players'] ?? []);

    $staff = array_map(function ($member) {
        return [
            'name'  => $member['name'],
            'role'  => $member['role'] ?? '',
            'image' => tokai_resolve_member_image_url($member),
        ];
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
            'grade'    => $player['grade'] ?? '3年生',
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
