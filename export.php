<?php
declare(strict_types=1);

require __DIR__ . '/lib.php';

$format = strtolower((string) ($_GET['format'] ?? 'json'));
$data = lodgeboard_read();

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="lodgeboard.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($format === 'startme_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="dockmark-startme.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['title', 'URL', 'description', 'widget', 'group']);
    foreach ($data['groups'] as $group) {
        foreach (($group['links'] ?? []) as $link) {
            fputcsv($out, [
                (string) ($link['title'] ?? ''),
                (string) ($link['url'] ?? ''),
                (string) ($link['note'] ?? ''),
                (string) ($group['title'] ?? ''),
                (string) ($group['page'] ?? '')
            ]);
        }
    }
    fclose($out);
    exit;
}

if ($format === 'bookmarks_html' || $format === 'startme_html') {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="dockmark-bookmarks.html"');
    echo "<!DOCTYPE NETSCAPE-Bookmark-file-1>\n";
    echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
    echo "<TITLE>Dockmark Bookmarks</TITLE>\n";
    echo "<H1>Dockmark Bookmarks</H1>\n";
    echo "<DL><p>\n";
    foreach ($data['pages'] as $page) {
        $pageName = (string) ($page['name'] ?? 'Imported');
        echo "  <DT><H3>" . htmlspecialchars($pageName, ENT_QUOTES) . "</H3>\n";
        echo "  <DL><p>\n";
        foreach ($data['groups'] as $group) {
            if (($group['page'] ?? '') !== $pageName) {
                continue;
            }
            echo "    <DT><H3>" . htmlspecialchars((string) ($group['title'] ?? 'Links'), ENT_QUOTES) . "</H3>\n";
            echo "    <DL><p>\n";
            foreach (($group['links'] ?? []) as $link) {
                echo "      <DT><A HREF=\"" . htmlspecialchars((string) ($link['url'] ?? ''), ENT_QUOTES) . "\">" . htmlspecialchars((string) ($link['title'] ?? ''), ENT_QUOTES) . "</A>\n";
                if (!empty($link['note'])) {
                    echo "      <DD>" . htmlspecialchars((string) $link['note'], ENT_QUOTES) . "\n";
                }
            }
            echo "    </DL><p>\n";
        }
        echo "  </DL><p>\n";
    }
    echo "</DL><p>\n";
    exit;
}

if ($format === 'homepage') {
    header('Content-Type: text/yaml');
    header('Content-Disposition: attachment; filename="bookmarks.yaml"');
    foreach ($data['groups'] as $group) {
        echo '- ' . lodgeboard_yaml_string((string) $group['title']) . ":\n";
        foreach (($group['links'] ?? []) as $link) {
            echo '    - ' . lodgeboard_yaml_string((string) $link['title']) . ":\n";
            echo '        - href: ' . lodgeboard_yaml_string((string) $link['url']) . "\n";
            if (!empty($link['note'])) {
                echo '          description: ' . lodgeboard_yaml_string((string) $link['note']) . "\n";
            }
        }
    }
    exit;
}

if ($format === 'glance') {
    header('Content-Type: text/yaml');
    header('Content-Disposition: attachment; filename="glance-bookmarks.yml"');
    echo "pages:\n";
    foreach ($data['pages'] as $page) {
        $pageName = (string) $page['name'];
        echo '  - name: ' . lodgeboard_yaml_string($pageName) . "\n";
        echo "    columns:\n";
        echo "      - size: full\n";
        echo "        widgets:\n";
        foreach ($data['groups'] as $group) {
            if (($group['page'] ?? '') !== $pageName) {
                continue;
            }
            echo "          - type: bookmarks\n";
            echo '            title: ' . lodgeboard_yaml_string((string) $group['title']) . "\n";
            echo "            links:\n";
            foreach (($group['links'] ?? []) as $link) {
                echo '              - title: ' . lodgeboard_yaml_string((string) $link['title']) . "\n";
                echo '                url: ' . lodgeboard_yaml_string((string) $link['url']) . "\n";
            }
        }
    }
    exit;
}

http_response_code(404);
echo 'Unknown export format.';
