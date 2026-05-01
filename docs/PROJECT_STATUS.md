# Dockmark Project Status

This file is the working source of truth for Codex, Claude, and human edits.

## Current Shape

Dockmark is a standalone PHP/JSON self-hosted start page. It can also import/export dashboard configs for Glance, Homepage, Start.me-style CSV, and browser bookmark HTML.

## Implemented

- Standalone dashboard with pages, groups, links, tags, search, favicons, notes, and dark/light mode
- PWA install shell with manifest, service worker, and app icon
- Admin drawer with add link, import, restore, export, and order save tools
- Drag ordering for pages, groups, and links
- Import from browser/Start.me HTML, Start.me CSV, Homepage YAML, and Glance YAML
- Export to Glance YAML, Homepage YAML, Start.me CSV, bookmark HTML, and JSON backup
- Chrome extension scaffold in `extensions/chrome`
- Public project landing page in `website`
- Widget starter set:
  - styled local clock with retro, matrix, pastel, and flip styles
  - free weather via Open-Meteo
  - GitHub repo watcher
  - RSS/reddit feed widget
- Google account-slot helper for Google app links using `/u/{slot}/` and `authuser={slot}` hints
- Project Ops Panel spec and seed project registry

## Not Yet Done

- Render Project Ops Panel from `data/projects.json`
- Link/group/page edit and delete controls
- Widget management UI inside the admin drawer
- Persistent widget configuration editor
- Claw link audit implementation
- Wallpaper downloader/review inbox
- Chrome extension production hardening
- Docker install
- Real GitHub remote, README screenshots, release tags, and issue templates

## Known Constraints

- Dockmark can hint Google account selection, but it cannot force an entire Chrome browser profile from a normal web link.
- Widget calls are anonymous/public API calls for now. OAuth-based widgets such as Gmail, Google Keep, Google Tasks, YouTube Studio, Etsy, and Facebook need a proper auth design.
- YAML import is intentionally lightweight and should be hardened before claiming broad config compatibility.

## Local Run

```powershell
php -S 127.0.0.1:8088 -t .
```

Open:

```text
http://127.0.0.1:8088
```

## Next Best Commit

Use this as the next real repo commit:

```text
Add project ops panel
```
