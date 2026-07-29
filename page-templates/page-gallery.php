<?php
/**
 * Template Name: Gallery
 * 試合投稿と連動するフォトギャラリー（試合アルバム）
 */

get_header();
tokai_page_hero('Gallery', 'ギャラリー');

$years = tokai_news_years();
// year=0 は全シーズン。未指定時は当年
$year_param = isset($_GET['year']) ? sanitize_text_field(wp_unslash($_GET['year'])) : '';
if ($year_param === 'all') {
    $current_year = 0;
} elseif ($year_param !== '') {
    $current_year = absint($year_param);
} else {
    $current_year = (int) ($years[0] ?? tokai_news_current_season_year());
}
if ($current_year > 0 && $current_year < 2026) {
    $current_year = (int) ($years[0] ?? 2026);
}

$team = isset($_GET['team']) ? sanitize_title(wp_unslash($_GET['team'])) : '';
if (!in_array($team, ['top', '2nd', '3rd', '4th'], true)) {
    $team = '';
}

$match_id = isset($_GET['match']) ? absint($_GET['match']) : 0;
$album_post = $match_id ? get_post($match_id) : null;
$album_items = [];

if ($album_post && $album_post->post_status === 'publish') {
    $album_items = tokai_get_match_gallery_items($album_post->ID);
    if (empty($album_items)) {
        $album_post = null;
    }
}

$posts = $album_post ? [] : tokai_get_gallery_posts([
    'year' => $current_year,
    'team' => $team,
]);
?>

<section class="section section--black page-body">
  <div class="section__inner">
    <?php if ($album_post) :
        $meta = tokai_get_match_album_meta($album_post->ID);
        $back_args = array_filter([
            'year' => $current_year > 0 ? $current_year : 'all',
            'team' => $team ?: null,
        ]);
        ?>
      <nav class="gallery-filters" aria-label="ギャラリーナビ">
        <a class="gallery-filters__back" href="<?php echo esc_url(tokai_page_url('gallery', $back_args)); ?>">← ギャラリー一覧</a>
      </nav>

      <header class="gallery-album__header">
        <p class="gallery-album__meta">
          <?php if ($meta['team']) : ?><span><?php echo esc_html($meta['team']); ?></span><?php endif; ?>
          <span><?php echo esc_html($meta['year']); ?></span>
          <time datetime="<?php echo esc_attr(get_the_date('c', $album_post)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $album_post)); ?></time>
        </p>
        <h2 class="gallery-album__title"><?php echo esc_html(get_the_title($album_post)); ?></h2>
        <?php if ($meta['score_line']) : ?>
          <p class="gallery-album__score"><?php echo esc_html($meta['score_line']); ?></p>
        <?php endif; ?>
        <p class="gallery-album__actions">
          <a href="<?php echo esc_url(get_permalink($album_post)); ?>" class="section__more">試合結果を見る</a>
        </p>
      </header>

      <div class="gallery-album__grid" aria-label="<?php echo esc_attr(get_the_title($album_post) . 'の写真'); ?>">
        <?php foreach ($album_items as $item) : ?>
          <a class="gallery-album__item" href="<?php echo esc_url($item['full']); ?>" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr($item['alt']); ?>" loading="lazy">
          </a>
        <?php endforeach; ?>
      </div>

    <?php else : ?>
      <p class="gallery-lead">試合結果の投稿に登録した写真を、試合ごとのアルバムとして表示します。</p>

      <nav class="gallery-filters" aria-label="年で絞り込み">
        <div class="gallery-filters__group" role="list">
          <a
            role="listitem"
            class="gallery-filters__btn<?php echo $current_year === 0 ? ' is-active' : ''; ?>"
            href="<?php echo esc_url(tokai_page_url('gallery', array_filter(['year' => 'all', 'team' => $team ?: null]))); ?>"
          >すべて</a>
          <?php foreach ($years as $year) : ?>
            <a
              role="listitem"
              class="gallery-filters__btn<?php echo (int) $year === $current_year ? ' is-active' : ''; ?>"
              href="<?php echo esc_url(tokai_page_url('gallery', array_filter(['year' => $year, 'team' => $team ?: null]))); ?>"
            ><?php echo esc_html($year); ?>年</a>
          <?php endforeach; ?>
        </div>
        <div class="gallery-filters__group" role="list" aria-label="チームで絞り込み">
          <a
            role="listitem"
            class="gallery-filters__btn<?php echo $team === '' ? ' is-active' : ''; ?>"
            href="<?php echo esc_url(tokai_page_url('gallery', $current_year > 0 ? ['year' => $current_year] : ['year' => 'all'])); ?>"
          >すべて</a>
          <?php foreach (['top' => 'TOP', '2nd' => '2nd', '3rd' => '3rd', '4th' => '4th'] as $slug => $label) : ?>
            <a
              role="listitem"
              class="gallery-filters__btn<?php echo $team === $slug ? ' is-active' : ''; ?>"
              href="<?php echo esc_url(tokai_page_url('gallery', array_filter([
                  'year' => $current_year > 0 ? $current_year : 'all',
                  'team' => $slug,
              ]))); ?>"
            ><?php echo esc_html($label); ?></a>
          <?php endforeach; ?>
        </div>
      </nav>

      <?php if (empty($posts)) : ?>
        <p class="gallery-empty">この条件の試合アルバムはまだありません。投稿編集画面の「試合ギャラリー」から画像を追加してください。</p>
      <?php else : ?>
        <div class="gallery-albums">
          <?php foreach ($posts as $post) :
              setup_postdata($post);
              $items = tokai_get_match_gallery_items($post->ID);
              $cover = $items[0]['thumb'] ?? '';
              $count = count($items);
              $meta = tokai_get_match_album_meta($post->ID);
              $album_url = tokai_page_url('gallery', array_filter([
                  'year'  => $current_year > 0 ? $current_year : 'all',
                  'team'  => $team ?: null,
                  'match' => $post->ID,
              ]));
              ?>
            <article class="gallery-album-card">
              <a class="gallery-album-card__link" href="<?php echo esc_url($album_url); ?>">
                <div class="gallery-album-card__cover">
                  <?php if ($cover) : ?>
                    <img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy">
                  <?php endif; ?>
                  <span class="gallery-album-card__count"><?php echo esc_html((string) $count); ?> photos</span>
                </div>
                <div class="gallery-album-card__body">
                  <p class="gallery-album-card__meta">
                    <?php if ($meta['team']) : ?><span><?php echo esc_html($meta['team']); ?></span><?php endif; ?>
                    <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $post)); ?></time>
                  </p>
                  <h2 class="gallery-album-card__title"><?php echo esc_html(get_the_title($post)); ?></h2>
                  <?php if ($meta['score_line']) : ?>
                    <p class="gallery-album-card__score"><?php echo esc_html($meta['score_line']); ?></p>
                  <?php endif; ?>
                </div>
              </a>
            </article>
          <?php endforeach;
          wp_reset_postdata(); ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>
