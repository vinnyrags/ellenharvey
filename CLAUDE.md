# CLAUDE.md — Ellen Harvey

> **Read [`RUNBOOK.md`](RUNBOOK.md) before any deploy, infra, or content work.**
> Do not answer from memory for hosts, paths, or commands.
>
> The one that catches people: **code and content deploy separately.** `make deploy` ships code;
> `make push-content` ships the database and uploads. A missing page after a deploy is almost
> always the wrong command, not a bug.

Freelance client site for the artist Ellen Harvey. **There is no production environment yet** — the
site runs on staging at https://ellenharvey.vincentragosta.io while hosting and DNS are moved off
her existing JetHost account.

## Stack

The canonical freelance stack: **Mythus** mu-plugin framework + **IX** parent theme + an
`ellenharvey` child scaffolded from **Ena**, on Timber/Twig and PHP 8.4, DDEV locally, private
Composer deps from the `packages.vincentragosta.io` satis registry, plus ACF Pro.

Architectural depth for the same platform lives in `vincentragosta.io/CLAUDE.md`. Cross-repo rules
auto-load from `~/.claude/rules/mythus-ix.md`.

## What's specific to this site

- **Gallery items** are a custom post type ordered by `menu_order`. That ordering is database
  state — it does not survive a sync in the wrong direction.
- Shares droplet `174.138.70.29` with `vincentragosta.io`, and is **not on deploy-kit** yet.

## Related

`akivili/engagements/ellen-harvey-engagement.md` — scope, client history, and the open hosting
question.
