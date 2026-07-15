<?php
/**
 * Template Name: Contact
 */

get_header();
tokai_page_hero('Contact', 'お問い合わせ');
?>

<section class="section section--white page-body page-body--white">
  <div class="section__inner">
    <div class="contact-form-intro">
      <p>
        東海大学付属札幌高等学校サッカー部へのお問い合わせは、下記フォームよりお願いいたします。<br>
        内容を確認のうえ、担当者よりご連絡いたします。
      </p>
    </div>

    <form class="contact-form" action="https://formsubmit.co/<?php echo esc_attr(tokai_get_contact_email()); ?>" method="POST">
      <input type="hidden" name="_subject" value="【公式サイト】お問い合わせ">
      <input type="hidden" name="_captcha" value="false">
      <input type="hidden" name="_template" value="table">
      <input type="hidden" name="_next" value="<?php echo esc_url(tokai_page_url('thanks')); ?>">

      <div class="form-field">
        <label for="cf-name">お名前 <span class="form-required">必須</span></label>
        <input type="text" id="cf-name" name="お名前" required autocomplete="name" placeholder="山田 太郎">
      </div>

      <div class="form-field">
        <label for="cf-kana">ふりがな</label>
        <input type="text" id="cf-kana" name="ふりがな" placeholder="やまだ たろう">
      </div>

      <div class="form-field">
        <label for="cf-email">メールアドレス <span class="form-required">必須</span></label>
        <input type="email" id="cf-email" name="メールアドレス" required autocomplete="email" placeholder="example@mail.com">
      </div>

      <div class="form-field">
        <label for="cf-tel">電話番号</label>
        <input type="tel" id="cf-tel" name="電話番号" autocomplete="tel" placeholder="090-1234-5678">
      </div>

      <div class="form-field">
        <label for="cf-type">お問い合わせ種別</label>
        <select id="cf-type" name="お問い合わせ種別">
          <option value="入部・体験について">入部・体験について</option>
          <option value="試合・イベントについて">試合・イベントについて</option>
          <option value="取材・メディアについて">取材・メディアについて</option>
          <option value="スポンサー・ご支援について">スポンサー・ご支援について</option>
          <option value="その他">その他</option>
        </select>
      </div>

      <div class="form-field">
        <label for="cf-message">お問い合わせ内容 <span class="form-required">必須</span></label>
        <textarea id="cf-message" name="お問い合わせ内容" rows="7" required placeholder="お問い合わせ内容をご記入ください"></textarea>
      </div>

      <p class="form-note">ご入力いただいた個人情報は、お問い合わせへの対応のみに使用いたします。</p>

      <div class="form-actions">
        <button type="submit" class="btn-primary">送信する</button>
      </div>
    </form>
  </div>
</section>

<?php get_footer(); ?>
