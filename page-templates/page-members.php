<?php
/**
 * Template Name: Members
 */

get_header();
tokai_page_hero('Members', 'メンバー', 'page-hero--members');
?>

<section class="section section--black page-body">
  <div class="section__inner">
    <div id="membersApp">
      <?php echo tokai_render_members_html(); ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
