<?php
/**
 * Template Name: News
 */

get_header();
tokai_page_hero('News', 'ニュース');
?>

<section class="section section--black page-body">
  <div class="section__inner">
    <div class="news-filters">
      <div class="news-tabs news-tabs--category" role="tablist" aria-label="ニュースカテゴリー">
        <button type="button" class="news-tabs__btn is-active" role="tab" aria-selected="true" data-filter="all">ALL</button>
        <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="match">試合結果</button>
        <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="oshirase">お知らせ</button>
      </div>
      <div class="news-tabs news-tabs--team" role="tablist" aria-label="チーム">
        <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="top">TOP</button>
        <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="2nd">2nd</button>
        <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="3rd">3rd</button>
        <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="4th">4th</button>
      </div>
    </div>
    <div class="news-list news-page-list" data-news-list data-limit="20" data-heading="h2"></div>
  </div>
</section>

<?php get_footer(); ?>
