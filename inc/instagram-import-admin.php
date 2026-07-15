<?php
/**
 * Instagram 記事取込 管理画面
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'Instagram記事取込',
        'Instagram記事',
        'edit_pages',
        'tokai-instagram-import',
        'tokai_instagram_import_admin_page',
        'dashicons-instagram',
        28
    );
});

function tokai_instagram_import_admin_page() {
    if (!current_user_can('edit_pages')) {
        wp_die('権限がありません。');
    }

    $results      = [];
    $username     = get_option(TOKAI_INSTAGRAM_OPTION_USERNAME, 'tokaisapporo.fc');
    $article_type = 'auto';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tokai_instagram_import_nonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tokai_instagram_import_nonce'])), 'tokai_instagram_import_save')) {
            wp_die('不正なリクエストです。');
        }

        if (isset($_POST['tokai_instagram_username'])) {
            $username = sanitize_text_field(wp_unslash($_POST['tokai_instagram_username']));
            $username = trim($username, '@/');
            update_option(TOKAI_INSTAGRAM_OPTION_USERNAME, $username, false);
        }

        $raw_urls     = sanitize_textarea_field(wp_unslash($_POST['tokai_instagram_urls'] ?? ''));
        $urls         = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw_urls)));
        $status       = isset($_POST['tokai_instagram_status']) && $_POST['tokai_instagram_status'] === 'draft' ? 'draft' : 'publish';
        $article_type = sanitize_text_field(wp_unslash($_POST['tokai_instagram_article_type'] ?? 'auto'));

        if (!empty($urls)) {
            $results = tokai_import_instagram_posts_bulk($urls, [
                'status'       => $status,
                'article_type' => $article_type,
            ]);
        }
    }

    $profile_url = tokai_get_instagram_profile_url();
    ?>
    <div class="wrap">
      <h1>Instagram記事取込</h1>
      <p class="description">
        Instagram の投稿URLから、内容を読み取って WordPress の記事を自動作成します。<br>
        試合結果の投稿は <strong>3rd / 2nd / TOP / 4th</strong> を自動判定し、該当カテゴリーとタグ（例: 3rdチームの試合結果）を付けます。<br>
        アカウント: <a href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noopener">@<?php echo esc_html($username); ?></a>
      </p>

      <form method="post">
        <?php wp_nonce_field('tokai_instagram_import_save', 'tokai_instagram_import_nonce'); ?>

        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="tokai_instagram_username">Instagramユーザー名</label></th>
            <td>
              <input type="text" class="regular-text" id="tokai_instagram_username" name="tokai_instagram_username" value="<?php echo esc_attr($username); ?>" placeholder="tokaisapporo.fc">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="tokai_instagram_urls">投稿URL（1行に1件）</label></th>
            <td>
              <textarea id="tokai_instagram_urls" name="tokai_instagram_urls" rows="10" class="large-text code" placeholder="https://www.instagram.com/p/xxxxxxxx/"></textarea>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="tokai_instagram_article_type">記事の種類</label></th>
            <td>
              <select id="tokai_instagram_article_type" name="tokai_instagram_article_type">
                <?php foreach (tokai_instagram_article_types() as $value => $label) : ?>
                  <option value="<?php echo esc_attr($value); ?>" <?php selected($article_type, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">
                「自動判定」では本文から <code>3rd VS 学園札幌</code> や <code>4-3</code> などを読み取り、試合結果カテゴリーに振り分けます。
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">公開状態</th>
            <td>
              <label><input type="radio" name="tokai_instagram_status" value="publish" checked> 公開</label>
              &nbsp;&nbsp;
              <label><input type="radio" name="tokai_instagram_status" value="draft"> 下書き</label>
            </td>
          </tr>
        </table>

        <p class="submit">
          <button type="submit" class="button button-primary button-hero">記事に取り込む</button>
        </p>
      </form>

      <?php if (!empty($results)) : ?>
        <h2>取り込み結果</h2>
        <table class="widefat striped">
          <thead>
            <tr>
              <th>結果</th>
              <th>種類</th>
              <th>URL</th>
              <th>詳細</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $row) : ?>
              <tr>
                <td>
                  <?php
                  if (($row['status'] ?? '') === 'created') {
                      echo '作成';
                  } elseif (($row['status'] ?? '') === 'skipped') {
                      echo 'スキップ';
                  } else {
                      echo 'エラー';
                  }
                  ?>
                </td>
                <td><?php echo esc_html(tokai_instagram_article_types()[$row['article_type'] ?? ''] ?? '-'); ?></td>
                <td><code><?php echo esc_html($row['url'] ?? ''); ?></code></td>
                <td>
                  <?php
                  echo esc_html($row['message'] ?? '');
                  if (!empty($row['post_id'])) {
                      echo ' <a href="' . esc_url(get_edit_post_link($row['post_id'])) . '">編集</a>';
                      echo ' / <a href="' . esc_url(get_permalink($row['post_id'])) . '" target="_blank" rel="noopener">表示</a>';
                  }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <hr>
      <h2>試合結果として取り込まれる例</h2>
      <p>次のような投稿は <strong>試合結果（3rd）</strong> ＋ タグ <strong>3rdチームの試合結果</strong> になります。</p>
      <ul>
        <li>本文に <code>3rd VS 学園札幌</code> とスコア <code>4-3</code> がある</li>
        <li>カテゴリー: 試合結果 → 3rd</li>
        <li>スコア欄・得点者・選手コメントも自動反映</li>
        <li>NEWSの「3rd」タブに表示</li>
      </ul>
    </div>
    <?php
}
