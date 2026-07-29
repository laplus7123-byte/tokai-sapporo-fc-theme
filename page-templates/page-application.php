<?php
/**
 * Template Name: Application
 */

get_header();
tokai_page_hero('Application', '公式アプリ');

$app_store = get_theme_mod('tokai_app_store_url', '#');
$google_play = get_theme_mod('tokai_google_play_url', '#');
$overview_lead = tokai_app_get_overview_lead();
$features = tokai_app_get_features();
?>

<section class="section section--black page-body">
  <div class="section__inner section__inner--narrow">
    <nav class="app-nav" aria-label="アプリ紹介の構成">
      <a href="#app-overview">1 Overview</a>
      <a href="#app-feature">2 Feature</a>
      <a href="#app-download">3 Download</a>
    </nav>

    <div class="app-overview" id="app-overview">
      <p class="app-overview__label">Overview</p>
      <h2 class="app-overview__title">東海大学付属札幌高等学校サッカー部<br>公式アプリ</h2>
      <p class="app-overview__lead">
        <?php echo nl2br(esc_html($overview_lead)); ?>
      </p>
      <div class="app-overview__visual">
        <img src="<?php echo esc_url(tokai_asset('app-promo.png')); ?>" alt="東海大学札幌サッカー部 公式アプリ" width="1024" height="432" loading="lazy">
      </div>
    </div>
  </div>
</section>

<section class="section section--white" id="app-feature">
  <div class="section__inner">
    <div class="section__header section__header--center">
      <h2 class="section__title">
        <span class="section__title-en">Feature</span>
        <span class="section__title-ja">アプリでできること</span>
      </h2>
    </div>

    <div class="app-features">
      <?php foreach ($features as $i => $feature) : ?>
        <?php
        $num = sprintf('%02d', $i + 1);
        $image_url = $feature['image']['url'] ?? '';
        ?>
        <article class="app-feature">
          <p class="app-feature__num"><?php echo esc_html($num); ?></p>
          <?php if ($image_url !== '') : ?>
            <div class="app-feature__media">
              <img src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy">
            </div>
          <?php endif; ?>
          <h3 class="app-feature__title"><?php echo esc_html($feature['title']); ?></h3>
          <p class="app-feature__text"><?php echo nl2br(esc_html($feature['text'])); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--yellow" id="app-download">
  <div class="section__inner">
    <div class="section__header section__header--center">
      <h2 class="section__title">
        <span class="section__title-en">Download</span>
        <span class="section__title-ja">ダウンロード</span>
      </h2>
    </div>
    <p class="app-download__text">App Store / Google Play から無料でダウンロードできます。</p>
    <div class="app-promo__actions">
      <a href="<?php echo esc_url($app_store); ?>" class="app-promo__badge" target="_blank" rel="noopener" aria-label="App Storeからダウンロード">
        <img src="<?php echo esc_url(tokai_asset('badge-appstore.png')); ?>" alt="App Store からダウンロード" width="717" height="236" loading="lazy">
      </a>
      <a href="<?php echo esc_url($google_play); ?>" class="app-promo__badge" target="_blank" rel="noopener" aria-label="Google Playで手に入れよう">
        <img src="<?php echo esc_url(tokai_asset('badge-googleplay.png')); ?>" alt="Google Play で手に入れよう" width="717" height="236" loading="lazy">
      </a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
