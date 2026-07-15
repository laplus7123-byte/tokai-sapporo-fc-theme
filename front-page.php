<?php get_header(); ?>

<section class="hero" id="top">
  <div class="hero__slider">
    <div class="hero__slide is-active">
      <img src="<?php echo esc_url(tokai_asset('p_1688184295.jpeg')); ?>" alt="チーム写真">
    </div>
    <div class="hero__slide">
      <img src="<?php echo esc_url(tokai_asset('p_1688184347.jpeg')); ?>" alt="チーム写真">
    </div>
    <div class="hero__slide">
      <img src="<?php echo esc_url(tokai_asset('p_1688184388.jpeg')); ?>" alt="チーム写真">
    </div>
    <div class="hero__slide">
      <img src="<?php echo esc_url(tokai_asset('p_1703486976.jpg')); ?>" alt="チーム写真">
    </div>
  </div>
  <div class="hero__overlay"></div>
  <div class="hero__badge">
    <img src="<?php echo esc_url(tokai_asset('hero-emblem.png')); ?>" alt="TOKAI SAPPORO FOOTBALL CLUB" class="hero__badge-emblem">
  </div>
  <div class="hero__content">
    <h1 class="sr-only">東海大学付属札幌高等学校サッカー部｜札幌の高校サッカー 東海サッカー部</h1>
    <img src="<?php echo esc_url(tokai_asset('hero-catchphrase.png')); ?>" alt="一体感に勝る戦術なし" class="hero__catchphrase">
  </div>
</section>

<section class="section section--black" id="news">
  <div class="section__inner">
    <div class="section__header">
      <h2 class="section__title">
        <span class="section__title-en">News</span>
        <span class="section__title-ja">ニュース</span>
      </h2>
    </div>
    <div class="news-tabs" role="tablist" aria-label="ニュースカテゴリー">
      <button type="button" class="news-tabs__btn is-active" role="tab" aria-selected="true" data-filter="all">ALL</button>
      <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="oshirase">お知らせ</button>
      <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="top">TOP</button>
      <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="2nd">2nd</button>
      <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="3rd">3rd</button>
      <button type="button" class="news-tabs__btn" role="tab" aria-selected="false" data-filter="4th">4th</button>
    </div>
    <div class="news-list" data-news-list data-limit="6" data-heading="h3"></div>
    <div class="section__more-wrap"><a href="<?php echo esc_url(tokai_page_url('news')); ?>" class="section__more">More</a></div>
  </div>
</section>

<section class="philosophy">
  <img src="<?php echo esc_url(tokai_asset('hero-emblem.png')); ?>" alt="" class="philosophy__emblem" aria-hidden="true">
  <div class="philosophy__inner">
    <h2 class="philosophy__title">「応援される」選手・チームに！</h2>
    <img src="<?php echo esc_url(tokai_asset('philosophy-catchphrase.png')); ?>" alt="一体感に勝る戦術なし" class="philosophy__catchphrase">
    <p class="philosophy__text">この言葉を胸に刻み、日々サッカーを通じた人格の形成を目指し活動しています。</p>
    <p class="philosophy__text">共に努力しかけがえのない時間を過ごす仲間の為、そして、応援してくださるすべてのサポーターと心を一つにして、「一体感」を武器に北海道の高校サッカーを牽引する存在であり続けます。攻守において主導権を握るサッカーで、北海道の頂点を掴み、全国の舞台で勝負します。</p>
  </div>
</section>

<section class="section section--black" id="instagram">
  <div class="section__inner instagram">
    <div class="section__header section__header--center">
      <h2 class="section__title">
        <span class="section__title-en">Instagram</span>
        <span class="section__title-ja">インスタグラム</span>
      </h2>
    </div>
    <a href="https://www.instagram.com/tokaisapporo.fc/" target="_blank" rel="noopener" class="instagram__link">
      <?php echo tokai_instagram_icon(); ?>
      @tokaisapporo.fc
    </a>
    <?php tokai_render_instagram_feed(); ?>
  </div>
</section>

<section class="section section--white" id="application">
  <div class="section__inner">
    <div class="section__header section__header--center">
      <h2 class="section__title">
        <span class="section__title-en">Application</span>
        <span class="section__title-ja">公式アプリ</span>
      </h2>
    </div>
    <div class="app-promo">
      <img src="<?php echo esc_url(tokai_asset('app-promo.png')); ?>" alt="東海大学札幌サッカー部 公式アプリのご案内" class="app-promo__visual" width="1024" height="432" loading="lazy">
      <div class="app-promo__actions">
        <a href="<?php echo esc_url(get_theme_mod('tokai_app_store_url', '#')); ?>" class="app-promo__badge" target="_blank" rel="noopener" aria-label="App Storeからダウンロード">
          <img src="<?php echo esc_url(tokai_asset('badge-appstore.png')); ?>" alt="App Store からダウンロード" width="717" height="236" loading="lazy">
        </a>
        <a href="<?php echo esc_url(get_theme_mod('tokai_google_play_url', '#')); ?>" class="app-promo__badge" target="_blank" rel="noopener" aria-label="Google Playで手に入れよう">
          <img src="<?php echo esc_url(tokai_asset('badge-googleplay.png')); ?>" alt="Google Play で手に入れよう" width="717" height="236" loading="lazy">
        </a>
      </div>
    </div>
  </div>
</section>

<section class="section section--yellow" id="contact">
  <div class="section__inner">
    <div class="section__header section__header--center">
      <h2 class="section__title">
        <span class="section__title-en">Contact</span>
        <span class="section__title-ja">お問い合わせ</span>
      </h2>
    </div>
    <div class="contact__content">
      <p class="contact__text">
        東海大学付属札幌高等学校サッカー部に関する<br>
        お問い合わせは、<br>
        下記フォームよりお気軽にご連絡ください。
      </p>
      <dl class="contact__info">
        <dt>School</dt>
        <dd>東海大学付属札幌高等学校</dd>
        <dt>Address</dt>
        <dd>〒005-8508<br>北海道札幌市南区南沢5条1丁目1-1</dd>
        <dt>Instagram</dt>
        <dd><a href="https://www.instagram.com/tokaisapporo.fc/" target="_blank" rel="noopener">@tokaisapporo.fc</a></dd>
      </dl>
      <div class="contact__cta">
        <a href="<?php echo esc_url(tokai_page_url('contact')); ?>" class="btn-primary">お問い合わせはこちら</a>
      </div>
    </div>
  </div>
</section>

<section class="section section--black" id="sponsors">
  <div class="section__inner">
    <div class="section__header section__header--center">
      <h2 class="section__title">
        <span class="section__title-en">Sponsor</span>
        <span class="section__title-ja">スポンサー</span>
      </h2>
    </div>
    <div class="sponsors__grid">
      <a href="https://ueda-gr.jp/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962062.png')); ?>" alt="UEDA"><span class="sponsor-item__name">上田商会グループ</span></a>
      <a href="https://cuberu.jp/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962005.png')); ?>" alt="CUBERU"><span class="sponsor-item__name">CUBERU／上田商会</span></a>
      <a href="https://carcolle.info/index.html" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962080.png')); ?>" alt="カーコレクション"><span class="sponsor-item__name">カーコレクション</span></a>
      <a href="https://currystand-sapporo.com/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962145.png')); ?>" alt="カリースタンド"><span class="sponsor-item__name">カリースタンド</span></a>
      <a href="https://rally-homes-info.my.canva.site/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962207.png')); ?>" alt="ラリーホームズ"><span class="sponsor-item__name">ラリーホームズ</span></a>
      <a href="https://shibetsuyoukan.jp/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962272.png')); ?>" alt="標津羊羹"><span class="sponsor-item__name">標津羊羹本舗</span></a>
      <a href="https://saltworks.jp/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('p_1728962318.png')); ?>" alt="saltworks"><span class="sponsor-item__name">ソルトワークス</span></a>
      <a href="https://laplusgroup.com/" target="_blank" rel="noopener" class="sponsor-item"><img src="<?php echo esc_url(tokai_asset('sponsor-laplus.png')); ?>" alt="La plus"><span class="sponsor-item__name">株式会社La plus</span></a>
    </div>
    <div class="section__more-wrap"><a href="<?php echo esc_url(tokai_page_url('sponsor')); ?>" class="section__more">More</a></div>
  </div>
</section>

<?php get_footer(); ?>
