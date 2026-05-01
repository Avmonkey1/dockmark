<?php
declare(strict_types=1);

require __DIR__ . '/lib.php';

session_start();

$loginError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (hash_equals(lodgeboard_admin_password(), (string) ($_POST['password'] ?? ''))) {
        $_SESSION['lodgeboard_admin'] = true;
        header('Location: index.php');
        exit;
    }
    $loginError = 'That password did not match.';
}

if (($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['lodgeboard_admin']);
    header('Location: index.php');
    exit;
}

$data = lodgeboard_read();
$settings = $data['settings'] ?? [];
$activePage = $_GET['page'] ?? ($settings['homePage'] ?? 'Home');
$pages = $data['pages'] ?? [];
$groups = array_values(array_filter($data['groups'] ?? [], fn ($group) => ($group['page'] ?? '') === $activePage));
$notes = $data['notes'] ?? [];
$widgets = array_values(array_filter($data['widgets'] ?? [], fn ($widget) => ($widget['page'] ?? '') === $activePage || ($widget['page'] ?? '') === '*'));
$adminConfigured = lodgeboard_admin_is_configured();
$adminAuthed = lodgeboard_admin_is_authenticated();
$appName = (string) ($settings['appName'] ?? 'Dockmark');
$brandLetter = strtoupper(substr($appName, 0, 1));
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($appName) ?></title>
  <meta name="description" content="A self-hosted visual start page and dashboard config builder.">
  <meta name="theme-color" content="#0d0d0b">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="icon" href="assets/icon.svg" type="image/svg+xml">
  <script>
    const requestedTheme = new URLSearchParams(location.search).get('theme');
    const savedTheme = requestedTheme || localStorage.getItem('dockmark-theme') || 'dark';
    document.documentElement.dataset.theme = savedTheme === 'light' ? 'light' : 'dark';
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="app-shell" data-active-page="<?= htmlspecialchars((string) $activePage) ?>">
    <aside class="sidebar">
      <a class="brand" href="index.php" aria-label="<?= htmlspecialchars($appName) ?> home">
        <span class="brand-mark"><?= htmlspecialchars($brandLetter) ?></span>
        <span>
          <strong><?= htmlspecialchars($appName) ?></strong>
          <small><?= htmlspecialchars((string) ($settings['tagline'] ?? 'Private start page')) ?></small>
        </span>
      </a>

      <nav class="page-nav" aria-label="Dashboard pages">
        <?php foreach ($pages as $page): ?>
          <?php $isActive = ($page['name'] ?? '') === $activePage; ?>
          <a class="<?= $isActive ? 'active' : '' ?>" href="?page=<?= urlencode((string) $page['name']) ?>" draggable="<?= $adminAuthed ? 'true' : 'false' ?>" data-page-name="<?= htmlspecialchars((string) $page['name']) ?>">
            <span><?= htmlspecialchars((string) $page['name']) ?></span>
            <small><?= htmlspecialchars((string) ($page['description'] ?? '')) ?></small>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar-footer">
        <a href="export.php?format=glance">Glance YAML</a>
        <a href="export.php?format=homepage">Homepage YAML</a>
        <a href="export.php?format=startme_csv">Start.me CSV</a>
        <a href="export.php?format=json">JSON Backup</a>
      </div>
    </aside>

    <main class="workspace">
      <header class="topbar">
        <button class="icon-button menu-toggle" type="button" aria-label="Open pages" data-menu-toggle>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <label class="search">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
          <input type="search" placeholder="Search links, notes, URLs..." data-search>
        </label>
        <div class="time-card" data-clock>
          <span><?= date('D, M j') ?></span>
          <strong><?= date('g:i A') ?></strong>
        </div>
        <button class="icon-button theme-toggle" type="button" aria-label="Toggle color theme" data-theme-toggle>
          <svg class="sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 15.5A8.5 8.5 0 0 1 8.5 4a7 7 0 1 0 11.5 11.5Z"/></svg>
        </button>
        <button class="button primary" type="button" data-open-admin>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
          <?= $adminAuthed ? 'Add Link' : 'Admin Login' ?>
        </button>
      </header>

      <section class="overview">
        <div>
          <p class="section-label">Private Command Page</p>
          <h1><?= htmlspecialchars((string) $activePage) ?></h1>
          <p><?= htmlspecialchars((string) (($pages[array_search($activePage, array_column($pages, 'name'))]['description'] ?? null) ?: 'A focused start page for the links that matter right now.')) ?></p>
        </div>
        <div class="export-strip" aria-label="Export dashboard">
          <a href="export.php?format=glance">Export Glance</a>
          <a href="export.php?format=homepage">Export Homepage</a>
          <a href="export.php?format=startme_csv">Export Start.me</a>
          <a href="export.php?format=json">Backup JSON</a>
        </div>
      </section>

      <section class="content-grid">
        <div class="bookmark-stack" data-bookmark-stack>
          <?php if (!$groups): ?>
            <article class="empty-state">
              <h2>No groups yet</h2>
              <p>Add the first bookmark for this page and <?= htmlspecialchars($appName) ?> will create a group for it.</p>
              <button class="button primary" type="button" data-open-admin>Add the first link</button>
            </article>
          <?php endif; ?>

          <?php foreach ($groups as $group): ?>
            <article class="link-group" draggable="<?= $adminAuthed ? 'true' : 'false' ?>" data-group-id="<?= htmlspecialchars((string) ($group['id'] ?? '')) ?>" style="--group-color: <?= htmlspecialchars((string) ($group['color'] ?? '#c6a35b')) ?>">
              <header>
                <span></span>
                <div>
                  <h2><?= htmlspecialchars((string) $group['title']) ?></h2>
                  <p><?= htmlspecialchars((string) ($group['description'] ?? '')) ?></p>
                </div>
                <?php if ($adminAuthed): ?>
                  <button class="drag-handle" type="button" aria-label="Drag group" title="Drag group">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5h.01M15 5h.01M9 12h.01M15 12h.01M9 19h.01M15 19h.01"/></svg>
                  </button>
                <?php endif; ?>
              </header>
              <div class="link-list">
                <?php foreach (($group['links'] ?? []) as $link): ?>
                  <?php
                    $url = (string) ($link['url'] ?? '');
                    $displayUrl = lodgeboard_display_url($url, (string) ($link['googleAccount'] ?? ''));
                    $tags = array_values(array_filter($link['tags'] ?? []));
                    $searchText = trim(($link['title'] ?? '') . ' ' . ($link['note'] ?? '') . ' ' . $url . ' ' . implode(' ', $tags));
                  ?>
                  <a class="link-row" href="<?= htmlspecialchars($displayUrl) ?>" target="_blank" rel="noopener" draggable="<?= $adminAuthed ? 'true' : 'false' ?>" data-link-id="<?= htmlspecialchars((string) ($link['id'] ?? '')) ?>" data-search-item data-search-text="<?= htmlspecialchars($searchText) ?>">
                    <span class="link-icon">
                      <?php if (lodgeboard_favicon_url($url)): ?>
                        <img src="<?= htmlspecialchars(lodgeboard_favicon_url($url)) ?>" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.hidden=true">
                      <?php endif; ?>
                      <span class="link-icon-fallback"><?= lodgeboard_icon((string) ($link['icon'] ?? 'link')) ?></span>
                    </span>
                    <span class="link-copy">
                      <strong><?= htmlspecialchars((string) $link['title']) ?></strong>
                      <small><?= htmlspecialchars((string) ($link['note'] ?? parse_url((string) $link['url'], PHP_URL_HOST))) ?></small>
                      <?php if ($tags): ?>
                        <span class="tag-row">
                          <?php if (!empty($link['googleAccount'])): ?>
                            <em>google <?= htmlspecialchars((string) $link['googleAccount']) ?></em>
                          <?php endif; ?>
                          <?php foreach ($tags as $tag): ?>
                            <em>#<?= htmlspecialchars((string) $tag) ?></em>
                          <?php endforeach; ?>
                        </span>
                      <?php endif; ?>
                    </span>
                    <span class="link-arrow">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>
                    </span>
                  </a>
                <?php endforeach; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <aside class="side-panel">
          <?php if ($widgets): ?>
          <section class="panel-block">
            <h2>Widgets</h2>
            <div class="widget-stack">
              <?php foreach ($widgets as $widget): ?>
                <article class="widget-card widget-<?= htmlspecialchars((string) ($widget['type'] ?? 'custom')) ?>" data-widget='<?= htmlspecialchars(json_encode($widget, JSON_UNESCAPED_SLASHES) ?: '{}', ENT_QUOTES) ?>'>
                  <header>
                    <strong><?= htmlspecialchars((string) ($widget['title'] ?? 'Widget')) ?></strong>
                    <?php if (($widget['type'] ?? '') === 'clock'): ?>
                      <button type="button" data-clock-style title="Change clock style">Style</button>
                    <?php endif; ?>
                  </header>
                  <div class="widget-body" data-widget-body>
                    <span class="widget-loading">Loading...</span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <section class="panel-block">
            <h2>Notes</h2>
            <?php foreach ($notes as $note): ?>
              <article class="note <?= htmlspecialchars((string) ($note['tone'] ?? 'gold')) ?>">
                <strong><?= htmlspecialchars((string) $note['title']) ?></strong>
                <p><?= htmlspecialchars((string) $note['body']) ?></p>
              </article>
            <?php endforeach; ?>
          </section>

          <section class="panel-block">
            <h2>Pages</h2>
            <div class="page-counts">
              <?php foreach ($pages as $page): ?>
                <?php
                  $count = 0;
                  foreach ($data['groups'] ?? [] as $group) {
                      if (($group['page'] ?? '') === ($page['name'] ?? '')) {
                          $count += count($group['links'] ?? []);
                      }
                  }
                ?>
                <a href="?page=<?= urlencode((string) $page['name']) ?>">
                  <span><?= htmlspecialchars((string) $page['name']) ?></span>
                  <strong><?= $count ?></strong>
                </a>
              <?php endforeach; ?>
            </div>
          </section>
        </aside>
      </section>
    </main>
  </div>

  <div class="drawer-backdrop" data-close-admin hidden></div>
  <aside class="admin-drawer" data-admin-drawer aria-hidden="true">
    <header>
      <div>
        <p class="section-label">Builder</p>
        <h2><?= $adminAuthed ? 'Add a bookmark' : 'Admin login' ?></h2>
      </div>
      <button class="icon-button" type="button" aria-label="Close admin" data-close-admin>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>

    <?php if (!$adminConfigured): ?>
      <div class="deploy-warning">
        <strong>Local admin is unlocked.</strong>
        <p>Before this goes public, copy <code>config.example.php</code> to <code>config.php</code> and set <code>LODGEBOARD_ADMIN_PASSWORD</code>.</p>
      </div>
    <?php endif; ?>

    <?php if (!$adminAuthed): ?>
    <form method="post" class="login-form">
      <input type="hidden" name="action" value="login">
      <label>
        <span>Password</span>
        <input name="password" type="password" required autocomplete="current-password">
      </label>
      <button class="button primary full" type="submit">Unlock admin</button>
      <?php if ($loginError): ?>
        <p class="form-status error"><?= htmlspecialchars($loginError) ?></p>
      <?php endif; ?>
    </form>
    <?php else: ?>
    <div class="drawer-tabs" role="tablist" aria-label="Admin tools">
      <button class="active" type="button" data-drawer-tab="add">Add</button>
      <button type="button" data-drawer-tab="import">Import</button>
      <button type="button" data-drawer-tab="backup">Backup</button>
    </div>

    <section class="drawer-panel active" data-drawer-panel="add">
    <form data-add-form>
      <label>
        <span>Title</span>
        <input name="title" required placeholder="OpenAI Docs">
      </label>
      <label>
        <span>URL</span>
        <input name="url" required placeholder="https://example.com">
      </label>
      <label>
        <span>Page</span>
        <select name="page">
          <?php foreach ($pages as $page): ?>
            <option <?= ($page['name'] ?? '') === $activePage ? 'selected' : '' ?>><?= htmlspecialchars((string) $page['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>Group</span>
        <input name="group" list="groups" placeholder="Daily Desk" value="<?= htmlspecialchars((string) ($groups[0]['title'] ?? 'Inbox')) ?>">
        <datalist id="groups">
          <?php foreach ($data['groups'] ?? [] as $group): ?>
            <option value="<?= htmlspecialchars((string) $group['title']) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </label>
      <label>
        <span>Note</span>
        <textarea name="note" rows="3" placeholder="What this link is for"></textarea>
      </label>
      <label>
        <span>Tags</span>
        <input name="tags" placeholder="ai, daily, client">
      </label>
      <label>
        <span>Google account slot</span>
        <input name="googleAccount" placeholder="0, 1, 2, or email">
      </label>
      <p class="helper-copy">For Google links, Dockmark can append account hints like <code>authuser=1</code> or <code>/u/1/</code>. This chooses a signed-in Google account, not a full Chrome browser profile.</p>
      <div class="form-row">
        <label>
          <span>Icon</span>
          <select name="icon">
            <option value="link">Link</option>
            <option value="spark">Spark</option>
            <option value="globe">Globe</option>
            <option value="code">Code</option>
            <option value="server">Server</option>
            <option value="shield">Shield</option>
            <option value="mail">Mail</option>
            <option value="calendar">Calendar</option>
          </select>
        </label>
        <label>
          <span>Accent</span>
          <input name="color" type="color" value="<?= htmlspecialchars((string) ($settings['accent'] ?? '#c6a35b')) ?>">
        </label>
      </div>
      <button class="button primary full" type="submit">Save bookmark</button>
      <p class="form-status" data-form-status></p>
    </form>
    </section>

    <section class="drawer-panel" data-drawer-panel="import">
      <form data-import-form>
        <label>
          <span>Import format</span>
          <select name="format">
            <option value="html">Browser / Start.me HTML</option>
            <option value="startme_csv">Start.me CSV</option>
            <option value="homepage_yaml">Homepage YAML</option>
            <option value="glance_yaml">Glance YAML</option>
          </select>
        </label>
        <label>
          <span>Import file</span>
          <input name="bookmark_file" type="file" accept=".html,.htm,.csv,.yaml,.yml,.txt,text/html,text/csv,text/yaml">
        </label>
        <label>
          <span>Page</span>
          <select name="page">
            <?php foreach ($pages as $page): ?>
              <option <?= ($page['name'] ?? '') === $activePage ? 'selected' : '' ?>><?= htmlspecialchars((string) $page['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>Group</span>
          <input name="group" value="Imported">
        </label>
        <button class="button primary full" type="submit">Import bookmarks</button>
        <p class="helper-copy">Import browser/Start.me HTML, Start.me CSV, Homepage YAML, or Glance YAML. Dockmark skips duplicate URLs.</p>
        <p class="form-status" data-import-status></p>
      </form>
    </section>

    <section class="drawer-panel" data-drawer-panel="backup">
      <form data-restore-form>
        <label>
          <span>Restore JSON backup</span>
          <input name="restore_file" type="file" accept=".json,application/json">
        </label>
        <button class="button primary full" type="submit">Restore backup</button>
        <p class="helper-copy">This replaces the current dashboard data with the selected Dockmark JSON backup.</p>
        <p class="form-status" data-restore-status></p>
      </form>
    </section>
    <?php endif; ?>

    <div class="drawer-exports">
      <h3>Export config</h3>
      <a href="export.php?format=glance">Download Glance YAML</a>
      <a href="export.php?format=homepage">Download Homepage YAML</a>
      <a href="export.php?format=startme_csv">Download Start.me CSV</a>
      <a href="export.php?format=bookmarks_html">Download bookmark HTML</a>
      <a href="export.php?format=json">Download JSON backup</a>
      <?php if ($adminAuthed): ?>
        <button class="button full" type="button" data-save-order>Save current order</button>
        <p class="form-status" data-order-status></p>
      <?php endif; ?>
      <?php if ($adminConfigured && $adminAuthed): ?>
        <a href="?action=logout">Lock admin</a>
      <?php endif; ?>
    </div>
  </aside>

  <script src="assets/app.js" defer></script>
</body>
</html>
