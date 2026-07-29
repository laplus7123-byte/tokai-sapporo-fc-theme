<?php
/**
 * トップFV（ヒーロー）画像 — データ取得・既定値
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_FV_OPTION', 'tokai_fv_images');
define('TOKAI_FV_MAX_SLIDES', 5);

/**
 * テーマ同梱の初期FV画像（PC）。SP未設定時はPCを使用。
 */
function tokai_fv_default_slides() {
    $files = [
        'p_1688184295.jpeg',
        'p_1688184347.jpeg',
        'p_1688184388.jpeg',
        'p_1703486976.jpg',
    ];

    $slides = [];
    foreach ($files as $file) {
        $slides[] = [
            'pc' => [
                'id'  => 0,
                'url' => tokai_asset($file),
            ],
            'sp' => [
                'id'  => 0,
                'url' => '',
            ],
        ];
    }

    return $slides;
}

function tokai_fv_normalize_slot($slot) {
    if (!is_array($slot)) {
        $slot = [];
    }

    return [
        'id'  => absint($slot['id'] ?? 0),
        'url' => esc_url_raw($slot['url'] ?? ''),
    ];
}

function tokai_fv_normalize_slides($raw) {
    $slides = [];
    if (!is_array($raw)) {
        $raw = [];
    }

    // 旧形式・連想配列にも対応
    $list = array_values($raw);
    for ($i = 0; $i < TOKAI_FV_MAX_SLIDES; $i++) {
        $row = $list[$i] ?? [];
        if (!is_array($row)) {
            $row = [];
        }

        // フラット形式 (pc_id / sp_id) も吸収
        if (isset($row['pc_id']) || isset($row['pc_url'])) {
            $row = [
                'pc' => ['id' => $row['pc_id'] ?? 0, 'url' => $row['pc_url'] ?? ''],
                'sp' => ['id' => $row['sp_id'] ?? 0, 'url' => $row['sp_url'] ?? ''],
            ];
        }

        $pc = tokai_fv_normalize_slot($row['pc'] ?? []);
        $sp = tokai_fv_normalize_slot($row['sp'] ?? []);

        // 添付IDがあれば最新URLに更新
        if ($pc['id'] > 0) {
            $resolved = wp_get_attachment_image_url($pc['id'], 'full');
            if ($resolved) {
                $pc['url'] = $resolved;
            }
        }
        if ($sp['id'] > 0) {
            $resolved = wp_get_attachment_image_url($sp['id'], 'full');
            if ($resolved) {
                $sp['url'] = $resolved;
            }
        }

        $slides[] = [
            'pc' => $pc,
            'sp' => $sp,
        ];
    }

    return $slides;
}

function tokai_fv_get_slides_raw() {
    $stored = get_option(TOKAI_FV_OPTION, null);
    if (!is_array($stored) || empty($stored)) {
        return tokai_fv_default_slides();
    }

    return tokai_fv_normalize_slides($stored);
}

/**
 * 表示用スライド。PC画像がある枠のみ。SPが空ならPCを使う。
 *
 * @return array<int, array{pc:string,sp:string}>
 */
function tokai_fv_get_display_slides() {
    $out = [];
    foreach (tokai_fv_get_slides_raw() as $slide) {
        $pc = trim((string) ($slide['pc']['url'] ?? ''));
        if ($pc === '') {
            continue;
        }
        $sp = trim((string) ($slide['sp']['url'] ?? ''));
        $out[] = [
            'pc' => $pc,
            'sp' => $sp !== '' ? $sp : $pc,
        ];
    }

    if (empty($out)) {
        foreach (tokai_fv_default_slides() as $slide) {
            $pc = $slide['pc']['url'];
            $out[] = [
                'pc' => $pc,
                'sp' => $pc,
            ];
        }
    }

    return $out;
}

function tokai_fv_save_slides($slides) {
    $normalized = tokai_fv_normalize_slides($slides);
    update_option(TOKAI_FV_OPTION, $normalized, false);
    return $normalized;
}

/**
 * フロントのヒーロースライダーHTMLを出力
 */
function tokai_render_hero_slider() {
    $slides = tokai_fv_get_display_slides();
    ?>
  <div class="hero__slider">
    <?php foreach ($slides as $index => $slide) : ?>
      <div class="hero__slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
        <picture>
          <source media="(max-width: 768px)" srcset="<?php echo esc_url($slide['sp']); ?>">
          <img src="<?php echo esc_url($slide['pc']); ?>" alt="チーム写真" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
        </picture>
      </div>
    <?php endforeach; ?>
  </div>
    <?php
}
