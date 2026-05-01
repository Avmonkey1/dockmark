<?php
declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json');

function dockmark_fetch_json(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 6,
            'header' => "User-Agent: Dockmark/0.1 (+https://dockmark.local)\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        throw new RuntimeException('Could not fetch widget data.');
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new RuntimeException('Widget response was not JSON.');
    }

    return $json;
}

function dockmark_fetch_text(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 6,
            'header' => "User-Agent: Dockmark/0.1 (+https://dockmark.local)\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        throw new RuntimeException('Could not fetch widget feed.');
    }

    return $raw;
}

function dockmark_git_command(string $path, array $args): string
{
    if ($path === '' || !is_dir($path) || !is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
        return '';
    }

    $command = array_merge(['git', '-C', $path], $args);
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $process = @proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        return '';
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return trim((string) $stdout);
}

function dockmark_git_status(string $path): array
{
    $branch = dockmark_git_command($path, ['rev-parse', '--abbrev-ref', 'HEAD']);
    $remote = dockmark_git_command($path, ['remote', 'get-url', 'origin']);
    $shortStatus = dockmark_git_command($path, ['status', '--short', '--branch']);

    if ($branch === '' && $shortStatus === '') {
        return [
            'available' => false,
            'branch' => '',
            'remote' => '',
            'dirtyCount' => 0,
            'ahead' => 0,
            'behind' => 0,
            'summary' => 'Git unavailable'
        ];
    }

    $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $shortStatus) ?: []));
    $branchLine = $lines[0] ?? '';
    $dirtyLines = array_filter(array_slice($lines, 1), fn ($line) => trim($line) !== '');
    $ahead = 0;
    $behind = 0;

    if (preg_match('/ahead\s+(\d+)/', $branchLine, $match)) {
        $ahead = (int) $match[1];
    }
    if (preg_match('/behind\s+(\d+)/', $branchLine, $match)) {
        $behind = (int) $match[1];
    }

    return [
        'available' => true,
        'branch' => $branch,
        'remote' => $remote,
        'dirtyCount' => count($dirtyLines),
        'ahead' => $ahead,
        'behind' => $behind,
        'summary' => count($dirtyLines) === 0 ? 'Clean' : count($dirtyLines) . ' changed'
    ];
}

try {
    $type = (string) ($_GET['type'] ?? '');

    if ($type === 'weather') {
        $lat = (float) ($_GET['lat'] ?? 39.7392);
        $lon = (float) ($_GET['lon'] ?? -104.9903);
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . rawurlencode((string) $lat)
            . '&longitude=' . rawurlencode((string) $lon)
            . '&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m&temperature_unit=fahrenheit&wind_speed_unit=mph&timezone=auto';
        $json = dockmark_fetch_json($url);
        echo json_encode(['ok' => true, 'data' => $json['current'] ?? []]);
        exit;
    }

    if ($type === 'github') {
        $repo = trim((string) ($_GET['repo'] ?? 'gethomepage/homepage'));
        if (!preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo)) {
            throw new RuntimeException('Invalid repo name.');
        }

        $repoPath = implode('/', array_map('rawurlencode', explode('/', $repo)));
        $repoData = dockmark_fetch_json('https://api.github.com/repos/' . $repoPath);
        $releaseData = [];
        try {
            $releaseData = dockmark_fetch_json('https://api.github.com/repos/' . $repoPath . '/releases/latest');
        } catch (Throwable) {
            $releaseData = [];
        }

        echo json_encode([
            'ok' => true,
            'data' => [
                'name' => $repo,
                'stars' => $repoData['stargazers_count'] ?? 0,
                'forks' => $repoData['forks_count'] ?? 0,
                'openIssues' => $repoData['open_issues_count'] ?? 0,
                'pushedAt' => $repoData['pushed_at'] ?? '',
                'latestRelease' => $releaseData['tag_name'] ?? 'No release'
            ]
        ]);
        exit;
    }

    if ($type === 'rss') {
        $url = trim((string) ($_GET['url'] ?? ''));
        if (!preg_match('/^https?:\/\//i', $url)) {
            throw new RuntimeException('Invalid feed URL.');
        }

        $raw = dockmark_fetch_text($url);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw);
        libxml_clear_errors();
        if (!$xml) {
            throw new RuntimeException('Could not parse feed.');
        }

        $items = [];
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = [
                    'title' => (string) $item->title,
                    'url' => (string) $item->link,
                    'date' => (string) $item->pubDate
                ];
                if (count($items) >= 5) {
                    break;
                }
            }
        } elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $link = '';
                foreach ($entry->link as $entryLink) {
                    $attributes = $entryLink->attributes();
                    if ((string) ($attributes['href'] ?? '') !== '') {
                        $link = (string) $attributes['href'];
                        break;
                    }
                }
                $items[] = [
                    'title' => (string) $entry->title,
                    'url' => $link,
                    'date' => (string) ($entry->updated ?? $entry->published ?? '')
                ];
                if (count($items) >= 5) {
                    break;
                }
            }
        }

        echo json_encode(['ok' => true, 'data' => $items]);
        exit;
    }

    if ($type === 'projects') {
        $registry = lodgeboard_read_projects();
        $projects = array_map(function (array $project): array {
            $localPath = (string) ($project['localPath'] ?? '');
            $exists = $localPath !== '' && is_dir($localPath);
            $git = $exists ? dockmark_git_status($localPath) : ['available' => false];
            return [
                'id' => (string) ($project['id'] ?? ''),
                'name' => (string) ($project['name'] ?? 'Untitled'),
                'status' => (string) ($project['status'] ?? 'paused'),
                'localPath' => $localPath,
                'repo' => (string) ($project['repo'] ?? ''),
                'branch' => (string) ($project['branch'] ?? ''),
                'localUrl' => (string) ($project['localUrl'] ?? ''),
                'deployUrl' => (string) ($project['deployUrl'] ?? ''),
                'owner' => (string) ($project['owner'] ?? ''),
                'nextTask' => (string) ($project['nextTask'] ?? ''),
                'notes' => (string) ($project['notes'] ?? ''),
                'pathExists' => $exists,
                'git' => $git
            ];
        }, $registry['projects'] ?? []);

        echo json_encode(['ok' => true, 'data' => $projects]);
        exit;
    }

    throw new RuntimeException('Unknown widget type.');
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
}
