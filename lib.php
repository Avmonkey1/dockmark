<?php
declare(strict_types=1);

const LODGEBOARD_DATA = __DIR__ . '/data/bookmarks.json';
const LODGEBOARD_PROJECTS = __DIR__ . '/data/projects.json';

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

function lodgeboard_read(): array
{
    if (!file_exists(LODGEBOARD_DATA)) {
        return ['settings' => [], 'pages' => [], 'groups' => [], 'notes' => []];
    }

    $json = file_get_contents(LODGEBOARD_DATA);
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        return ['settings' => [], 'pages' => [], 'groups' => [], 'notes' => []];
    }

    return $data;
}

function lodgeboard_read_projects(): array
{
    if (!file_exists(LODGEBOARD_PROJECTS)) {
        return ['projects' => []];
    }

    $json = file_get_contents(LODGEBOARD_PROJECTS);
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        return ['projects' => []];
    }

    return $data;
}

function lodgeboard_write(array $data): void
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Could not encode dashboard data.');
    }

    $tmp = LODGEBOARD_DATA . '.tmp';
    file_put_contents($tmp, $encoded . PHP_EOL, LOCK_EX);
    rename($tmp, LODGEBOARD_DATA);
}

function lodgeboard_normalize_url(string $url): string
{
    $url = trim($url);
    if ($url !== '' && !preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    return $url;
}

function lodgeboard_slug(string $value): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? ''));
    return trim($slug, '-') ?: 'item-' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function lodgeboard_unique_id(string $value, array $existingIds): string
{
    $base = lodgeboard_slug($value);
    $id = $base;
    $index = 2;

    while (in_array($id, $existingIds, true)) {
        $id = $base . '-' . $index;
        $index++;
    }

    return $id;
}

function lodgeboard_tags_from_string(string $value): array
{
    $tags = array_filter(array_map('trim', preg_split('/[,#]+/', $value) ?: []));
    $clean = [];
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if ($tag !== '' && !in_array($tag, $clean, true)) {
            $clean[] = $tag;
        }
    }

    return array_values($clean);
}

function lodgeboard_display_url(string $url, string $googleAccount = ''): string
{
    $url = lodgeboard_normalize_url($url);
    $account = trim($googleAccount);
    if ($account === '' || !preg_match('/(^\d+$)|(^[^@\s]+@[^@\s]+$)/', $account)) {
        return $url;
    }

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (!str_ends_with($host, 'google.com') && !str_ends_with($host, 'youtube.com')) {
        return $url;
    }

    $parts = parse_url($url);
    $scheme = $parts['scheme'] ?? 'https';
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['authuser'] = $account;

    $servicesWithUserPath = [
        'mail.google.com',
        'drive.google.com',
        'calendar.google.com',
        'keep.google.com',
        'docs.google.com',
        'sheets.google.com',
        'slides.google.com',
        'forms.google.com',
        'classroom.google.com',
        'www.youtube.com',
        'youtube.com'
    ];

    if (in_array($host, $servicesWithUserPath, true) && !preg_match('#/u/[^/]+#', $path)) {
        if ($host === 'mail.google.com') {
            $path = '/mail/u/' . rawurlencode($account) . '/';
        } elseif ($path === '/' || $path === '') {
            $path = '/u/' . rawurlencode($account) . '/';
        }
    }

    $rebuilt = $scheme . '://' . ($parts['host'] ?? $host) . $path;
    if (!empty($query)) {
        $rebuilt .= '?' . http_build_query($query);
    }
    if (!empty($parts['fragment'])) {
        $rebuilt .= '#' . $parts['fragment'];
    }

    return $rebuilt;
}

function lodgeboard_favicon_url(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return '';
    }

    return 'https://www.google.com/s2/favicons?sz=64&domain_url=' . rawurlencode($url);
}

function lodgeboard_yaml_string(string $value): string
{
    $escaped = str_replace("'", "''", $value);
    return "'" . $escaped . "'";
}

function lodgeboard_icon(string $icon): string
{
    $icons = [
        'calendar' => '<path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>',
        'code' => '<path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'mail' => '<path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/>',
        'server' => '<rect x="4" y="4" width="16" height="6" rx="2"/><rect x="4" y="14" width="16" height="6" rx="2"/><path d="M8 7h.01M8 17h.01"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.2 2.8 7.7 7 10 4.2-2.3 7-5.8 7-10V6z"/>',
        'spark' => '<path d="M12 2l1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8z"/><path d="M5 17l1 3 3 1-3 1-1 3-1-3-3-1 3-1z"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/>'
    ];

    $path = $icons[$icon] ?? $icons['link'];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function lodgeboard_import_bookmarks_html(string $html, string $page, string $groupTitle): array
{
    $links = [];
    if (trim($html) === '') {
        return $links;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
    libxml_clear_errors();

    if (!$loaded) {
        return $links;
    }

    foreach ($dom->getElementsByTagName('a') as $anchor) {
        $url = lodgeboard_normalize_url($anchor->getAttribute('href'));
        $title = trim($anchor->textContent);

        if ($title === '' || $url === '' || !preg_match('/^https?:\/\//i', $url)) {
            continue;
        }

        $tags = [];
        $folders = [];
        $node = $anchor->parentNode;
        while ($node) {
            if (strtolower($node->nodeName) === 'dl') {
                $previous = $node->previousSibling;
                while ($previous && trim($previous->textContent) === '') {
                    $previous = $previous->previousSibling;
                }
                if ($previous && strtolower($previous->nodeName) === 'h3') {
                    $folders[] = trim($previous->textContent);
                }
            }
            $node = $node->parentNode;
        }

        $folders = array_values(array_filter(array_reverse($folders)));
        if ($folders) {
            $tags[] = end($folders);
        }

        $links[] = [
            'id' => lodgeboard_slug($title),
            'title' => $title,
            'url' => $url,
            'note' => $folders ? implode(' / ', $folders) : 'Imported bookmark',
            'icon' => 'link',
            'tags' => array_values(array_unique($tags)),
            'page' => $page,
            'group' => $groupTitle
        ];
    }

    return $links;
}

function lodgeboard_link_from_import(string $title, string $url, string $note, string $page, string $group, array $tags = []): ?array
{
    $title = trim($title);
    $url = lodgeboard_normalize_url($url);
    if ($title === '' || $url === '' || !preg_match('/^https?:\/\//i', $url)) {
        return null;
    }

    return [
        'id' => lodgeboard_slug($title),
        'title' => $title,
        'url' => $url,
        'note' => trim($note) ?: 'Imported bookmark',
        'icon' => 'link',
        'tags' => array_values(array_unique($tags)),
        'page' => $page,
        'group' => $group
    ];
}

function lodgeboard_import_startme_csv(string $csv, string $page, string $fallbackGroup): array
{
    $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($csv)) ?: []);
    if (!$rows || count($rows) < 2) {
        return [];
    }

    $headers = array_map(fn ($header) => strtolower(trim((string) $header)), array_shift($rows));
    $links = [];
    foreach ($rows as $row) {
        if (!array_filter($row)) {
            continue;
        }

        $record = [];
        foreach ($headers as $index => $header) {
            $record[$header] = trim((string) ($row[$index] ?? ''));
        }

        $group = $record['group'] ?? $record['widget'] ?? $fallbackGroup;
        $link = lodgeboard_link_from_import(
            $record['title'] ?? ($record['url'] ?? ''),
            $record['url'] ?? '',
            $record['description'] ?? '',
            $page,
            $group ?: $fallbackGroup,
            ['startme']
        );

        if ($link) {
            $links[] = $link;
        }
    }

    return $links;
}

function lodgeboard_import_homepage_yaml(string $yaml, string $page, string $fallbackGroup): array
{
    $lines = preg_split('/\r\n|\r|\n/', $yaml) ?: [];
    $links = [];
    $currentGroup = $fallbackGroup;
    $currentTitle = '';
    $currentUrl = '';
    $currentNote = '';

    $flush = function () use (&$links, &$currentTitle, &$currentUrl, &$currentNote, &$currentGroup, $page) {
        $link = lodgeboard_link_from_import($currentTitle, $currentUrl, $currentNote, $page, $currentGroup, ['homepage']);
        if ($link) {
            $links[] = $link;
        }
        $currentTitle = '';
        $currentUrl = '';
        $currentNote = '';
    };

    foreach ($lines as $line) {
        if (preg_match('/^-\s+[\'"]?(.+?)[\'"]?:\s*$/', $line, $match)) {
            $flush();
            $currentGroup = trim($match[1], "'\"") ?: $fallbackGroup;
            continue;
        }

        if (preg_match('/^\s+-\s+[\'"]?(.+?)[\'"]?:\s*$/', $line, $match)) {
            $flush();
            $currentTitle = trim($match[1], "'\"");
            continue;
        }

        if (preg_match('/href:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $match)) {
            $currentUrl = trim($match[1], "'\"");
            continue;
        }

        if (preg_match('/description:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $match)) {
            $currentNote = trim($match[1], "'\"");
        }
    }

    $flush();
    return $links;
}

function lodgeboard_import_glance_yaml(string $yaml, string $fallbackPage, string $fallbackGroup): array
{
    $lines = preg_split('/\r\n|\r|\n/', $yaml) ?: [];
    $links = [];
    $currentPage = $fallbackPage;
    $currentGroup = $fallbackGroup;
    $currentTitle = '';
    $currentUrl = '';

    $flush = function () use (&$links, &$currentTitle, &$currentUrl, &$currentGroup, &$currentPage) {
        $link = lodgeboard_link_from_import($currentTitle, $currentUrl, '', $currentPage, $currentGroup, ['glance']);
        if ($link) {
            $links[] = $link;
        }
        $currentTitle = '';
        $currentUrl = '';
    };

    foreach ($lines as $line) {
        if (preg_match('/^\s*-\s+name:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $match)) {
            $currentPage = trim($match[1], "'\"") ?: $fallbackPage;
            continue;
        }

        if (preg_match('/^\s*title:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $match)) {
            $value = trim($match[1], "'\"");
            if ($currentTitle !== '') {
                $flush();
            }
            if ($currentGroup === $fallbackGroup && $currentUrl === '') {
                $currentGroup = $value;
            } else {
                $currentTitle = $value;
            }
            continue;
        }

        if (preg_match('/^\s*-\s+title:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $match)) {
            $flush();
            $currentTitle = trim($match[1], "'\"");
            continue;
        }

        if (preg_match('/^\s*url:\s*[\'"]?(.+?)[\'"]?\s*$/', $line, $match)) {
            $currentUrl = trim($match[1], "'\"");
        }
    }

    $flush();
    return $links;
}

function lodgeboard_apply_import(array $data, array $imported, string $fallbackPage, string $fallbackGroup): array
{
    $existingUrls = [];
    $existingIds = [];
    foreach ($data['groups'] as $group) {
        foreach (($group['links'] ?? []) as $link) {
            $existingUrls[] = (string) ($link['url'] ?? '');
            $existingIds[] = (string) ($link['id'] ?? '');
        }
    }

    $added = 0;
    foreach ($imported as $link) {
        $page = (string) ($link['page'] ?? $fallbackPage);
        $groupTitle = (string) ($link['group'] ?? $fallbackGroup);
        if (in_array($link['url'], $existingUrls, true)) {
            continue;
        }

        $pageExists = false;
        foreach ($data['pages'] as $existingPage) {
            if (($existingPage['name'] ?? '') === $page) {
                $pageExists = true;
                break;
            }
        }
        if (!$pageExists) {
            $data['pages'][] = ['name' => $page, 'description' => 'Imported page'];
        }

        $groupIndex = null;
        foreach ($data['groups'] as $index => $group) {
            if (($group['page'] ?? '') === $page && strcasecmp((string) ($group['title'] ?? ''), $groupTitle) === 0) {
                $groupIndex = $index;
                break;
            }
        }

        if ($groupIndex === null) {
            $data['groups'][] = [
                'id' => lodgeboard_unique_id($groupTitle, array_map(fn ($group) => (string) ($group['id'] ?? ''), $data['groups'])),
                'page' => $page,
                'title' => $groupTitle,
                'description' => 'Imported collection',
                'color' => '#9bb7b0',
                'links' => []
            ];
            $groupIndex = array_key_last($data['groups']);
        }

        $link['id'] = lodgeboard_unique_id((string) $link['title'], $existingIds);
        unset($link['page'], $link['group']);
        $existingIds[] = $link['id'];
        $existingUrls[] = $link['url'];
        $data['groups'][$groupIndex]['links'][] = $link;
        $added++;
    }

    return [$data, $added];
}

function lodgeboard_admin_password(): string
{
    if (defined('LODGEBOARD_ADMIN_PASSWORD')) {
        return (string) constant('LODGEBOARD_ADMIN_PASSWORD');
    }

    return trim((string) getenv('LODGEBOARD_ADMIN_PASSWORD'));
}

function lodgeboard_admin_is_configured(): bool
{
    return lodgeboard_admin_password() !== '';
}

function lodgeboard_admin_is_authenticated(): bool
{
    if (!lodgeboard_admin_is_configured()) {
        return true;
    }

    return !empty($_SESSION['lodgeboard_admin']);
}
