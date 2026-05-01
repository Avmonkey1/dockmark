# Mobile App Direction

Dockmark is already installable as a PWA. That should be the first mainstream mobile path because it keeps hosting simple and works on iOS and Android without app store friction.

## Phase A: PWA

- Add to home screen
- Offline shell
- Mobile-friendly dashboard
- Share target for saving links from mobile browsers

## Phase B: Wrapper apps

Use Capacitor if native app stores become important.

```text
apps/
  ios/
  android/
```

Native wrappers should keep the Dockmark server as the source of truth. Avoid building separate sync logic unless the project has real users asking for it.

## Phase C: Native extras

- Share sheet capture
- Wallpaper download/preview manager
- Push notifications for Claw alerts
- Biometric lock for private dashboards
