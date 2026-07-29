<?php
/**
 * 試合ギャラリー（投稿メタ）— 試合記事ごとに画像をまとめる
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_MATCH_GALLERY_META', 'match_gallery');

/**
 * 添付ID配列を正規化
 */
function tokai_normalize_gallery_ids($value) {
    if ($value === null || $value === false) {
        return [];
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $value = $decoded;
        } else {
            $value = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
    }

    // WP がシリアライズ配列を返す場合
    if (is_object($value)) {
        $value = (array) $value;
    }

    if (!is_array($value)) {
        return [];
    }

    $ids = [];
    foreach ($value as $id) {
        $id = absint($id);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function tokai_get_match_gallery_ids($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return [];
    }
    return tokai_normalize_gallery_ids(get_post_meta($post_id, TOKAI_MATCH_GALLERY_META, true));
}

/**
 * ギャラリー画像情報（URL付き）
 */
function tokai_get_match_gallery_items($post_id = null) {
    $items = [];
    foreach (tokai_get_match_gallery_ids($post_id) as $id) {
        $full = wp_get_attachment_image_url($id, 'large');
        $thumb = wp_get_attachment_image_url($id, 'medium_large') ?: $full;
        if (!$full) {
            continue;
        }
        $items[] = [
            'id'    => $id,
            'full'  => $full,
            'thumb' => $thumb,
            'alt'   => get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id),
        ];
    }
    return $items;
}

add_action('init', function () {
    register_post_meta('post', TOKAI_MATCH_GALLERY_META, [
        'type'              => 'array',
        'single'            => true,
        'show_in_rest'      => [
            'schema' => [
                'type'  => 'array',
                'items' => ['type' => 'integer'],
            ],
        ],
        'default'           => [],
        'sanitize_callback' => 'tokai_normalize_gallery_ids',
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);
});

/**
 * クラシックエディタのみメタボックス表示
 * （ブロックエディタではサイドパネルで保存。両方あると空上書きが起きる）
 */
add_action('add_meta_boxes', function ($post_type, $post) {
    if ($post_type !== 'post') {
        return;
    }
    if ($post && function_exists('use_block_editor_for_post') && use_block_editor_for_post($post)) {
        return;
    }

    add_meta_box(
        'tokai_match_gallery',
        '試合ギャラリー（複数画像）',
        'tokai_match_gallery_meta_box_render',
        'post',
        'normal',
        'high'
    );
}, 10, 2);

function tokai_match_gallery_meta_box_render($post) {
    wp_nonce_field('tokai_match_gallery_save', 'tokai_match_gallery_nonce');
    $ids = tokai_get_match_gallery_ids($post->ID);
    ?>
    <div class="tokai-gallery-box" id="tokai-gallery-box">
      <ol class="tokai-gallery-box__steps">
        <li>「画像を追加」を押す</li>
        <li>メディアから写真を<strong>複数選択</strong>（または新規アップロード）</li>
        <li>「追加する」→ 記事を更新／公開</li>
      </ol>
      <p class="description">ここに入れた写真が Gallery の試合アルバムと、記事詳細のギャラリーに表示されます。（本文中の画像とは別です）</p>
      <input type="hidden" name="match_gallery_ids" id="tokai-gallery-ids" value="<?php echo esc_attr(implode(',', $ids)); ?>">
      <input type="hidden" name="tokai_gallery_touched" id="tokai-gallery-touched" value="0">
      <ul class="tokai-gallery-box__list" id="tokai-gallery-list">
        <?php foreach ($ids as $id) :
            $thumb = wp_get_attachment_image_url($id, 'thumbnail');
            if (!$thumb) {
                continue;
            }
            ?>
          <li class="tokai-gallery-box__item" data-id="<?php echo esc_attr((string) $id); ?>">
            <img src="<?php echo esc_url($thumb); ?>" alt="">
            <button type="button" class="tokai-gallery-box__remove" aria-label="削除">&times;</button>
          </li>
        <?php endforeach; ?>
      </ul>
      <p>
        <button type="button" class="button button-primary" id="tokai-gallery-add">画像を追加（複数可）</button>
        <button type="button" class="button-link" id="tokai-gallery-clear">すべて削除</button>
      </p>
    </div>
    <?php
}

add_action('save_post_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        // ブロックエディタの REST 保存は register_post_meta に任せる
        return;
    }
    if (!isset($_POST['tokai_match_gallery_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokai_match_gallery_nonce'])), 'tokai_match_gallery_save')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    // ユーザーがギャラリーUIを触っていない場合は上書きしない
    if (!isset($_POST['tokai_gallery_touched']) || (string) wp_unslash($_POST['tokai_gallery_touched']) !== '1') {
        return;
    }

    $raw = isset($_POST['match_gallery_ids']) ? wp_unslash($_POST['match_gallery_ids']) : '';
    $ids = tokai_normalize_gallery_ids($raw);
    update_post_meta($post_id, TOKAI_MATCH_GALLERY_META, $ids);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'post') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style(
        'tokai-admin-gallery',
        tokai_uri('assets/admin-gallery.css'),
        [],
        TOKAI_THEME_VERSION
    );

    $is_block = method_exists($screen, 'is_block_editor') && $screen->is_block_editor();
    if ($is_block) {
        wp_enqueue_script(
            'tokai-admin-gallery-block',
            tokai_uri('assets/admin-gallery-block.js'),
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-core-data'],
            TOKAI_THEME_VERSION,
            true
        );
    } else {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script(
            'tokai-admin-gallery',
            tokai_uri('assets/admin-gallery.js'),
            ['jquery', 'jquery-ui-sortable'],
            TOKAI_THEME_VERSION,
            true
        );
    }
});

/**
 * 試合アルバム表示用メタ
 */
function tokai_get_match_album_meta($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $season = get_post_meta($post_id, 'match_season', true);
    $year = $season !== '' ? (string) $season : get_the_date('Y', $post_id);
    $home = get_post_meta($post_id, 'match_home_team', true) ?: '東海';
    $away = get_post_meta($post_id, 'match_opponent', true);
    $hs = get_post_meta($post_id, 'match_home_score', true);
    $as = get_post_meta($post_id, 'match_away_score', true);
    $score = ($hs !== '' && $as !== '') ? ($hs . ' - ' . $as) : '';
    $score_line = trim($home . ($away ? ' vs ' . $away : '') . ($score ? '  ' . $score : ''));

    $team = '';
    foreach (get_the_category($post_id) as $cat) {
        if (in_array($cat->slug, ['top', '2nd', '3rd', '4th'], true)) {
            $team = $cat->slug === 'top' ? 'TOP' : strtoupper($cat->name);
            break;
        }
    }

    return [
        'year'       => $year,
        'team'       => $team,
        'score_line' => $score_line,
    ];
}

/**
 * 投稿のシーズン年を取得
 */
function tokai_get_post_season_year($post_id) {
    $season = get_post_meta($post_id, 'match_season', true);
    if ($season !== '' && $season !== null) {
        return (int) $season;
    }
    return (int) get_the_date('Y', $post_id);
}

/**
 * ギャラリー付き投稿一覧（2026シーズン以降）
 *
 * @param array $args {
 *   @type int    $year シーズン年（0で全件）
 *   @type string $team top|2nd|3rd|4th|''
 * }
 */
function tokai_get_gallery_posts($args = []) {
    $min_year = 2026;
    $year = isset($args['year']) ? (int) $args['year'] : 0;
    $team = isset($args['team']) ? sanitize_title((string) $args['team']) : '';
    unset($args['year'], $args['team']);

    // meta_query の EXISTS だけだと保存形式によって取りこぼすため、広めに取って PHP で判定
    $query_args = array_merge([
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => 200,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
    ], $args);

    if (in_array($team, ['top', '2nd', '3rd', '4th'], true)) {
        $query_args['category_name'] = $team;
    }

    $query = new WP_Query($query_args);

    $posts = [];
    foreach ($query->posts as $post) {
        $ids = tokai_get_match_gallery_ids($post->ID);
        if (empty($ids)) {
            continue;
        }

        $post_year = tokai_get_post_season_year($post->ID);
        if ($post_year < $min_year) {
            continue;
        }
        if ($year > 0 && $post_year !== $year) {
            continue;
        }

        $posts[] = $post;
    }

    return $posts;
}

/**
 * 記事詳細のギャラリーHTML
 */
function tokai_render_match_gallery($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $items = tokai_get_match_gallery_items($post_id);
    if (empty($items)) {
        return '';
    }

    $album_url = tokai_page_url('gallery', ['match' => $post_id]);

    ob_start();
    ?>
    <section class="match-gallery" aria-label="試合ギャラリー">
      <div class="match-gallery__head">
        <h2 class="match-gallery__title">Gallery</h2>
        <a class="match-gallery__album-link" href="<?php echo esc_url($album_url); ?>">アルバムで見る</a>
      </div>
      <div class="match-gallery__grid">
        <?php foreach ($items as $item) : ?>
          <a class="match-gallery__item" href="<?php echo esc_url($item['full']); ?>" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr($item['alt']); ?>" loading="lazy">
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
