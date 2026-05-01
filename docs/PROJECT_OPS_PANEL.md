# Project Ops Panel

The Project Ops Panel is Dockmark's command center for active work. It should make it obvious where each project lives, what state it is in, and what the next responsible action is.

## Purpose

Dockmark should not only launch links. It should help run the user's project world.

The panel tracks:

- local project folder
- GitHub repo
- current branch
- local dev URL
- deploy URL
- project owner/AI helper
- status
- next task
- last checked time
- notes and risk flags

## First Version

Use a JSON file:

```text
data/projects.json
```

Render it as a Project Ops widget on the `Projects` page.

The first version can be read-only and manually edited. Later versions should add:

- refresh Git status button
- add/edit project UI
- Claude/Codex handoff notes
- GitHub issue/PR widgets
- local server health checks
- deploy checklist

## Status Labels

- `active`: currently being built
- `stable`: good enough to deploy/use
- `paused`: not dead, but not current
- `archive`: keep for reference only
- `needs-review`: AI or human work exists but needs inspection

## Safety Rule

No AI should work from OneDrive project roots anymore. The ops panel should point active projects to:

```text
C:\Users\Dell\Projects
```

Old OneDrive copies are references only until manually archived.
