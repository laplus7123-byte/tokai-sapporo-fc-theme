<?php
/**
 * Template Name: Thanks
 */

get_header();
?>

<section class="thanks">
  <div class="thanks__inner">
    <h1 class="thanks__title">送信完了</h1>
    <p class="thanks__text">お問い合わせありがとうございました。<br>内容を確認のうえ、担当者よりご連絡いたします。</p>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">トップページへ戻る</a>
  </div>
</section>

<?php get_footer(); ?>
