<?php get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <div class="page-hero">
    <h1 class="page-hero__title-en"><?php the_title(); ?></h1>
  </div>
  <section class="section section--white page-body page-body--white">
    <div class="section__inner legal">
      <div class="news-detail__content wp-content">
        <?php the_content(); ?>
      </div>
    </div>
  </section>
<?php endwhile; endif; ?>

<?php get_footer(); ?>
