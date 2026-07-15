<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="header">
  <div class="header__inner">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="header__logo" aria-label="TOKAI SAPPORO FOOTBALL CLUB">
      <span class="header__logo-mark">
        <img src="<?php echo esc_url(tokai_asset('team-logo.png')); ?>" alt="">
      </span>
      <span class="header__logo-text">TOKAI SAPPORO FOOTBALL CLUB</span>
    </a>
    <nav class="header__nav">
      <div class="header__nav-item header__nav-item--has-sub">
        <a href="<?php echo esc_url(tokai_page_url('news')); ?>" class="header__nav-link">News</a>
        <div class="header__submenu">
          <a href="<?php echo esc_url(tokai_page_url('news', ['cat' => 'oshirase'])); ?>">お知らせ</a>
          <a href="<?php echo esc_url(tokai_page_url('news', ['cat' => 'top'])); ?>">TOP</a>
          <a href="<?php echo esc_url(tokai_page_url('news', ['cat' => '2nd'])); ?>">2nd</a>
          <a href="<?php echo esc_url(tokai_page_url('news', ['cat' => '3rd'])); ?>">3rd</a>
          <a href="<?php echo esc_url(tokai_page_url('news', ['cat' => '4th'])); ?>">4th</a>
          <span class="header__submenu-divider"></span>
          <?php foreach (tokai_news_years() as $year) : ?>
            <a href="<?php echo esc_url(tokai_page_url('news', ['year' => $year])); ?>"><?php echo esc_html($year); ?>年</a>
          <?php endforeach; ?>
          <a href="<?php echo esc_url(tokai_page_url('news')); ?>" class="header__submenu-back">backnumber</a>
        </div>
      </div>
      <a href="<?php echo esc_url(tokai_page_url('members')); ?>">Members</a>
      <a href="<?php echo esc_url(tokai_page_url('schedule')); ?>">Schedule</a>
      <a href="<?php echo esc_url(tokai_page_url('sponsor')); ?>">Sponsor</a>
      <a href="<?php echo esc_url(tokai_page_url('contact')); ?>">Contact</a>
      <a href="https://www.instagram.com/tokaisapporo.fc/" target="_blank" rel="noopener" class="header__nav-sns" aria-label="Instagram">
        <?php echo tokai_instagram_icon(); ?>
        <span class="header__nav-sns-label">Instagram</span>
      </a>
    </nav>
    <button class="header__toggle" aria-label="メニューを開く">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>
</header>
