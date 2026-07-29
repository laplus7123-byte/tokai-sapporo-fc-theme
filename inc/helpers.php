<?php
/**
 * Theme helper functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

function tokai_uri($path = '') {
    return trailingslashit(get_template_directory_uri()) . ltrim($path, '/');
}

function tokai_asset($file) {
    return tokai_uri('assets/' . ltrim($file, '/'));
}

function tokai_page_url($slug, $query_args = []) {
    $page = get_page_by_path($slug);
    $url  = $page ? get_permalink($page) : home_url('/' . trim($slug, '/') . '/');

    if (!empty($query_args)) {
        $url = add_query_arg($query_args, $url);
    }

    return $url;
}

function tokai_instagram_icon() {
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
}

function tokai_render_instagram_feed() {
    $shortcode = trim((string) get_theme_mod('tokai_instagram_shortcode', '[instagram-feed feed=1]'));

    if ($shortcode === '') {
        echo '<p class="instagram__empty">Instagram プラグインのショートコードを「外観 → カスタマイズ → サッカー部サイト設定」で設定してください。</p>';
        return;
    }

    echo '<div class="instagram__plugin">';
    echo do_shortcode($shortcode);
    echo '</div>';
}

/**
 * 年度ごとの News ナビ構成。
 * チーム数は学年で変わるため、年単位で蓄積する（新しい年を先頭に追加）。
 *
 * @return array<int, list<array{slug:string,label:string}>>
 */
function tokai_news_season_nav_config() {
    $config = [
        2026 => [
            ['slug' => 'oshirase', 'label' => 'お知らせ'],
            ['slug' => 'top', 'label' => 'TOP'],
            ['slug' => '2nd', 'label' => '2nd'],
            ['slug' => '3rd', 'label' => '3rd'],
            ['slug' => '4th', 'label' => '4th'],
        ],
    ];

    /**
     * 年度ナビ構成をフィルタで拡張可能
     */
    return apply_filters('tokai_news_season_nav_config', $config);
}

/**
 * News ナビに登録されている年度一覧（新しい順）
 */
function tokai_news_years() {
    $years = array_map('intval', array_keys(tokai_news_season_nav_config()));
    rsort($years, SORT_NUMERIC);
    return $years ?: [2026];
}

/**
 * いま表示する「当年」シーズン
 */
function tokai_news_current_season_year() {
    $years = tokai_news_years();
    $now = (int) date('Y');
    if (in_array($now, $years, true)) {
        return $now;
    }
    return (int) $years[0];
}

/**
 * 指定年のナビ項目
 *
 * @return list<array{slug:string,label:string}>
 */
function tokai_news_season_nav_items($year) {
    $config = tokai_news_season_nav_config();
    $year = (int) $year;
    return isset($config[$year]) && is_array($config[$year]) ? $config[$year] : [];
}

/**
 * News サブメニュー（当年常時表示＋過去年アコーディオン）を出力
 */
function tokai_render_news_submenu() {
    $current_year = tokai_news_current_season_year();
    $years = tokai_news_years();
    $current_items = tokai_news_season_nav_items($current_year);
    $past_years = array_values(array_filter($years, static function ($y) use ($current_year) {
        return (int) $y < $current_year;
    }));
    ?>
    <div class="header__submenu">
      <p class="header__submenu-year-label"><?php echo esc_html($current_year); ?>年</p>
      <?php foreach ($current_items as $item) : ?>
        <a href="<?php echo esc_url(tokai_page_url('news', ['year' => $current_year, 'cat' => $item['slug']])); ?>">
          <?php echo esc_html($item['label']); ?>
        </a>
      <?php endforeach; ?>

      <?php if (!empty($past_years)) : ?>
        <span class="header__submenu-divider" aria-hidden="true"></span>
        <?php foreach ($past_years as $year) :
            $items = tokai_news_season_nav_items($year);
            if (empty($items)) {
                continue;
            }
            $panel_id = 'news-year-panel-' . $year;
            ?>
          <div class="header__submenu-year-group">
            <button
              type="button"
              class="header__submenu-year"
              aria-expanded="false"
              aria-controls="<?php echo esc_attr($panel_id); ?>"
            >
              <span><?php echo esc_html($year); ?></span>
              <span class="header__submenu-year-mark" aria-hidden="true">＞</span>
            </button>
            <div class="header__submenu-year-panel" id="<?php echo esc_attr($panel_id); ?>" hidden>
              <?php foreach ($items as $item) : ?>
                <a href="<?php echo esc_url(tokai_page_url('news', ['year' => $year, 'cat' => $item['slug']])); ?>">
                  <?php echo esc_html($item['label']); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php
}

/**
 * スポンサーロゴ一覧（記事下などで共通利用）
 */
function tokai_sponsor_logos() {
    return [
        ['name' => '上田商会グループ（UEDA GROUP）', 'logo' => 'p_1728962062.png', 'url' => 'https://ueda-gr.jp/'],
        ['name' => 'CUBERU（クベル）',                 'logo' => 'p_1728962005.png', 'url' => 'https://cuberu.jp/'],
        ['name' => 'カーコレクション',                  'logo' => 'p_1728962080.png', 'url' => 'https://carcolle.info/index.html'],
        ['name' => 'カリースタンド',                    'logo' => 'p_1728962145.png', 'url' => 'https://currystand-sapporo.com/'],
        ['name' => '株式会社ラリーホームズ',            'logo' => 'p_1728962207.png', 'url' => 'https://rally-homes-info.my.canva.site/'],
        ['name' => '標津羊羹本舗',                       'logo' => 'p_1728962272.png', 'url' => 'https://shibetsuyoukan.jp/'],
        ['name' => '株式会社ソルトワークス（Saltworks）', 'logo' => 'p_1728962318.png', 'url' => 'https://saltworks.jp/'],
        ['name' => '株式会社La plus',                   'logo' => 'sponsor-laplus.png', 'url' => 'https://laplusgroup.com/'],
    ];
}

/**
 * アプリ誘導CTA（スポンサー帯の上などで共通利用）
 */
function tokai_render_app_cta() {
    ?>
    <aside class="app-cta" aria-label="公式アプリ">
      <p class="app-cta__micro">＼無料アプリで速報をチェック／</p>
      <a href="<?php echo esc_url(tokai_page_url('application')); ?>" class="app-cta__button">
        TOKAIアプリの詳細はこちら<span class="app-cta__arrow" aria-hidden="true">→</span>
      </a>
    </aside>
    <?php
}

/**
 * 記事下などに表示するスポンサーロゴ帯
 */
function tokai_render_sponsor_strip() {
    $sponsors = tokai_sponsor_logos();
    if (empty($sponsors)) {
        return;
    }
    ?>
    <?php tokai_render_app_cta(); ?>
    <aside class="article-sponsors" aria-label="スポンサー">
      <p class="article-sponsors__heading">
        <span class="article-sponsors__en">SPONSORS</span>
        <span class="article-sponsors__ja">オフィシャルスポンサー</span>
      </p>
      <div class="article-sponsors__grid">
        <?php foreach ($sponsors as $sponsor) : ?>
          <a href="<?php echo esc_url($sponsor['url']); ?>" target="_blank" rel="noopener" class="article-sponsors__item">
            <img src="<?php echo esc_url(tokai_asset($sponsor['logo'])); ?>" alt="<?php echo esc_attr($sponsor['name']); ?>" loading="lazy">
          </a>
        <?php endforeach; ?>
      </div>
      <p class="article-sponsors__note">
        <a href="<?php echo esc_url(tokai_page_url('sponsor')); ?>">スポンサー一覧を見る</a>
      </p>
    </aside>
    <?php
}

function tokai_format_post_date($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $date    = get_post_time('U', true, $post_id);
    return $date ? date_i18n('Y.m.d', $date) : '';
}

function tokai_get_post_category_slug($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $slugs   = ['oshirase', 'top', '2nd', '3rd', '4th'];
    $cats    = get_the_category($post_id);

    foreach ($cats as $cat) {
        if (in_array($cat->slug, $slugs, true)) {
            return $cat->slug;
        }
    }

    return !empty($cats) ? $cats[0]->slug : 'oshirase';
}

function tokai_get_category_label($slug) {
    $labels = [
        'oshirase' => 'お知らせ',
        'top'      => 'TOP',
        '2nd'      => '2nd',
        '3rd'      => '3rd',
        '4th'      => '4th',
    ];

    return $labels[$slug] ?? $slug;
}

function tokai_parse_members($raw) {
    if (!$raw) {
        return [];
    }

    $members = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($raw)) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $members[] = [
            'number'   => $parts[0] ?? '',
            'name'     => $parts[1] ?? $line,
            'position' => $parts[2] ?? '',
        ];
    }

    return $members;
}

function tokai_render_match_score($post_id = null) {
    $post_id    = $post_id ?: get_the_ID();
    $home       = get_post_meta($post_id, 'match_home_team', true) ?: '東海';
    $away       = get_post_meta($post_id, 'match_opponent', true);
    $home_score = get_post_meta($post_id, 'match_home_score', true);
    $away_score = get_post_meta($post_id, 'match_away_score', true);

    if ($away === '' && $home_score === '' && $away_score === '') {
        return '';
    }

    ob_start();
    ?>
    <div class="news-detail__score">
      <div class="news-detail__team">
        <span class="news-detail__team-name"><?php echo esc_html($home); ?></span>
        <span class="news-detail__team-score"><?php echo esc_html($home_score !== '' ? $home_score : '-'); ?></span>
      </div>
      <span class="news-detail__vs">VS</span>
      <div class="news-detail__team">
        <span class="news-detail__team-name"><?php echo esc_html($away ?: '対戦校'); ?></span>
        <span class="news-detail__team-score"><?php echo esc_html($away_score !== '' ? $away_score : '-'); ?></span>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function tokai_render_members_table($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $members = tokai_parse_members(get_post_meta($post_id, 'match_members', true));

    if (empty($members)) {
        return '';
    }

    ob_start();
    ?>
    <section class="news-detail__members">
      <h2 class="news-detail__members-title">メンバー</h2>
      <div class="news-detail__members-table-wrap">
        <table class="news-detail__members-table">
          <thead>
            <tr>
              <th>背番号</th>
              <th>名前</th>
              <th>ポジション</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($members as $member) : ?>
              <tr>
                <td class="news-detail__member-no"><?php echo esc_html($member['number']); ?></td>
                <td class="news-detail__member-name"><?php echo esc_html($member['name']); ?></td>
                <td class="news-detail__member-pos"><?php echo esc_html($member['position']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

function tokai_page_hero($title_en, $title_ja, $modifier = '') {
    $class = 'page-hero' . ($modifier ? ' ' . esc_attr($modifier) : '');
    ?>
    <div class="<?php echo esc_attr($class); ?>">
      <h1 class="page-hero__title-en"><?php echo esc_html($title_en); ?></h1>
      <p class="page-hero__title-ja"><?php echo esc_html($title_ja); ?></p>
      <img src="<?php echo esc_url(tokai_asset('hero-emblem.png')); ?>" alt="" class="page-hero__emblem" aria-hidden="true">
    </div>
    <?php
}
