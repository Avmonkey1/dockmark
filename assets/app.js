const drawer = document.querySelector('[data-admin-drawer]');
const backdrop = document.querySelector('[data-close-admin].drawer-backdrop');
const openButtons = document.querySelectorAll('[data-open-admin]');
const closeButtons = document.querySelectorAll('[data-close-admin]');
const form = document.querySelector('[data-add-form]');
const statusText = document.querySelector('[data-form-status]');
const searchInput = document.querySelector('[data-search]');
const sidebar = document.querySelector('.sidebar');
const menuToggle = document.querySelector('[data-menu-toggle]');
const themeToggle = document.querySelector('[data-theme-toggle]');
const importForm = document.querySelector('[data-import-form]');
const importStatus = document.querySelector('[data-import-status]');
const restoreForm = document.querySelector('[data-restore-form]');
const restoreStatus = document.querySelector('[data-restore-status]');
const saveOrderButton = document.querySelector('[data-save-order]');
const orderStatus = document.querySelector('[data-order-status]');
const clockStyles = ['retro', 'matrix', 'pastel', 'flip'];
let draggedGroup = null;
let draggedLink = null;
let draggedPage = null;

function setTheme(theme) {
  document.documentElement.dataset.theme = theme;
  localStorage.setItem('dockmark-theme', theme);
}

function openDrawer() {
  drawer.classList.add('open');
  drawer.setAttribute('aria-hidden', 'false');
  backdrop.hidden = false;
  drawer.querySelector('input[name="title"]').focus();
}

function closeDrawer() {
  drawer.classList.remove('open');
  drawer.setAttribute('aria-hidden', 'true');
  backdrop.hidden = true;
}

openButtons.forEach((button) => button.addEventListener('click', openDrawer));
closeButtons.forEach((button) => button.addEventListener('click', closeDrawer));

document.querySelectorAll('[data-drawer-tab]').forEach((tab) => {
  tab.addEventListener('click', () => {
    const name = tab.dataset.drawerTab;
    document.querySelectorAll('[data-drawer-tab]').forEach((item) => item.classList.toggle('active', item === tab));
    document.querySelectorAll('[data-drawer-panel]').forEach((panel) => {
      panel.classList.toggle('active', panel.dataset.drawerPanel === name);
    });
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeDrawer();
    sidebar.classList.remove('open');
  }

  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    searchInput.focus();
  }
});

menuToggle?.addEventListener('click', () => {
  sidebar.classList.toggle('open');
});

themeToggle?.addEventListener('click', () => {
  const current = document.documentElement.dataset.theme || 'dark';
  setTheme(current === 'dark' ? 'light' : 'dark');
});

searchInput?.addEventListener('input', () => {
  const query = searchInput.value.trim().toLowerCase();
  const rows = document.querySelectorAll('[data-search-item]');

  rows.forEach((row) => {
    const searchable = `${row.textContent} ${row.dataset.searchText || ''}`.toLowerCase();
    const visible = searchable.includes(query);
    row.hidden = query.length > 0 && !visible;
  });
});

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  statusText.textContent = 'Saving...';

  const values = Object.fromEntries(new FormData(form).entries());
  const response = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'add_link', ...values })
  });

  const result = await response.json();
  if (!result.ok) {
    statusText.textContent = result.message || 'Could not save bookmark.';
    return;
  }

  statusText.textContent = 'Saved. Refreshing the board...';
  window.setTimeout(() => window.location.href = `?page=${encodeURIComponent(values.page)}`, 450);
});

importForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const file = importForm.elements.bookmark_file.files[0];
  if (!file) {
    importStatus.textContent = 'Choose a browser bookmarks HTML file first.';
    return;
  }

  importStatus.textContent = 'Reading bookmarks...';
  const content = await file.text();
  const values = Object.fromEntries(new FormData(importForm).entries());
  delete values.bookmark_file;

  const response = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'import_bookmarks', content, ...values })
  });

  const result = await response.json();
  if (!result.ok) {
    importStatus.textContent = result.message || 'Could not import bookmarks.';
    importStatus.classList.add('error');
    return;
  }

  importStatus.classList.remove('error');
  importStatus.textContent = `Imported ${result.added} new bookmarks. Refreshing...`;
  window.setTimeout(() => window.location.href = `?page=${encodeURIComponent(values.page)}`, 650);
});

restoreForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const file = restoreForm.elements.restore_file.files[0];
  if (!file) {
    restoreStatus.textContent = 'Choose a Dockmark JSON backup first.';
    return;
  }

  restoreStatus.textContent = 'Checking backup...';
  try {
    const data = JSON.parse(await file.text());
    const response = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'restore_json', data })
    });
    const result = await response.json();
    if (!result.ok) {
      throw new Error(result.message || 'Could not restore backup.');
    }

    restoreStatus.classList.remove('error');
    restoreStatus.textContent = 'Backup restored. Refreshing...';
    window.setTimeout(() => window.location.href = 'index.php', 650);
  } catch (error) {
    restoreStatus.classList.add('error');
    restoreStatus.textContent = error.message || 'Invalid backup file.';
  }
});

function collectOrder() {
  const pageIds = [...document.querySelectorAll('[data-page-name]')].map((page) => page.dataset.pageName);
  const groupIds = [];
  const linksByGroup = {};

  document.querySelectorAll('[data-group-id]').forEach((group) => {
    const groupId = group.dataset.groupId;
    groupIds.push(groupId);
    linksByGroup[groupId] = [...group.querySelectorAll('[data-link-id]')].map((link) => link.dataset.linkId);
  });

  return { pageIds, groupIds, linksByGroup };
}

async function saveOrder() {
  if (!saveOrderButton) return;
  orderStatus.textContent = 'Saving order...';
  const response = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'save_order', ...collectOrder() })
  });
  const result = await response.json();
  if (!result.ok) {
    orderStatus.classList.add('error');
    orderStatus.textContent = result.message || 'Could not save order.';
    return;
  }

  orderStatus.classList.remove('error');
  orderStatus.textContent = 'Order saved.';
}

saveOrderButton?.addEventListener('click', saveOrder);

document.querySelectorAll('[data-group-id]').forEach((group) => {
  group.addEventListener('dragstart', (event) => {
    if (event.target.closest('[data-link-id]')) return;
    draggedGroup = group;
    group.classList.add('dragging');
    event.dataTransfer.effectAllowed = 'move';
  });

  group.addEventListener('dragover', (event) => {
    if (!draggedGroup || draggedGroup === group) return;
    event.preventDefault();
    group.classList.add('drag-over');
  });

  group.addEventListener('dragleave', () => group.classList.remove('drag-over'));

  group.addEventListener('drop', (event) => {
    if (!draggedGroup || draggedGroup === group) return;
    event.preventDefault();
    group.classList.remove('drag-over');
    const stack = document.querySelector('[data-bookmark-stack]');
    const after = [...stack.children].indexOf(group) > [...stack.children].indexOf(draggedGroup);
    stack.insertBefore(draggedGroup, after ? group.nextSibling : group);
    orderStatus.textContent = 'Order changed. Save when ready.';
  });

  group.addEventListener('dragend', () => {
    group.classList.remove('dragging');
    document.querySelectorAll('.drag-over').forEach((item) => item.classList.remove('drag-over'));
    draggedGroup = null;
  });
});

document.querySelectorAll('[data-page-name]').forEach((page) => {
  page.addEventListener('dragstart', (event) => {
    draggedPage = page;
    page.classList.add('dragging');
    event.dataTransfer.effectAllowed = 'move';
  });

  page.addEventListener('dragover', (event) => {
    if (!draggedPage || draggedPage === page) return;
    event.preventDefault();
    page.classList.add('drag-over');
  });

  page.addEventListener('dragleave', () => page.classList.remove('drag-over'));

  page.addEventListener('drop', (event) => {
    if (!draggedPage || draggedPage === page) return;
    event.preventDefault();
    page.classList.remove('drag-over');
    const nav = page.closest('.page-nav');
    const after = [...nav.children].indexOf(page) > [...nav.children].indexOf(draggedPage);
    nav.insertBefore(draggedPage, after ? page.nextSibling : page);
    orderStatus.textContent = 'Page order changed. Save when ready.';
  });

  page.addEventListener('dragend', () => {
    page.classList.remove('dragging');
    document.querySelectorAll('.drag-over').forEach((item) => item.classList.remove('drag-over'));
    draggedPage = null;
  });
});

document.querySelectorAll('[data-link-id]').forEach((link) => {
  link.addEventListener('dragstart', (event) => {
    draggedLink = link;
    link.classList.add('dragging');
    event.dataTransfer.effectAllowed = 'move';
  });

  link.addEventListener('dragover', (event) => {
    if (!draggedLink || draggedLink === link) return;
    event.preventDefault();
    link.classList.add('drag-over');
  });

  link.addEventListener('dragleave', () => link.classList.remove('drag-over'));

  link.addEventListener('drop', (event) => {
    if (!draggedLink || draggedLink === link) return;
    event.preventDefault();
    link.classList.remove('drag-over');
    const list = link.closest('.link-list');
    const after = [...list.children].indexOf(link) > [...list.children].indexOf(draggedLink);
    list.insertBefore(draggedLink, after ? link.nextSibling : link);
    orderStatus.textContent = 'Order changed. Save when ready.';
  });

  link.addEventListener('dragend', () => {
    link.classList.remove('dragging');
    document.querySelectorAll('.drag-over').forEach((item) => item.classList.remove('drag-over'));
    draggedLink = null;
  });
});

function updateClock() {
  const node = document.querySelector('[data-clock]');
  if (!node) return;

  const now = new Date();
  node.querySelector('span').textContent = now.toLocaleDateString(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  });
  node.querySelector('strong').textContent = now.toLocaleTimeString(undefined, {
    hour: 'numeric',
    minute: '2-digit'
  });
}

function formatRelativeDate(value) {
  if (!value) return 'unknown';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const diff = Date.now() - date.getTime();
  const days = Math.max(0, Math.round(diff / 86400000));
  if (days === 0) return 'today';
  if (days === 1) return 'yesterday';
  return `${days} days ago`;
}

function renderClock(widget, body) {
  const saved = localStorage.getItem(`dockmark-clock-${widget.id}`);
  const style = saved || widget.style || 'retro';
  const now = new Date();
  body.innerHTML = `
    <div class="clock-face ${style}">
      <span class="clock-time">${now.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}</span>
      <span class="clock-date">${now.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })}</span>
    </div>
  `;
}

async function renderWeather(widget, body) {
  const response = await fetch(`widgets.php?type=weather&lat=${encodeURIComponent(widget.lat)}&lon=${encodeURIComponent(widget.lon)}`);
  const result = await response.json();
  if (!result.ok) throw new Error(result.message || 'Weather unavailable.');
  const data = result.data || {};
  body.innerHTML = `
    <div class="weather-reading">
      <span class="weather-temp">${Math.round(data.temperature_2m ?? 0)}&deg;</span>
      <span>${widget.label || 'Local'} feels like ${Math.round(data.apparent_temperature ?? data.temperature_2m ?? 0)}&deg;</span>
    </div>
    <div class="metric-row"><span>Humidity</span><strong>${data.relative_humidity_2m ?? '-'}%</strong></div>
    <div class="metric-row"><span>Wind</span><strong>${Math.round(data.wind_speed_10m ?? 0)} mph</strong></div>
  `;
}

async function renderGithub(widget, body) {
  const response = await fetch(`widgets.php?type=github&repo=${encodeURIComponent(widget.repo)}`);
  const result = await response.json();
  if (!result.ok) throw new Error(result.message || 'GitHub unavailable.');
  const data = result.data || {};
  body.innerHTML = `
    <div class="metric-row"><span>${data.name}</span><strong>${data.latestRelease}</strong></div>
    <div class="metric-row"><span>Stars</span><strong>${Number(data.stars || 0).toLocaleString()}</strong></div>
    <div class="metric-row"><span>Forks</span><strong>${Number(data.forks || 0).toLocaleString()}</strong></div>
    <div class="metric-row"><span>Updated</span><strong>${formatRelativeDate(data.pushedAt)}</strong></div>
  `;
}

async function renderRss(widget, body) {
  const response = await fetch(`widgets.php?type=rss&url=${encodeURIComponent(widget.url)}`);
  const result = await response.json();
  if (!result.ok) throw new Error(result.message || 'Feed unavailable.');
  const items = result.data || [];
  body.innerHTML = `
    <div class="feed-list">
      ${items.map((item) => `<a href="${item.url}" target="_blank" rel="noopener">${item.title}</a>`).join('') || '<span>No feed items found.</span>'}
    </div>
  `;
}

function bootWidgets() {
  document.querySelectorAll('[data-widget]').forEach((card) => {
    const body = card.querySelector('[data-widget-body]');
    const widget = JSON.parse(card.dataset.widget || '{}');

    const render = async () => {
      try {
        if (widget.type === 'clock') renderClock(widget, body);
        if (widget.type === 'weather') await renderWeather(widget, body);
        if (widget.type === 'github') await renderGithub(widget, body);
        if (widget.type === 'rss') await renderRss(widget, body);
      } catch (error) {
        body.innerHTML = `<span class="widget-loading">${error.message || 'Widget failed.'}</span>`;
      }
    };

    card.querySelector('[data-clock-style]')?.addEventListener('click', () => {
      const current = localStorage.getItem(`dockmark-clock-${widget.id}`) || widget.style || 'retro';
      const next = clockStyles[(clockStyles.indexOf(current) + 1) % clockStyles.length];
      localStorage.setItem(`dockmark-clock-${widget.id}`, next);
      renderClock(widget, body);
    });

    render();
    if (widget.type === 'clock') {
      window.setInterval(() => renderClock(widget, body), 30000);
    }
  });
}

updateClock();
window.setInterval(updateClock, 30000);
bootWidgets();

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  });
}
