# Git Workflow

Dockmark should use Git as the coordination layer between William, Codex, Claude, and future contributors.

## Branches

- `main`: stable, deployable version
- `codex/*`: Codex work branches
- `claude/*`: Claude work branches
- `feature/*`: human feature branches

## Simple Loop

1. Start from a clean branch.
2. Make one coherent change.
3. Run syntax checks and smoke tests.
4. Stage only project files, not screenshots, zips, or local config.
5. Commit with a plain message.
6. Update `docs/PROJECT_STATUS.md` when scope changes.

## Before Asking Another AI To Work

Run:

```powershell
git status --short --branch
```

Then paste the result and the current goal. If there are uncommitted changes, say whether they should be preserved.

## Checks

```powershell
php -l index.php
php -l api.php
php -l lib.php
php -l export.php
php -l widgets.php
node --check assets/app.js
```

## Commit Identity

If Git cannot commit, set a local repo identity:

```powershell
git config user.name "Your Name"
git config user.email "you@example.com"
```

Use your real GitHub email or a GitHub no-reply email before pushing publicly.
