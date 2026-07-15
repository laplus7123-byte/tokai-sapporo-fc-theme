<?php
/**
 * Instagram 投稿 → WordPress 記事インポート
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_INSTAGRAM_META_URL', '_tokai_instagram_url');
define('TOKAI_INSTAGRAM_OPTION_USERNAME', 'tokai_instagram_username');

function tokai_instagram_article_types() {
    return [
        'auto'        => '自動判定',
        'oshirase'    => 'お知らせ',
        'match-top'   => '試合結果（TOP）',
        'match-2nd'   => '試合結果（2nd）',
        'match-3rd'   => '試合結果（3rd）',
        'match-4th'   => '試合結果（4th）',
    ];
}

function tokai_get_instagram_profile_url() {
    $username = get_option(TOKAI_INSTAGRAM_OPTION_USERNAME, 'tokaisapporo.fc');
    $username = trim($username, '@/');
    return 'https://www.instagram.com/' . rawurlencode($username) . '/';
}

function tokai_normalize_instagram_post_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    if (!preg_match('#instagram\.com/(p|reel|tv)/#i', $url)) {
        return '';
    }

    $url = strtok($url, '?');
    $url = strtok($url, '#');

    return esc_url_raw(trailingslashit($url));
}

function tokai_parse_instagram_caption($caption) {
    $caption = trim((string) $caption);
    $lines   = preg_split('/\r\n|\r|\n/', $caption);
    $flat    = preg_replace('/\s+/u', ' ', $caption);

    $parsed = [
        'article_type' => 'oshirase',
        'team_slug'    => '',
        'team_label'   => '',
        'tag'          => '',
        'is_match'     => false,
        'tournament'   => '',
        'opponent'     => '',
        'home_score'   => '',
        'away_score'   => '',
        'season'       => (string) (int) date('Y'),
        'scorers'      => [],
        'comments'     => [],
        'body_lines'   => [],
    ];

    if ($caption === '') {
        return $parsed;
    }

    if (preg_match('/(\d+)\s*[-ー−‐]\s*(\d+)/u', $flat, $score_match)) {
        $parsed['home_score'] = $score_match[1];
        $parsed['away_score'] = $score_match[2];
        $parsed['is_match']   = true;
    }

    if (preg_match('/(vs|VS|ｖｓ|対)\s*([^\n\r.、。]+)/u', $caption, $opp_match)) {
        $parsed['opponent'] = trim($opp_match[2]);
        $parsed['is_match'] = true;
    }

    if (preg_match('/(TOP|2nd|3rd|4th)\s*(?:VS|vs|ｖｓ|対)/u', $caption, $team_match)) {
        $parsed['team_slug']  = strtolower($team_match[1]);
        $parsed['team_label'] = strtoupper($team_match[1]) === 'TOP' ? 'TOP' : $team_match[1];
        $parsed['article_type'] = 'match-' . strtolower($team_match[1]);
        $parsed['is_match']   = true;
    } elseif (preg_match('/(TOP|2nd|3rd|4th)チーム/u', $caption, $team_match)) {
        $parsed['team_slug']  = strtolower($team_match[1]);
        $parsed['team_label'] = strtoupper($team_match[1]) === 'TOP' ? 'TOP' : $team_match[1];
        $parsed['article_type'] = 'match-' . strtolower($team_match[1]);
        $parsed['is_match']   = true;
    } elseif (preg_match('/\b(TOP|2nd|3rd|4th)\b/u', $caption, $team_match)) {
        $parsed['team_slug']  = strtolower($team_match[1]);
        $parsed['team_label'] = strtoupper($team_match[1]) === 'TOP' ? 'TOP' : $team_match[1];
        $parsed['article_type'] = 'match-' . strtolower($team_match[1]);
    }

  if (preg_match('/(得点者|ゴール)/u', $caption) || preg_match('/(リーグ|大会|トーナメント|ブロック)/u', $caption)) {
        $parsed['is_match'] = true;
    }

    if ($parsed['is_match'] && $parsed['article_type'] === 'oshirase' && $parsed['team_slug'] === '') {
        $parsed['article_type'] = 'match-top';
        $parsed['team_slug']    = 'top';
        $parsed['team_label']   = 'TOP';
    }

    if ($parsed['team_slug']) {
        $parsed['tag'] = $parsed['team_label'] . 'チームの試合結果';
    }

    if (!empty($lines[0])) {
        $parsed['tournament'] = trim($lines[0]);
    }

    if (preg_match('/(\d{1,2})月(\d{1,2})日/u', $caption, $date_match)) {
        $year = (int) date('Y');
        $month = (int) $date_match[1];
        $current_month = (int) date('n');
        if ($month > $current_month + 2) {
            $year--;
        }
        $parsed['season'] = (string) $year;
    }

    $section = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^得点者/u', $line)) {
            $section = 'scorers';
            continue;
        }
        if (preg_match('/選手コメント/u', $line)) {
            $section = 'comments';
            continue;
        }

        if ($section === 'scorers' && preg_match('/^[・•\-]?\s*(.+)$/u', $line, $scorer_match)) {
            $parsed['scorers'][] = trim($scorer_match[1], "・•- \t");
            continue;
        }

        if ($section === 'comments' && preg_match('/^【([^】]+)】\s*(.+)$/u', $line, $comment_match)) {
            $parsed['comments'][] = [
                'name' => trim($comment_match[1]),
                'text' => trim($comment_match[2]),
            ];
            continue;
        }

        if ($section === '' && !preg_match('/^#/', $line)) {
            $parsed['body_lines'][] = $line;
        }
    }

    return $parsed;
}

function tokai_resolve_instagram_article_type($caption, $override = 'auto') {
    if ($override && $override !== 'auto') {
        return $override;
    }

    return tokai_parse_instagram_caption($caption)['article_type'];
}

function tokai_build_instagram_match_title($parsed, $caption) {
    $parts = [];

    if (!empty($parsed['tournament'])) {
        $parts[] = trim($parsed['tournament']);
    }

    $team = $parsed['team_label'] ?: '東海';
    $opponent = $parsed['opponent'] ?: '対戦校';
    $parts[] = sprintf('%s vs %s', $team, $opponent);

    $title = implode('　', array_filter($parts));
    if ($title === '') {
        $title = mb_substr(wp_strip_all_tags($caption), 0, 50);
    }

    if (mb_strlen($title) > 80) {
        $title = mb_substr($title, 0, 80) . '…';
    }

    return $title;
}

function tokai_build_instagram_match_content($parsed, $caption, $url) {
    $content = '';

    if (!empty($parsed['body_lines'])) {
        foreach ($parsed['body_lines'] as $line) {
            $content .= '<p>' . esc_html($line) . '</p>';
        }
    } else {
        $content .= '<p>' . nl2br(esc_html($caption)) . '</p>';
    }

    if (!empty($parsed['scorers'])) {
        $content .= '<h3>得点者</h3><ul>';
        foreach ($parsed['scorers'] as $scorer) {
            $content .= '<li>' . esc_html($scorer) . '</li>';
        }
        $content .= '</ul>';
    }

    if (!empty($parsed['comments'])) {
        $content .= '<h3>選手コメント</h3>';
        foreach ($parsed['comments'] as $comment) {
            $content .= '<p><strong>【' . esc_html($comment['name']) . '】</strong><br>' . esc_html($comment['text']) . '</p>';
        }
    }

    $content .= '<p><a href="' . esc_url($url) . '" target="_blank" rel="noopener">Instagramの投稿を見る</a></p>';

    return $content;
}

function tokai_build_instagram_notice_content($caption, $url) {
    $embed = wp_oembed_get($url);
    $content = '';

    if ($embed) {
        $content .= '<div class="instagram-embed">' . $embed . '</div>';
    }

    if ($caption) {
        $content .= '<p>' . nl2br(esc_html($caption)) . '</p>';
    }

    $content .= '<p><a href="' . esc_url($url) . '" target="_blank" rel="noopener">Instagramで見る</a></p>';

    return $content;
}

function tokai_fetch_instagram_post_meta($url) {
    $response = wp_remote_get($url, [
        'timeout'    => 20,
        'user-agent' => 'Mozilla/5.0 (compatible; TokaiSapporoFC/1.0)',
        'headers'    => [
            'Accept-Language' => 'ja,en;q=0.8',
        ],
    ]);

    $title   = 'Instagram更新';
    $caption = '';
    $image   = '';

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);

        if (preg_match('/property="og:title" content="([^"]+)"/i', $body, $match)) {
            $title = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
        }

        if (preg_match('/property="og:description" content="([^"]+)"/i', $body, $match)) {
            $caption = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
        }

        if (preg_match('/property="og:image" content="([^"]+)"/i', $body, $match)) {
            $image = esc_url_raw(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
        }
    }

    $parsed      = tokai_parse_instagram_caption($caption);
    $article_type = $parsed['article_type'];
    $is_match    = strpos($article_type, 'match-') === 0;

    if ($is_match) {
        $title   = tokai_build_instagram_match_title($parsed, $caption);
        $content = tokai_build_instagram_match_content($parsed, $caption, $url);
    } else {
        $title = wp_strip_all_tags($title);
        $title = preg_replace('/\s*on Instagram:?\s*/iu', ' ', $title);
        $title = trim($title);
        if ($title === '' || stripos($title, 'instagram') !== false) {
            $title = $caption ? mb_substr(wp_strip_all_tags($caption), 0, 40) : 'Instagram更新';
        }
        if (mb_strlen($title) > 60) {
            $title = mb_substr($title, 0, 60) . '…';
        }
        $content = tokai_build_instagram_notice_content($caption, $url);
    }

    return [
        'title'        => $title ?: 'Instagram更新',
        'caption'      => $caption,
        'image'        => $image,
        'content'      => $content,
        'parsed'       => $parsed,
        'article_type' => $article_type,
        'is_match'     => $is_match,
    ];
}

function tokai_get_match_category_ids($team_slug) {
    $ids = [];
    $parent = get_category_by_slug('match-result');
    if ($parent) {
        $ids[] = (int) $parent->term_id;
    }

    $child = get_category_by_slug($team_slug);
    if ($child) {
        $ids[] = (int) $child->term_id;
    }

    return array_values(array_unique(array_filter($ids)));
}

function tokai_apply_instagram_post_meta($post_id, $meta, $article_type) {
    update_post_meta($post_id, TOKAI_INSTAGRAM_META_URL, $meta['url'] ?? '');

    if (strpos($article_type, 'match-') === 0) {
        $team_slug = str_replace('match-', '', $article_type);
        $parsed    = $meta['parsed'] ?? [];

        update_post_meta($post_id, 'news_badge', 'match');
        update_post_meta($post_id, 'match_home_team', '東海');
        update_post_meta($post_id, 'match_opponent', $parsed['opponent'] ?? '');
        update_post_meta($post_id, 'match_home_score', $parsed['home_score'] ?? '');
        update_post_meta($post_id, 'match_away_score', $parsed['away_score'] ?? '');
        update_post_meta($post_id, 'match_season', $parsed['season'] ?? (string) (int) date('Y'));

        $category_ids = tokai_get_match_category_ids($team_slug);
        if (!empty($category_ids)) {
            wp_set_post_categories($post_id, $category_ids);
        }

        if (!empty($parsed['tag'])) {
            wp_set_post_tags($post_id, [$parsed['tag']], false);
        }

        return;
    }

    update_post_meta($post_id, 'news_badge', 'notice');
    $category = get_category_by_slug('oshirase');
    if ($category) {
        wp_set_post_categories($post_id, [(int) $category->term_id]);
    }
}

function tokai_find_post_by_instagram_url($url) {
    $posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => TOKAI_INSTAGRAM_META_URL,
        'meta_value'     => $url,
        'fields'         => 'ids',
    ]);

    return !empty($posts) ? (int) $posts[0] : 0;
}

function tokai_attach_image_from_url($image_url, $post_id, $title) {
    if (!$image_url) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url, 30);
    if (is_wp_error($tmp)) {
        return 0;
    }

    $file_array = [
        'name'     => sanitize_file_name(($title ?: 'instagram') . '.jpg'),
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file_array, $post_id, $title);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }

    return (int) $attachment_id;
}

function tokai_import_instagram_post($url, $args = []) {
    $status       = $args['status'] ?? 'publish';
    $article_type = $args['article_type'] ?? 'auto';

    $url = tokai_normalize_instagram_post_url($url);
    if ($url === '') {
        return new WP_Error('invalid_url', 'Instagram投稿URLが正しくありません。');
    }

    $existing_id = tokai_find_post_by_instagram_url($url);
    if ($existing_id) {
        return [
            'status'  => 'skipped',
            'post_id' => $existing_id,
            'message' => 'すでに取り込み済みです。',
        ];
    }

    $meta = tokai_fetch_instagram_post_meta($url);
    $meta['url'] = $url;
    $resolved_type = tokai_resolve_instagram_article_type($meta['caption'], $article_type);

    $post_id = wp_insert_post([
        'post_title'   => $meta['title'],
        'post_content' => $meta['content'],
        'post_status'  => $status === 'draft' ? 'draft' : 'publish',
        'post_type'    => 'post',
    ], true);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    tokai_apply_instagram_post_meta($post_id, $meta, $resolved_type);
    update_post_meta($post_id, TOKAI_INSTAGRAM_META_URL, $url);

    if (!empty($meta['image'])) {
        $attachment_id = tokai_attach_image_from_url($meta['image'], $post_id, $meta['title']);
        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    $type_label = tokai_instagram_article_types()[$resolved_type] ?? $resolved_type;
    $tag_note   = !empty($meta['parsed']['tag']) ? '（タグ: ' . $meta['parsed']['tag'] . '）' : '';

    return [
        'status'       => 'created',
        'post_id'      => (int) $post_id,
        'article_type' => $resolved_type,
        'message'      => $type_label . ' として記事を作成しました。' . $tag_note,
    ];
}

function tokai_import_instagram_posts_bulk($urls, $args = []) {
    $results = [];

    foreach ($urls as $url) {
        $result = tokai_import_instagram_post($url, $args);
        $results[] = is_wp_error($result)
            ? ['status' => 'error', 'url' => $url, 'message' => $result->get_error_message()]
            : array_merge($result, ['url' => $url]);
    }

    return $results;
}
