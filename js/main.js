document.addEventListener('DOMContentLoaded', () => {
  initOpening();
  initMobileNav();
  initHeroSlider();
  setActiveNav();
});

function initOpening() {
  const opening = document.getElementById('opening');
  const video = opening?.querySelector('.opening__video');
  const skipBtn = document.getElementById('openingSkip');
  if (!opening || !video) return;

  function closeOpening() {
    opening.classList.add('is-hidden');
    document.body.classList.remove('opening-active');
    video.pause();
    sessionStorage.setItem('openingSeen', '1');
  }

  if (sessionStorage.getItem('openingSeen')) {
    opening.classList.add('is-hidden');
    document.body.classList.remove('opening-active');
    return;
  }

  skipBtn?.addEventListener('click', closeOpening);
  video.addEventListener('ended', closeOpening);
  video.play().catch(() => {});
}

function initMobileNav() {
  const toggle = document.querySelector('.header__toggle');
  const nav = document.querySelector('.header__nav');
  if (!toggle || !nav) return;

  const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

  toggle.addEventListener('click', () => {
    toggle.classList.toggle('is-open');
    nav.classList.toggle('is-open');
  });

  const subItem = nav.querySelector('.header__nav-item--has-sub');
  const subLink = subItem?.querySelector('.header__nav-link');

  if (subItem && subLink) {
    subLink.addEventListener('click', (e) => {
      if (isMobile()) {
        e.preventDefault();
        subItem.classList.toggle('is-open');
      }
    });
  }

  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', (e) => {
      if (link === subLink && isMobile() && e.defaultPrevented) return;
      toggle.classList.remove('is-open');
      nav.classList.remove('is-open');
    });
  });
}

function initHeroSlider() {
  const slides = document.querySelectorAll('.hero__slide');
  if (slides.length === 0) return;

  let current = 0;
  let timer;

  function goTo(index) {
    slides[current].classList.remove('is-active');
    current = index;
    slides[current].classList.add('is-active');
  }

  function next() {
    goTo((current + 1) % slides.length);
  }

  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(next, 5000);
  }

  resetTimer();
}

function setActiveNav() {
  const path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.header__nav a').forEach(link => {
    const href = link.getAttribute('href');
    if (href === path || (path === '' && href === 'index.html')) {
      link.classList.add('is-active');
    }
  });
}
