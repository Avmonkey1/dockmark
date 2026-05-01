# Dockmark Clipper

This is the first Chrome/Chromium extension scaffold for Dockmark.

## Local install

1. Open `chrome://extensions`.
2. Enable Developer mode.
3. Click **Load unpacked**.
4. Select this folder: `extensions/chrome`.
5. Open the extension popup and set your Dockmark URL, for example `https://dock.example.com`.

## Notes

The extension posts to `api.php` using your existing Dockmark session cookie. If admin password protection is enabled, log in to Dockmark in the browser first.

For a public Chrome Web Store release, the next step is to tighten `host_permissions` to user-configured domains through optional permissions and add screenshots/privacy docs.
