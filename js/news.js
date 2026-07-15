/**
 * WordPress REST API 連携 - ニュース一覧・詳細
 */
(function () {
  const config = window.WORDPRESS_CONFIG || {};

  const DEMO_POSTS = [
    {
      id: 1,
      slug: 'new-member-recruitment',
      title: { rendered: '新入部員募集のお知らせ' },
      date: '2026-01-10T10:00:00',
      content: { rendered: '<p>2026年度の新入部員を募集しています。詳細はお問い合わせください。</p>' },
      categories: [{ slug: 'oshirase', name: 'お知らせ' }],
      meta: { news_badge: 'notice' },
      _embedded: {},
    },
    {
      id: 2,
      slug: 'championship-tomakomai',
      title: { rendered: '選手権全道大会　東海 vs 苫小牧中央' },
      date: '2025-10-17T14:00:00',
      content: { rendered: '<p>激戦の末、勝利を収めました。</p>' },
      categories: [{ slug: 'top', name: 'TOP' }],
      meta: {
        news_badge: 'match',
        match_home_team: '東海',
        match_opponent: '苫小牧中央',
        match_home_score: '2',
        match_away_score: '1',
        match_members: '10|山田 太郎|GK\n11|佐藤 健|DF\n9|鈴木 大輔|FW',
      },
      _embedded: { 'wp:featuredmedia': [{ source_url: 'assets/p_1688184295.jpeg' }] },
    },
    {
      id: 3,
      slug: 'summer-cup-morioka',
      title: { rendered: 'SUMMER CUP U-18 東海 vs 盛岡誠桜' },
      date: '2024-12-18T10:00:00',
      content: { rendered: '<p>SUMMER CUP U-18の試合結果です。</p>' },
      categories: [{ slug: 'top', name: 'TOP' }],
      meta: {
        news_badge: 'match',
        match_home_team: '東海',
        match_opponent: '盛岡誠桜',
        match_home_score: '3',
        match_away_score: '0',
        match_members: '1|田中 翔|GK\n4|高橋 蓮|DF\n7|伊藤 颯|MF',
      },
      _embedded: {},
    },
    {
      id: 4,
      slug: 'summer-cup-muroran',
      title: { rendered: 'SUMMER CUP U-18 東海 vs 室蘭大谷' },
      date: '2024-12-18T10:00:00',
      content: { rendered: '<p>2ndチームの試合結果です。</p>' },
      categories: [{ slug: '2nd', name: '2nd' }],
      meta: { news_badge: 'match', match_home_team: '東海', match_opponent: '室蘭大谷', match_home_score: '1', match_away_score: '1' },
      _embedded: {},
    },
    {
      id: 5,
      slug: 'summer-cup-hokkai',
      title: { rendered: 'SUMMER CUP U18 東海 vs 北海' },
      date: '2024-12-18T10:00:00',
      content: { rendered: '<p>3rdチームの試合結果です。</p>' },
      categories: [{ slug: '3rd', name: '3rd' }],
      meta: { news_badge: 'match', match_home_team: '東海', match_opponent: '北海', match_home_score: '2', match_away_score: '2' },
      _embedded: {},
    },
    {
      id: 6,
      slug: 'summer-cup-eiran',
      title: { rendered: 'SUMMER CUP U-18 東海 vs 英藍' },
      date: '2024-12-18T10:00:00',
      content: { rendered: '<p>4thチームの試合結果です。</p>' },
      categories: [{ slug: '4th', name: '4th' }],
      meta: { news_badge: 'match', match_home_team: '東海', match_opponent: '英藍', match_home_score: '4', match_away_score: '0' },
      _embedded: {},
    },
  ];

  function getMeta(post, key) {
    return post.meta?.[key] ?? post.acf?.[key] ?? '';
  }

  function getCategorySlug(post) {
    const cats = post.categories || [];
    if (Array.isArray(cats) && cats.length > 0) {
      if (typeof cats[0] === 'object' && cats[0].slug) {
        const tabSlugs = (config.tabCategories || [])
          .map((t) => t.slug)
          .filter(Boolean);
        const match = cats.find((c) => tabSlugs.includes(c.slug));
        return match?.slug || cats[0].slug;
      }
    }
    return 'oshirase';
  }

  function getFeaturedImage(post) {
    const media = post._embedded?.['wp:featuredmedia']?.[0];
    return media?.source_url || '';
  }

  function formatDate(dateStr) {
    const d = new Date(dateStr);
    if (Number.isNaN(d.getTime())) return dateStr;
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}.${m}.${day}`;
  }

  function getBadgeSrc(post) {
    const badge = getMeta(post, 'news_badge');
    if (badge === 'match') return config.badgeMatch || config.defaultBadge;
    if (badge === 'notice') return config.badgeNotice || config.defaultBadge;
    const slug = getCategorySlug(post);
    return slug === 'oshirase'
      ? (config.badgeNotice || config.defaultBadge)
      : (config.badgeMatch || config.defaultBadge);
  }

  function getBadgeAlt(post) {
    const badge = getMeta(post, 'news_badge');
    if (badge === 'notice' || getCategorySlug(post) === 'oshirase') return 'お知らせ';
    return '試合結果';
  }

  function parseMembers(raw) {
    if (!raw || typeof raw !== 'string') return [];
    return raw
      .trim()
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
      .map((line) => {
        const [number, name, position] = line.split('|').map((s) => s.trim());
        return { number: number || '', name: name || line, position: position || '' };
      });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || '';
  }

  function detailUrl(post) {
    if (post.link) return post.link;
    if (config.postUrlBase && post.slug) {
      return `${config.postUrlBase.replace(/\/$/, '')}/${encodeURIComponent(post.slug)}/`;
    }
    const slug = post.slug || post.id;
    return `?slug=${encodeURIComponent(slug)}`;
  }

  async function fetchPosts(perPage) {
    if (!config.apiBase) {
      return DEMO_POSTS.slice(0, perPage);
    }

    try {
      const url = new URL(`${config.apiBase.replace(/\/$/, '')}/posts`);
      url.searchParams.set('_embed', '1');
      url.searchParams.set('per_page', String(perPage));
      url.searchParams.set('orderby', 'date');
      url.searchParams.set('order', 'desc');

      const res = await fetch(url.toString());
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const posts = await res.json();
      return posts.map(normalizeWpPost);
    } catch (err) {
      console.warn('[News] WordPress API failed, using demo data:', err);
      return DEMO_POSTS.slice(0, perPage);
    }
  }

  async function fetchPostBySlug(slug) {
    if (!config.apiBase) {
      return DEMO_POSTS.find((p) => p.slug === slug) || null;
    }

    try {
      const url = new URL(`${config.apiBase.replace(/\/$/, '')}/posts`);
      url.searchParams.set('slug', slug);
      url.searchParams.set('_embed', '1');

      const res = await fetch(url.toString());
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const posts = await res.json();
      if (!posts.length) return null;
      return normalizeWpPost(posts[0]);
    } catch (err) {
      console.warn('[News] WordPress API failed:', err);
      return DEMO_POSTS.find((p) => p.slug === slug) || null;
    }
  }

  function normalizeWpPost(post) {
    const terms = post._embedded?.['wp:term']?.flat() || [];
    const categories = terms
      .filter((t) => t.taxonomy === 'category')
      .map((t) => ({ slug: t.slug, name: t.name }));

    const meta = {};
    if (post.meta && typeof post.meta === 'object') {
      Object.assign(meta, post.meta);
    }
    if (post.acf && typeof post.acf === 'object') {
      Object.assign(meta, post.acf);
    }

    return {
      ...post,
      categories: categories.length ? categories : post.categories,
      meta,
    };
  }

  function renderListItem(post, headingTag) {
    const tag = headingTag || 'h3';
    const title = stripHtml(post.title?.rendered || '');
    const category = getCategorySlug(post);
    const badge = getBadgeSrc(post);
    const badgeAlt = getBadgeAlt(post);
    const eyecatch = getFeaturedImage(post);
    const hasThumb = Boolean(eyecatch);

    const thumbHtml = hasThumb
      ? `<a class="news-item__thumb" href="${detailUrl(post)}"><img src="${escapeHtml(eyecatch)}" alt="" loading="lazy"></a>`
      : '';

    const year = getMeta(post, 'match_season')
      || (post.date ? new Date(post.date).getFullYear() : '');

    return `
      <article class="news-item${hasThumb ? ' has-thumb' : ''}" data-category="${escapeHtml(category)}" data-year="${escapeHtml(String(year))}">
        <div class="news-item__badge"><img src="${escapeHtml(badge)}" alt="${escapeHtml(badgeAlt)}"></div>
        <div class="news-item__body">
          <time class="news-item__date" datetime="${escapeHtml(post.date || '')}">${formatDate(post.date)}</time>
          <${tag} class="news-item__title"><a href="${detailUrl(post)}">${escapeHtml(title)}</a></${tag}>
        </div>
        ${thumbHtml}
      </article>
    `;
  }

  function renderMatchScore(post) {
    const home = getMeta(post, 'match_home_team') || '東海';
    const away = getMeta(post, 'match_opponent');
    const homeScore = getMeta(post, 'match_home_score');
    const awayScore = getMeta(post, 'match_away_score');

    if (!away && homeScore === '' && awayScore === '') return '';

    return `
      <div class="news-detail__score">
        <div class="news-detail__team">
          <span class="news-detail__team-name">${escapeHtml(home)}</span>
          <span class="news-detail__team-score">${escapeHtml(String(homeScore || '-'))}</span>
        </div>
        <span class="news-detail__vs">VS</span>
        <div class="news-detail__team">
          <span class="news-detail__team-name">${escapeHtml(away || '対戦校')}</span>
          <span class="news-detail__team-score">${escapeHtml(String(awayScore || '-'))}</span>
        </div>
      </div>
    `;
  }

  function renderMembers(post) {
    const members = parseMembers(getMeta(post, 'match_members'));
    if (!members.length) return '';

    const rows = members
      .map(
        (m) => `
        <tr>
          <td class="news-detail__member-no">${escapeHtml(m.number)}</td>
          <td class="news-detail__member-name">${escapeHtml(m.name)}</td>
          <td class="news-detail__member-pos">${escapeHtml(m.position)}</td>
        </tr>
      `,
      )
      .join('');

    return `
      <section class="news-detail__members">
        <h2 class="news-detail__members-title">メンバー</h2>
        <div class="news-detail__members-table-wrap">
          <table class="news-detail__members-table">
            <thead>
              <tr>
                <th>背番号</th>
                <th>名前</th>
                <th>ポジション</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </section>
    `;
  }

  function renderDetail(post) {
    const title = post.title?.rendered || '';
    const category = getCategorySlug(post);
    const catLabel =
      (config.tabCategories || []).find((t) => t.slug === category)?.label || category;
    const eyecatch = getFeaturedImage(post);
    const content = post.content?.rendered || '';

    const eyecatchHtml = eyecatch
      ? `<div class="news-detail__eyecatch"><img src="${escapeHtml(eyecatch)}" alt=""></div>`
      : '';

    return `
      <article class="news-detail__article">
        <header class="news-detail__header">
          <span class="news-detail__category">${escapeHtml(catLabel)}</span>
          <time class="news-detail__date" datetime="${escapeHtml(post.date || '')}">${formatDate(post.date)}</time>
          <h1 class="news-detail__title">${title}</h1>
        </header>
        ${eyecatchHtml}
        ${renderMatchScore(post)}
        <div class="news-detail__content wp-content">${content}</div>
        ${renderMembers(post)}
        <p class="news-detail__back"><a href="${escapeHtml(config.newsUrl || 'news.html')}" class="section__more">一覧に戻る</a></p>
      </article>
    `;
  }

  function bindTabs(tabsEl, listEl) {
    const buttons = tabsEl.querySelectorAll('.news-tabs__btn');

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const filter = btn.dataset.filter;
        const items = listEl.querySelectorAll('.news-item');

        buttons.forEach((b) => {
          b.classList.remove('is-active');
          b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('is-active');
        btn.setAttribute('aria-selected', 'true');

        items.forEach((item) => {
          const category = item.dataset.category;
          const show = filter === 'all' || category === filter;
          item.classList.toggle('is-hidden', !show);
        });
      });
    });
  }

  function applyUrlFilter(tabsEl, listEl) {
    const params = new URLSearchParams(window.location.search);
    const cat = params.get('cat');
    const year = params.get('year');

    if (cat && tabsEl) {
      const btn = tabsEl.querySelector(`.news-tabs__btn[data-filter="${cat}"]`);
      if (btn) {
        btn.click();
        return;
      }
    }

    if (year) {
      const items = listEl.querySelectorAll('.news-item');
      items.forEach((item) => {
        item.classList.toggle('is-hidden', item.dataset.year !== year);
      });
      if (tabsEl) {
        tabsEl.querySelectorAll('.news-tabs__btn').forEach((b) => {
          b.classList.remove('is-active');
          b.setAttribute('aria-selected', 'false');
        });
      }
    }
  }

  async function initNewsList() {
    const lists = document.querySelectorAll('[data-news-list]');
    if (!lists.length) return;

    for (const listEl of lists) {
      const limit = Number(listEl.dataset.limit) || config.perPageList || 20;
      const headingTag = listEl.dataset.heading || 'h3';
      const tabsEl = listEl.previousElementSibling?.classList.contains('news-tabs')
        ? listEl.previousElementSibling
        : null;

      listEl.innerHTML = '<p class="news-list__loading">読み込み中...</p>';

      const posts = await fetchPosts(limit);

      if (!posts.length) {
        listEl.innerHTML = '<p class="news-list__empty">投稿がありません。</p>';
        continue;
      }

      listEl.innerHTML = posts.map((p) => renderListItem(p, headingTag)).join('');

      if (tabsEl) bindTabs(tabsEl, listEl);

      applyUrlFilter(tabsEl, listEl);
    }
  }

  async function initNewsDetail() {
    const container = document.getElementById('newsDetail');
    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const slug = params.get('slug');

    if (!slug) {
      container.innerHTML = '<p class="news-detail__error">記事が見つかりません。</p>';
      return;
    }

    const post = await fetchPostBySlug(slug);

    if (!post) {
      container.innerHTML = '<p class="news-detail__error">記事が見つかりません。</p>';
      return;
    }

    container.innerHTML = renderDetail(post);
    document.title = `${stripHtml(post.title?.rendered || '')} | 東海大学付属札幌高等学校サッカー部`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    initNewsList();
    initNewsDetail();
  });

  window.TokaiNews = { fetchPosts, fetchPostBySlug, initNewsList, initNewsDetail };
})();
