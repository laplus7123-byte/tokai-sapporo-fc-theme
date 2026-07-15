<footer class="footer">
  <div class="footer__inner">
    <div class="footer__top">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="footer__logo">
        <img src="<?php echo esc_url(tokai_asset('team-logo.png')); ?>" alt="東海大学付属札幌高等学校サッカー部">
      </a>
      <nav class="footer__nav">
        <a href="<?php echo esc_url(tokai_page_url('news')); ?>">News</a>
        <a href="<?php echo esc_url(tokai_page_url('members')); ?>">Members</a>
        <a href="<?php echo esc_url(tokai_page_url('schedule')); ?>">Schedule</a>
        <a href="<?php echo esc_url(tokai_page_url('sponsor')); ?>">Sponsor</a>
        <a href="<?php echo esc_url(tokai_page_url('contact')); ?>">Contact</a>
      </nav>
      <div class="footer__sns">
        <a href="https://www.instagram.com/tokaisapporo.fc/" target="_blank" rel="noopener" aria-label="Instagram">
          <?php echo tokai_instagram_icon(); ?>
        </a>
      </div>
    </div>
    <nav class="footer__legal">
      <a href="<?php echo esc_url(tokai_page_url('privacy-policy')); ?>">プライバシーポリシー</a>
    </nav>
    <p class="footer__copy">&copy; Tokai Sapporo Football Club. All Rights Reserved.</p>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
