# Dockmark Roadmap

Dockmark should avoid becoming "yet another bookmark grid." The strong angle is:

> A self-hosted start page with a visual editor, portable exports, and community-built widgets.

## Phase 1: Make it useful every day

- Done: drag-and-drop ordering for pages, groups, and links
- Done: import browser bookmark HTML
- Done: PWA install support for mobile home screen
- Done: favicons from link URLs
- Done: tagging and stronger search
- Done: backup/restore from JSON

Next polish for Phase 1:

- Add editable page management
- Add delete/edit controls for links and groups
- Add keyboard command palette
- Add generated screenshots for the GitHub README

## Phase 2: Become the GUI for dashboard configs

- Done: Start.me-compatible CSV export
- Done: browser/Start.me bookmark HTML export
- Done: import browser/Start.me HTML
- Done: import Start.me CSV
- Done: import Homepage YAML
- Done: import Glance YAML
- Started: Chrome/Chromium clipping extension
- Started: public project website

Next polish for Phase 2:

- Better Glance export with widget layout options
- Better Homepage export with icon/service mapping
- Template gallery for common self-hosted dashboards
- Chrome extension packaging, screenshots, and privacy statement
- YAML parser hardening for complex configs

## Phase 3: Widget system

Started widgets that are useful without needing scary OAuth setup:

- Done: styled local clock widget with retro, matrix, pastel, and flip styles
- Done: weather widget using Open-Meteo, no API key
- Done: GitHub repo watcher for stars, forks, issues, releases, and update recency
- Done: RSS/reddit feed reader
- Done: Google account-slot helper for multi-account Google links

Next widget candidates:

- YouTube channel stats: subscribers, views, latest videos
- Uptime checks for personal sites
- Notes and reminders
- Wallpaper downloader/review inbox for subreddit feeds

Later, add authenticated widgets:

- Gmail unread summary
- Google Calendar agenda
- YouTube Studio-style deeper stats
- Etsy shop analytics
- Indeed/job tracking
- Facebook page inbox or comments

## Phase 4: Community repo energy

- Clear screenshots in README
- One-command Docker install
- `awesome-dockmark-widgets` list
- Good first issues for widget adapters
- Public demo with fake data
- Short YouTube build/devlog videos

## Product rule

Every widget should be optional. The dashboard must stay fast, private, and useful even with no external API keys.
