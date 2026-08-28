# CLAUDE.md — Ellen Harvey

> **Read [`RUNBOOK.md`](RUNBOOK.md) before any deploy, infra, or content work.**
> Do not answer from memory for hosts, paths, or commands.
>
> The one that catches people: **code and content deploy separately.** `make deploy` ships code;
> `make push-content` ships the database and uploads. A missing page after a deploy is almost
> always the wrong command, not a bug.

Freelance client site for the artist Ellen Harvey. **Live at https://ellenharvey.net since
2026-08-27**, on a DigitalOcean droplet in Ellen's own account (`161.35.119.59`). Staging and
production are two vhosts on that single droplet. DNS is Cloudflare, also in her account. Her mail
still runs through JetHost until Email Routing is set up — see [`RUNBOOK.md`](RUNBOOK.md).

## Stack

The canonical freelance stack: **Mythus** mu-plugin framework + **IX** parent theme + an
`ellenharvey` child scaffolded from **Ena**, on Timber/Twig and PHP 8.4, DDEV locally, private
Composer deps from the `packages.vincentragosta.io` satis registry, plus ACF Pro.

Architectural depth for the same platform lives in `vincentragosta.io/CLAUDE.md`. Cross-repo rules
auto-load from `~/.claude/rules/mythus-ix.md`.

## What's specific to this site

- **Gallery items** are a custom post type ordered by `menu_order`. That ordering is database
  state — it does not survive a sync in the wrong direction.
- Runs on its **own droplet** (`161.35.119.59`) in Ellen's DigitalOcean account, hosting both staging
  and production. Not on deploy-kit.
- Her **domain is registered at Register.com / Network Solutions** — not JetHost, and not Cloudflare.
  Cloudflare only serves DNS. The cutover was a nameserver change, never a registrar transfer.
- ⚠ `git remote production` points at the **retired** droplet. **`production-new` is the live site.**

## Related

`akivili/engagements/ellen-harvey-engagement.md` — scope, client history, and the open hosting
question.
