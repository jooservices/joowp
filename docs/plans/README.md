# Plans Directory

Use this directory to track medium/large implementation plans. Each plan lives in its own Markdown file so the history is easy to follow and status changes are explicit.

## Naming

- File name: `YYYY-MM-DD-short-title.md`
- Title inside file: `# Plan – <Short Title>`

## Structure

```markdown
# Plan – Example Module Rollout

Status: Active | Completed | Abandoned  
Owner: full name or UUID  
Created: 2025-11-11  
Updated: 2025-11-11

## Summary
- one-line purpose of the plan

## Objectives
- Bullet list of measurable goals

## Tasks
- [ ] Task one
- [ ] Task two

## Notes
- Optional context, decisions, links to tickets.
```

## Status updates

- Keep `Status:` at the top up to date (`Active`, `Completed`, or `Abandoned`).
- Add a short line under _Notes_ or a new section describing the outcome when the plan finishes.
- Do **not** delete completed plans—archive them in place with `Status: Completed`.

## Checklist for new plans

1. Copy the structure above into a new file.
2. Fill in metadata and objectives.
3. Update `docs/README.md` or the relevant guide with any new links if needed.
4. Commit the plan file with the rest of the work.


