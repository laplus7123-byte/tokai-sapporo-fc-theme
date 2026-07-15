<?php
/**
 * Theme bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TOKAI_THEME_VERSION', '1.0.3');

require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/tokai-news-fields.php';
require_once get_template_directory() . '/inc/members.php';
require_once get_template_directory() . '/inc/members-admin.php';
require_once get_template_directory() . '/inc/instagram-import.php';
require_once get_template_directory() . '/inc/instagram-import-admin.php';

function tokai_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => __('Primary Menu', 'tokai-sapporo-fc'),
        'footer'  => __('Footer Menu', 'tokai-sapporo-fc'),
    ]);
}
add_action('after_setup_theme', 'tokai_theme_setup');

function tokai_enqueue_assets() {
    wp_enqueue_style(
        'tokai-google-fonts',
        'https://fonts.googleapis.com/css2?family=Anton&family=Noto+Sans+JP:wght@400;500;600;700&family=Oswald:wght@400;500;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'tokai-style',
        tokai_uri('assets/style.css'),
        ['tokai-google-fonts'],
        TOKAI_THEME_VERSION
    );

    wp_enqueue_script(
        'tokai-main',
        tokai_uri('js/main.js'),
        [],
        TOKAI_THEME_VERSION,
        true
    );

    if (is_front_page() || is_page('news') || is_singular('post')) {
        wp_enqueue_script(
            'tokai-news',
            tokai_uri('js/news.js'),
            [],
            TOKAI_THEME_VERSION,
            true
        );

        wp_localize_script('tokai-news', 'WORDPRESS_CONFIG', [
            'apiBase'      => esc_url_raw(rest_url('wp/v2')),
            'perPageHome'  => 6,
            'perPageList'  => 20,
            'newsUrl'      => esc_url_raw(tokai_page_url('news')),
            'postUrlBase'  => esc_url_raw(home_url('/')),
            'tabCategories' => [
                ['filter' => 'all', 'label' => 'ALL'],
                ['filter' => 'match', 'label' => '試合結果', 'slug' => 'match'],
                ['filter' => 'oshirase', 'label' => 'お知らせ', 'slug' => 'oshirase'],
                ['filter' => 'top', 'label' => 'TOP', 'slug' => 'top'],
                ['filter' => '2nd', 'label' => '2nd', 'slug' => '2nd'],
                ['filter' => '3rd', 'label' => '3rd', 'slug' => '3rd'],
                ['filter' => '4th', 'label' => '4th', 'slug' => '4th'],
            ],
        ]);
    }

    if (is_page_template('page-templates/page-members.php') || is_page('members')) {
        wp_enqueue_script(
            'tokai-members-data',
            tokai_uri('js/members-data.js'),
            [],
            TOKAI_THEME_VERSION,
            true
        );
        wp_enqueue_script(
            'tokai-members',
            tokai_uri('js/members.js'),
            ['tokai-members-data'],
            TOKAI_THEME_VERSION,
            true
        );

        wp_localize_script('tokai-members', 'TOKAI_MEMBERS_CONFIG', [
            'apiUrl' => esc_url_raw(rest_url('tokai/v1/members')),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'tokai_enqueue_assets');

function tokai_customize_register($wp_customize) {
    $wp_customize->add_section('tokai_settings', [
        'title'    => 'サッカー部サイト設定',
        'priority' => 30,
    ]);

    $wp_customize->add_setting('tokai_contact_email', [
        'default'           => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
    ]);
    $wp_customize->add_control('tokai_contact_email', [
        'label'   => 'お問い合わせ受信メール（FormSubmit）',
        'section' => 'tokai_settings',
        'type'    => 'email',
    ]);

    $wp_customize->add_setting('tokai_google_calendar_id', [
        'default'           => 'YOUR_CALENDAR_ID',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('tokai_google_calendar_id', [
        'label'       => 'Googleカレンダー ID',
        'description' => '例: abc123@group.calendar.google.com',
        'section'     => 'tokai_settings',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('tokai_app_store_url', [
        'default'           => '#',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('tokai_app_store_url', [
        'label'   => 'App Store URL',
        'section' => 'tokai_settings',
        'type'    => 'url',
    ]);

    $wp_customize->add_setting('tokai_google_play_url', [
        'default'           => '#',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('tokai_google_play_url', [
        'label'   => 'Google Play URL',
        'section' => 'tokai_settings',
        'type'    => 'url',
    ]);

    $wp_customize->add_setting('tokai_instagram_shortcode', [
        'default'           => '[instagram-feed feed=1]',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('tokai_instagram_shortcode', [
        'label'       => 'Instagram ショートコード',
        'description' => 'Instagram Feed 等のプラグインで発行されたショートコードを入力してください。例: [instagram-feed feed=1]',
        'section'     => 'tokai_settings',
        'type'        => 'text',
    ]);
}
add_action('customize_register', 'tokai_customize_register');

function tokai_get_contact_email() {
    $email = get_theme_mod('tokai_contact_email', get_option('admin_email'));
    return $email ?: get_option('admin_email');
}

function tokai_get_calendar_embed_url() {
    $calendar_id = get_theme_mod('tokai_google_calendar_id', 'YOUR_CALENDAR_ID');
    $params      = [
        'src'             => $calendar_id,
        'ctz'             => 'Asia/Tokyo',
        'mode'            => 'MONTH',
        'showTitle'       => '0',
        'showNav'         => '1',
        'showPrint'       => '0',
        'showTabs'        => '1',
        'showCalendars'   => '0',
        'showTz'          => '0',
        'bgcolor'         => '%230A0A0A',
    ];

    return 'https://calendar.google.com/calendar/embed?' . http_build_query($params);
}

function tokai_create_page_if_missing($title, $slug, $template = '') {
    $existing = get_page_by_path($slug);
    if ($existing) {
        if ($template && get_page_template_slug($existing->ID) !== $template) {
            update_post_meta($existing->ID, '_wp_page_template', $template);
        }
        return (int) $existing->ID;
    }

    $page_id = wp_insert_post([
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ]);

    if ($template && !is_wp_error($page_id)) {
        update_post_meta($page_id, '_wp_page_template', $template);
    }

    return is_wp_error($page_id) ? 0 : (int) $page_id;
}

function tokai_theme_activation() {
    $pages = [
        ['title' => 'News', 'slug' => 'news', 'template' => 'page-templates/page-news.php'],
        ['title' => 'Members', 'slug' => 'members', 'template' => 'page-templates/page-members.php'],
        ['title' => 'Schedule', 'slug' => 'schedule', 'template' => 'page-templates/page-schedule.php'],
        ['title' => 'Sponsor', 'slug' => 'sponsor', 'template' => 'page-templates/page-sponsor.php'],
        ['title' => 'Contact', 'slug' => 'contact', 'template' => 'page-templates/page-contact.php'],
        ['title' => 'Thanks', 'slug' => 'thanks', 'template' => 'page-templates/page-thanks.php'],
        ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'template' => 'page-templates/page-privacy-policy.php'],
    ];

    foreach ($pages as $page) {
        tokai_create_page_if_missing($page['title'], $page['slug'], $page['template']);
    }

    flush_rewrite_rules();
}
add_action('after_switch_theme', 'tokai_theme_activation');

function tokai_document_title($title) {
    if (is_front_page()) {
        return '東海大学付属札幌高等学校サッカー部｜札幌の高校サッカー 東海サッカー部 公式サイト';
    }
    return $title;
}
add_filter('pre_get_document_title', 'tokai_document_title');

function tokai_head_meta() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <meta name="keywords" content="東海大付属高等学校サッカー部,東海大学付属札幌高等学校サッカー部,札幌 高校サッカー,東海 サッカー部,東海大札幌 サッカー,高校サッカー 北海道">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SportsTeam",
      "name": "東海大学付属札幌高等学校サッカー部",
      "alternateName": ["東海大付属高等学校サッカー部", "東海サッカー部", "東海大札幌サッカー部"],
      "sport": "サッカー",
      "url": "<?php echo esc_url(home_url('/')); ?>",
      "logo": "<?php echo esc_url(tokai_asset('team-logo.png')); ?>",
      "parentOrganization": {
        "@type": "HighSchool",
        "name": "東海大学付属札幌高等学校",
        "address": {
          "@type": "PostalAddress",
          "postalCode": "005-8508",
          "addressRegion": "北海道",
          "addressLocality": "札幌市南区",
          "streetAddress": "南沢5条1丁目1-1",
          "addressCountry": "JP"
        }
      },
      "sameAs": ["https://www.instagram.com/tokaisapporo.fc/"]
    }
    </script>
    <?php
}
add_action('wp_head', 'tokai_head_meta', 1);

add_filter('wp_robots', function ($robots) {
    if (is_page('thanks')) {
        $robots['noindex'] = true;
    }
    return $robots;
});
