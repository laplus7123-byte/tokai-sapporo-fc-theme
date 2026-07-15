/**
 * メンバー一覧
 */
(function () {
  const GRADE_ORDER = ['3年生', '2年生', '1年生'];
  const GRADE_MAP = {
    '3rd Grade': '3年生',
    '2nd Grade': '2年生',
    '1st Grade': '1年生',
    '3年生': '3年生',
    '2年生': '2年生',
    '1年生': '1年生',
  };

  function normalizeGrade(grade) {
    return GRADE_MAP[grade] || grade;
  }

  function normalizeData(data) {
    return {
      ...data,
      players: (data.players || []).map((p) => ({
        ...p,
        grade: normalizeGrade(p.grade),
      })),
    };
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function renderCard(member, subtitleKey) {
    const subtitle = member[subtitleKey] || '';
    const image = member.image || '';
    const imageHtml = image
      ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(member.name)}" loading="lazy">`
      : '<span class="member-card__placeholder" aria-hidden="true"></span>';

    return `
      <div class="member-card">
        <div class="member-card__photo">
          ${imageHtml}
        </div>
        <p class="member-card__name">${escapeHtml(member.name)}</p>
        ${subtitle ? `<p class="member-card__position">${escapeHtml(subtitle)}</p>` : ''}
      </div>
    `;
  }

  const NAV_ITEMS = [
    { id: 'members-grade-3', label: '3年生' },
    { id: 'members-grade-2', label: '2年生' },
    { id: 'members-grade-1', label: '1年生' },
    { id: 'members-staff', label: 'スタッフ' },
  ];

  function renderNav() {
    const buttons = NAV_ITEMS.map(
      (item, i) =>
        `<button type="button" class="members-nav__btn${i === 0 ? ' is-active' : ''}" data-target="${item.id}">${escapeHtml(item.label)}</button>`,
    ).join('');

    return `<nav class="members-nav" aria-label="メンバーカテゴリー">${buttons}</nav>`;
  }

  function renderSection(title, members, subtitleKey, sectionId) {
    if (!members.length) return '';
    const cards = members.map((m) => renderCard(m, subtitleKey)).join('');
    return `
      <div class="members-category" id="${sectionId}">
        <h2 class="members-category__title">${escapeHtml(title)}</h2>
        <div class="members-grid">${cards}</div>
      </div>
    `;
  }

  function renderMembers(data) {
    const normalized = normalizeData(data);
    let html = renderNav();

    for (const grade of GRADE_ORDER) {
      const players = (normalized.players || []).filter((p) => p.grade === grade);
      if (players.length) {
        const id = `members-grade-${grade.charAt(0)}`;
        html += renderSection(grade, players, '', id);
      }
    }

    html += renderSection('Staff', normalized.staff || [], 'role', 'members-staff');

    return html || '<p class="members-empty">メンバー情報がありません。</p>';
  }

  function bindMembersNav(container) {
    const nav = container.querySelector('.members-nav');
    if (!nav) return;

    const buttons = nav.querySelectorAll('.members-nav__btn');
    const sections = NAV_ITEMS.map((item) => document.getElementById(item.id)).filter(Boolean);

    if (!sections.length) return;

    function setActive(id) {
      buttons.forEach((btn) => {
        btn.classList.toggle('is-active', btn.dataset.target === id);
      });
    }

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        if (!target) return;

        const offset = nav.offsetHeight + parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-height'), 10) + 16;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
        setActive(btn.dataset.target);
      });
    });

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
        if (visible.length) {
          setActive(visible[0].target.id);
        }
      },
      {
        rootMargin: `-${nav.offsetHeight + 80}px 0px -55% 0px`,
        threshold: [0, 0.25, 0.5],
      },
    );

    sections.forEach((section) => observer.observe(section));
  }

  async function loadMembersData() {
    if (window.MEMBERS_DATA && ((window.MEMBERS_DATA.players || []).length || (window.MEMBERS_DATA.staff || []).length)) {
      return window.MEMBERS_DATA;
    }

    const apiUrl = window.TOKAI_MEMBERS_CONFIG?.apiUrl;
    if (apiUrl) {
      try {
        const res = await fetch(apiUrl);
        if (res.ok) {
          const data = await res.json();
          if ((data.players || []).length || (data.staff || []).length) {
            return data;
          }
        }
      } catch (err) {
        console.warn('[Members] API failed, using fallback:', err);
      }
    }

    if (window.MEMBERS_DATA) {
      return window.MEMBERS_DATA;
    }

    const res = await fetch('data/members.json');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  async function initMembers() {
    const container = document.getElementById('membersApp');
    if (!container) return;

    try {
      if (!container.querySelector('.members-category')) {
        const data = await loadMembersData();
        container.innerHTML = renderMembers(data);
      }

      bindMembersNav(container);
    } catch (err) {
      console.error('[Members]', err);
      if (!container.querySelector('.members-category')) {
        container.innerHTML = '<p class="members-empty">メンバー情報の読み込みに失敗しました。</p>';
      }
    }
  }

  document.addEventListener('DOMContentLoaded', initMembers);
  window.TokaiMembers = { bindMembersNav, renderMembers };
})();
