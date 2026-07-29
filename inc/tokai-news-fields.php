<?php
/**
 * Plugin Name: Tokai News REST Fields
 * Description: サッカー部サイト用のニュースカスタムフィールドを REST API に公開
 * Version: 1.0.0
 *
 * 使い方: このファイルを wp-content/mu-plugins/ に配置するか、
 *         テーマの functions.php に require してください。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * カスタムフィールド定義
 */
function tokai_news_meta_fields() {
    return [
        'news_badge'        => 'string',  // match | notice
        'match_season'      => 'string',  // 試合のシーズン（西暦）
        'match_home_team'   => 'string',
        'match_opponent'    => 'string',
        'match_home_score'  => 'string',
        'match_away_score'  => 'string',
        'match_members'     => 'string',  // 背番号|名前|ポジション を改行区切り
    ];
}

/**
 * REST API にメタフィールドを登録
 */
add_action('init', function () {
    foreach (tokai_news_meta_fields() as $key => $type) {
        $sanitize = $key === 'match_members' ? 'sanitize_textarea_field' : 'sanitize_text_field';
        register_post_meta('post', $key, [
            'type'              => $type,
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => $sanitize,
            'auth_callback'     => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

/**
 * 管理画面にカスタムフィールド入力欄を追加
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'tokai_news_fields',
        'ニュース詳細（試合結果・メンバー）',
        'tokai_news_meta_box_render',
        'post',
        'normal',
        'high'
    );
});

function tokai_news_meta_box_render($post) {
    wp_nonce_field('tokai_news_meta_save', 'tokai_news_meta_nonce');

    $badge       = get_post_meta($post->ID, 'news_badge', true);
    $season      = get_post_meta($post->ID, 'match_season', true);
    $home_team   = get_post_meta($post->ID, 'match_home_team', true);
    $opponent    = get_post_meta($post->ID, 'match_opponent', true);
    $home_score  = get_post_meta($post->ID, 'match_home_score', true);
    $away_score  = get_post_meta($post->ID, 'match_away_score', true);
    $members     = get_post_meta($post->ID, 'match_members', true);

    $badge_options = ['' => '自動', 'match' => '試合結果', 'notice' => 'お知らせ'];

    $current_year = (int) date('Y');
    $season_options = ['' => '（未設定 / 投稿日を使用）'];
    for ($y = $current_year + 1; $y >= 2026; $y--) {
        $season_options[(string) $y] = $y . 'シーズン';
    }
    ?>
    <style>
        .tokai-news-box h4 { margin: 20px 0 8px; font-size: 14px; border-left: 4px solid #FFD100; padding-left: 8px; }
        .tokai-news-box h4:first-child { margin-top: 4px; }
        .tokai-news-score { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
        .tokai-news-score .team { display: flex; flex-direction: column; gap: 4px; }
        .tokai-news-score .team label { font-size: 12px; color: #666; }
        .tokai-news-score input[type=text].team-name { width: 160px; }
        .tokai-news-score input[type=text].team-score { width: 70px; font-size: 20px; text-align: center; font-weight: bold; }
        .tokai-news-score .vs { padding: 0 6px 6px; font-weight: bold; color: #999; }
        .tokai-news-box .desc { color: #666; font-size: 12px; margin: 6px 0 0; }
    </style>
    <div class="tokai-news-box">
        <h4>表示バッジ</h4>
        <select name="news_badge" id="news_badge">
            <?php foreach ($badge_options as $val => $label) : ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($badge, $val); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="desc">「試合結果」を選ぶと、記事上部にスコアが大きく表示されます。</p>

        <h4>試合のシーズン</h4>
        <select name="match_season" id="match_season">
            <?php foreach ($season_options as $val => $label) : ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($season, $val); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="desc">試合が行われたシーズン（西暦）です。設定するとサイトの「年別」絞り込みに反映されます。未設定の場合は投稿日の年が使われます。</p>

        <h4>スコア（試合結果の場合）</h4>
        <div class="tokai-news-score">
            <div class="team">
                <label for="match_home_team">自校名</label>
                <input type="text" class="team-name" name="match_home_team" id="match_home_team" value="<?php echo esc_attr($home_team); ?>" placeholder="東海">
            </div>
            <div class="team">
                <label for="match_home_score">得点</label>
                <input type="text" class="team-score" name="match_home_score" id="match_home_score" value="<?php echo esc_attr($home_score); ?>" placeholder="0">
            </div>
            <span class="vs">VS</span>
            <div class="team">
                <label for="match_away_score">得点</label>
                <input type="text" class="team-score" name="match_away_score" id="match_away_score" value="<?php echo esc_attr($away_score); ?>" placeholder="0">
            </div>
            <div class="team">
                <label for="match_opponent">対戦校</label>
                <input type="text" class="team-name" name="match_opponent" id="match_opponent" value="<?php echo esc_attr($opponent); ?>" placeholder="○○高校">
            </div>
        </div>
        <p class="desc">試合結果でない記事（お知らせ等）は空欄のままでOK。空欄ならスコアは表示されません。</p>

        <h4>出場メンバー（任意）</h4>
        <textarea name="match_members" id="match_members" rows="8" class="large-text" placeholder="10|山田太郎|GK&#10;11|佐藤花子|DF"><?php echo esc_textarea($members); ?></textarea>
        <p class="desc">1行に1人。「背番号|名前|ポジション」の形式で入力してください（区切りは半角の縦棒 <code>|</code>）。</p>

        <p class="desc">記事本文は上のエディターに入力します。カテゴリーは右側の「カテゴリー」から選択してください（親：<strong>お知らせ</strong> / <strong>試合結果</strong>、試合結果の子：<strong>TOP / 2nd / 3rd / 4th</strong>）。アイキャッチ画像は一覧のサムネイル・詳細のメイン画像になります。</p>
    </div>
    <?php
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['tokai_news_meta_nonce']) || !wp_verify_nonce($_POST['tokai_news_meta_nonce'], 'tokai_news_meta_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (array_keys(tokai_news_meta_fields()) as $key) {
        if (!isset($_POST[$key])) {
            continue;
        }
        $value = $_POST[$key];
        if ($key === 'match_members') {
            $value = sanitize_textarea_field($value);
        } else {
            $value = sanitize_text_field($value);
        }
        update_post_meta($post_id, $key, $value);
    }
});

/**
 * カテゴリーを作成・階層化（存在すれば親を修正）
 */
function tokai_ensure_category($name, $slug, $parent_id) {
    $existing = get_term_by('slug', $slug, 'category');
    if ($existing) {
        if ((int) $existing->parent !== (int) $parent_id) {
            wp_update_term($existing->term_id, 'category', ['parent' => (int) $parent_id]);
        }
        return (int) $existing->term_id;
    }
    $res = wp_insert_term($name, 'category', ['slug' => $slug, 'parent' => (int) $parent_id]);
    return is_wp_error($res) ? 0 : (int) $res['term_id'];
}

/**
 * 初回有効化時にカテゴリー階層を自動作成
 *   お知らせ（親）
 *   試合結果（親）
 *     ├ TOP / 2nd / 3rd / 4th（子）
 */
add_action('admin_init', function () {
    $version = '2';
    if (get_option('tokai_news_categories_created') === $version) {
        return;
    }

    // 親カテゴリー
    tokai_ensure_category('お知らせ', 'oshirase', 0);
    $match_parent = tokai_ensure_category('試合結果', 'match-result', 0);

    // 試合結果の子カテゴリー
    $children = [
        'top' => 'TOP',
        '2nd' => '2nd',
        '3rd' => '3rd',
        '4th' => '4th',
    ];
    foreach ($children as $slug => $name) {
        tokai_ensure_category($name, $slug, $match_parent);
    }

    update_option('tokai_news_categories_created', $version);
});
