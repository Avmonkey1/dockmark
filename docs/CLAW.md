# Claw: Dockmark's Cleanup and Discovery Agent

Claw is the planned automation layer for Dockmark. It should be optional, transparent, and user-controlled.

## Jobs

- Link audit: find dead links, redirects, duplicate URLs, and stale domains.
- Related-link search: suggest nearby docs, repo pages, product pages, or official sources for a saved link.
- Wallpaper collector: pull fresh wallpapers from configured RSS/subreddit sources and save approved images.
- Repo watcher: alert when starred/forked GitHub repos release, archive, change license, or publish major updates.
- Feed summarizer: turn noisy RSS/reddit/youtube feeds into a small daily panel.

## Safety rules

- Claw should never delete links automatically. It should mark, suggest, or archive only after user approval.
- Download jobs should write into a review folder first.
- Authenticated widgets should use scoped API keys or OAuth and show exactly what data is being read.
- Every scheduled job needs a visible last-run time, next-run time, and error log.

## First implementation shape

```text
claw/
  jobs/
    link-audit.php
    wallpaper-reddit.php
    github-watch.php
  storage/
    claw-log.json
    wallpaper-inbox/
```

The first shippable job should be link audit because it is useful to everyone and does not require OAuth.
