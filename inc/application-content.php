<?php
/**
 * Application ページ — Feature 文言・画像
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_APP_FEATURES_OPTION', 'tokai_app_features');

function tokai_app_features_defaults() {
    return [
        [
            'title' => '試合速報',
            'text'  => 'SNSよりも早く正確に得点者情報や試合結果が更新されます！',
            'image' => ['id' => 0, 'url' => ''],
        ],
        [
            'title' => '選手ブログを閲覧可能',
            'text'  => 'アプリ限定で選手のブログを更新しています！ここでしか見られないので試合では見られない裏の顔をぜひ見てください！',
            'image' => ['id' => 0, 'url' => ''],
        ],
        [
            'title' => 'メンバーシップ制度',
            'text'  => 'メンバーシップのランクに応じてチームグッズを手に入れることができます！ぜひ、TOKAIグッズを手に入れてください！',
            'image' => ['id' => 0, 'url' => ''],
        ],
    ];
}

function tokai_app_overview_lead_default() {
    return '試合速報やお知らせはもちろん、SNSでは公開されていない選手ブログや写真なども更新しています！';
}

function tokai_app_normalize_feature($row, $fallback = null) {
    $fallback = is_array($fallback) ? $fallback : [
        'title' => '',
        'text'  => '',
        'image' => ['id' => 0, 'url' => ''],
    ];

    if (!is_array($row)) {
        $row = [];
    }

    $image = $row['image'] ?? [];
    if (!is_array($image)) {
        $image = [];
    }

    $id  = absint($image['id'] ?? ($row['image_id'] ?? 0));
    $url = esc_url_raw($image['url'] ?? ($row['image_url'] ?? ''));

    if ($id > 0) {
        $resolved = wp_get_attachment_image_url($id, 'large');
        if ($resolved) {
            $url = $resolved;
        }
    }

    $title = sanitize_text_field($row['title'] ?? '');
    $text  = sanitize_textarea_field($row['text'] ?? '');

    if ($title === '') {
        $title = $fallback['title'] ?? '';
    }
    if ($text === '') {
        $text = $fallback['text'] ?? '';
    }

    return [
        'title' => $title,
        'text'  => $text,
        'image' => [
            'id'  => $id,
            'url' => $url,
        ],
    ];
}

function tokai_app_get_features() {
    $defaults = tokai_app_features_defaults();
    $stored   = get_option(TOKAI_APP_FEATURES_OPTION, null);
    $list     = is_array($stored) ? array_values($stored) : [];

    $features = [];
    for ($i = 0; $i < 3; $i++) {
        $features[] = tokai_app_normalize_feature($list[$i] ?? [], $defaults[$i]);
    }

    return $features;
}

function tokai_app_save_features($features) {
    $defaults = tokai_app_features_defaults();
    $out      = [];
    $list     = is_array($features) ? array_values($features) : [];

    for ($i = 0; $i < 3; $i++) {
        $out[] = tokai_app_normalize_feature($list[$i] ?? [], $defaults[$i]);
    }

    update_option(TOKAI_APP_FEATURES_OPTION, $out, false);
    return $out;
}

function tokai_app_get_overview_lead() {
    $lead = trim((string) get_option('tokai_app_overview_lead', ''));
    return $lead !== '' ? $lead : tokai_app_overview_lead_default();
}

function tokai_app_save_overview_lead($lead) {
    $lead = sanitize_textarea_field($lead);
    update_option('tokai_app_overview_lead', $lead, false);
    return $lead;
}
