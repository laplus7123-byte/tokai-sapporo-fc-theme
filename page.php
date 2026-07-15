<?php
/**
 * Fallback page template.
 */

get_header();
tokai_page_hero(get_the_title(), get_the_title());
?>

<section class="section section--white page-body page-body--white">
  <div class="section__inner legal">
    <div class="news-detail__content wp-content">
      <?php while (have_posts()) : the_post(); the_content(); endwhile; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
