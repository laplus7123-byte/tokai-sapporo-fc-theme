<?php
/**
 * Single post template (News detail).
 */

get_header();

$category_slug = tokai_get_post_category_slug();
$team_label    = in_array($category_slug, ['top', '2nd', '3rd', '4th'], true)
    ? tokai_get_category_label($category_slug)
    : '';
$badge         = get_post_meta(get_the_ID(), 'news_badge', true);
$is_match      = $badge === 'match'
    || in_array($category_slug, ['top', '2nd', '3rd', '4th', 'match'], true);
$type_label    = $is_match ? '試合結果' : 'お知らせ';
$type_class    = $is_match ? 'match' : 'notice';
?>

<?php tokai_page_hero('News', 'ニュース'); ?>

<section class="section section--black page-body">
  <div class="section__inner section__inner--narrow">
    <article class="news-detail__article">
      <header class="news-detail__header">
        <div class="news-detail__meta">
          <span class="news-detail__category news-detail__category--<?php echo esc_attr($type_class); ?>"><?php echo esc_html($type_label); ?></span>
          <?php if ($team_label) : ?>
            <span class="news-detail__tag"><?php echo esc_html($team_label); ?></span>
          <?php endif; ?>
        </div>
        <time class="news-detail__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(tokai_format_post_date()); ?></time>
        <h1 class="news-detail__title"><?php the_title(); ?></h1>
      </header>

      <?php if (has_post_thumbnail()) : ?>
        <div class="news-detail__eyecatch">
          <?php the_post_thumbnail('large'); ?>
        </div>
      <?php endif; ?>

      <?php echo tokai_render_match_score(); ?>

      <div class="news-detail__content wp-content">
        <?php the_content(); ?>
      </div>

      <?php echo tokai_render_members_table(); ?>

      <p class="news-detail__back">
        <a href="<?php echo esc_url(tokai_page_url('news')); ?>" class="section__more">一覧に戻る</a>
      </p>
    </article>
  </div>
</section>

<?php get_footer(); ?>
