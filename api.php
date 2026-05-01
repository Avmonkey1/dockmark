<?php
declare(strict_types=1);

require __DIR__ . '/lib.php';

session_start();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        echo json_encode(lodgeboard_read());
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload.');
    }

    $action = (string) ($payload['action'] ?? '');

    if (!lodgeboard_admin_is_authenticated()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Admin login is required.']);
        exit;
    }

    $data = lodgeboard_read();

    if ($action === 'save') {
        if (!isset($payload['data']) || !is_array($payload['data'])) {
            throw new RuntimeException('Missing dashboard data.');
        }

        lodgeboard_write($payload['data']);
        echo json_encode(['ok' => true, 'data' => lodgeboard_read()]);
        exit;
    }

    if ($action === 'restore_json') {
        if (!isset($payload['data']) || !is_array($payload['data'])) {
            throw new RuntimeException('Missing restore data.');
        }

        $restore = $payload['data'];
        foreach (['settings', 'pages', 'groups', 'notes'] as $key) {
            if (!array_key_exists($key, $restore)) {
                throw new RuntimeException('Backup is missing ' . $key . '.');
            }
        }

        lodgeboard_write($restore);
        echo json_encode(['ok' => true, 'data' => lodgeboard_read()]);
        exit;
    }

    if ($action === 'add_link') {
        $page = trim((string) ($payload['page'] ?? 'Home'));
        $groupTitle = trim((string) ($payload['group'] ?? 'Inbox'));
        $title = trim((string) ($payload['title'] ?? ''));
        $url = lodgeboard_normalize_url((string) ($payload['url'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));
        $icon = trim((string) ($payload['icon'] ?? 'link'));
        $color = trim((string) ($payload['color'] ?? '#c6a35b'));
        $tags = lodgeboard_tags_from_string((string) ($payload['tags'] ?? ''));
        $googleAccount = trim((string) ($payload['googleAccount'] ?? ''));

        if ($title === '' || $url === '') {
            throw new RuntimeException('Title and URL are required.');
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
                'id' => lodgeboard_slug($groupTitle),
                'page' => $page,
                'title' => $groupTitle,
                'description' => 'New collection',
                'color' => $color,
                'links' => []
            ];
            $groupIndex = array_key_last($data['groups']);
        }

        $existingIds = [];
        foreach ($data['groups'] as $group) {
            foreach (($group['links'] ?? []) as $link) {
                $existingIds[] = (string) ($link['id'] ?? '');
            }
        }

        $data['groups'][$groupIndex]['links'][] = [
            'id' => lodgeboard_unique_id($title, $existingIds),
            'title' => $title,
            'url' => $url,
            'note' => $note,
            'icon' => $icon,
            'googleAccount' => $googleAccount,
            'tags' => $tags
        ];

        lodgeboard_write($data);
        echo json_encode(['ok' => true, 'data' => lodgeboard_read()]);
        exit;
    }

    if ($action === 'import_bookmarks') {
        $page = trim((string) ($payload['page'] ?? 'Home'));
        $groupTitle = trim((string) ($payload['group'] ?? 'Imported'));
        $format = trim((string) ($payload['format'] ?? 'html'));
        $content = (string) ($payload['content'] ?? ($payload['html'] ?? ''));

        $imported = match ($format) {
            'startme_csv' => lodgeboard_import_startme_csv($content, $page, $groupTitle),
            'homepage_yaml' => lodgeboard_import_homepage_yaml($content, $page, $groupTitle),
            'glance_yaml' => lodgeboard_import_glance_yaml($content, $page, $groupTitle),
            default => lodgeboard_import_bookmarks_html($content, $page, $groupTitle),
        };

        if (!$imported) {
            throw new RuntimeException('No bookmarks were found in that file.');
        }

        [$data, $added] = lodgeboard_apply_import($data, $imported, $page, $groupTitle);

        lodgeboard_write($data);
        echo json_encode(['ok' => true, 'added' => $added, 'data' => lodgeboard_read()]);
        exit;
    }

    if ($action === 'save_order') {
        $pageIds = $payload['pageIds'] ?? [];
        $groupIds = $payload['groupIds'] ?? [];
        $linksByGroup = $payload['linksByGroup'] ?? [];

        if (!is_array($pageIds) || !is_array($groupIds) || !is_array($linksByGroup)) {
            throw new RuntimeException('Invalid order payload.');
        }

        if ($pageIds) {
            $pagesByName = [];
            foreach ($data['pages'] as $page) {
                $pagesByName[(string) ($page['name'] ?? '')] = $page;
            }

            $orderedPages = [];
            foreach ($pageIds as $pageName) {
                $pageName = (string) $pageName;
                if (isset($pagesByName[$pageName])) {
                    $orderedPages[] = $pagesByName[$pageName];
                    unset($pagesByName[$pageName]);
                }
            }

            $data['pages'] = array_merge($orderedPages, array_values($pagesByName));
        }

        $groupsById = [];
        foreach ($data['groups'] as $group) {
            $groupsById[(string) ($group['id'] ?? '')] = $group;
        }

        $orderedGroups = [];
        foreach ($groupIds as $groupId) {
            $groupId = (string) $groupId;
            if (!isset($groupsById[$groupId])) {
                continue;
            }

            $group = $groupsById[$groupId];
            $linksById = [];
            foreach (($group['links'] ?? []) as $link) {
                $linksById[(string) ($link['id'] ?? '')] = $link;
            }

            $orderedLinks = [];
            foreach (($linksByGroup[$groupId] ?? []) as $linkId) {
                $linkId = (string) $linkId;
                if (isset($linksById[$linkId])) {
                    $orderedLinks[] = $linksById[$linkId];
                    unset($linksById[$linkId]);
                }
            }

            $group['links'] = array_merge($orderedLinks, array_values($linksById));
            $orderedGroups[] = $group;
            unset($groupsById[$groupId]);
        }

        $data['groups'] = array_merge($orderedGroups, array_values($groupsById));
        lodgeboard_write($data);
        echo json_encode(['ok' => true, 'data' => lodgeboard_read()]);
        exit;
    }

    throw new RuntimeException('Unknown action.');
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
}
