<?php
/**
 * Template Name: Schedule
 */

get_header();
tokai_page_hero('Schedule', 'スケジュール');
?>

<section class="section section--black page-body">
  <div class="section__inner">
    <div class="gcal">
      <iframe
        class="gcal__frame"
        title="東海大学付属札幌高等学校サッカー部 スケジュール"
        src="<?php echo esc_url(tokai_get_calendar_embed_url()); ?>"
        style="border:0"
        frameborder="0"
        scrolling="no"
        loading="lazy"></iframe>
    </div>
    <p class="note">※ スケジュールは変更になる場合があります。最新情報はNEWSをご確認ください。</p>
  </div>
</section>

<?php get_footer(); ?>
