# Google Account Slots

Dockmark can add a Google account hint to a saved Google link.

Examples:

```text
https://mail.google.com/mail/u/1/
https://drive.google.com/u/2/
https://docs.google.com/document/d/.../edit?authuser=1
```

This helps when you are signed into several Google accounts in the same browser profile.

Important distinction:

- `authuser=1` or `/u/1/` can select a signed-in Google account inside Google apps.
- It does not force a whole Chrome browser profile.
- Full Chrome profile targeting usually requires Chrome profile shortcuts, OS-level handlers, a browser extension, or routing tools.

Dockmark stores this as `googleAccount` on a link. Valid values can be `0`, `1`, `2`, or an email address when Google supports it.
