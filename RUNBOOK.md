# Ellen Harvey — Runbook

Operating this project: where it lives, how to deploy, what to purge, what bites.

**Stack** WordPress · Mythus mu-plugin · IX parent + `ellenharvey` child · Timber/Twig · PHP 8.4 · DDEV
**Client** Ellen Harvey (direct, not ARTHOUSE) · **Repo** `vinnyrags/ellenharvey`
**Local** https://ellenharvey.ddev.site · `make help` lists every target

---

## Environments

| Env | Host | Docroot | Branch | URL |
|---|---|---|---|---|
| local | DDEV | repo root | `main` | https://ellenharvey.ddev.site |
| staging | `root@174.138.70.29` | `/var/www/ellenharvey.vincentragosta.io` | `main` | https://ellenharvey.vincentragosta.io |
| production | — | — | — | **does not exist yet** |

WP core sits in a **`/wp` subdirectory** (`$STAGING_DIR/wp`), so remote wp-cli needs `--path=wp`.

**There is no production environment.** The site lives on staging under a subdomain of
vincentragosta.io. Moving it to its own hosting and DNS — off Ellen's existing JetHost account — is
the open engagement item.

The destination is settled (2026-08-20): a **$6/mo DigitalOcean droplet, account in Ellen's own
name, billed to her card directly** — we set it up, she owns it. DNS then points from JetHost to
that droplet and JetHost lapses on its own in December. Not yet executed; see the engagement doc for
the agreed signup flow.

This droplet is **shared with vincentragosta.io** and is **not on deploy-kit** — extending deploy-kit
to it is a tracked follow-up. Deploys here still use the older per-site post-receive hook.

## Deploy

Remote `production` → `root@174.138.70.29:/var/repo/ellenharvey.git` (the remote is named
`production` even though it currently serves staging — a naming artifact worth knowing before you
push). `origin` is GitHub and deploys nothing.

**Code and content deploy separately, and this is the thing to remember here:**

```bash
make deploy        # code only — git push to the droplet
make push-content  # database + uploads
```

Content is the **database**, not git. A code deploy will not move a page, a gallery item, or an
image. If a change looks missing after a deploy, check which of the two you actually ran.

## Content vs code

- **Gallery items** are a custom post type ordered by `menu_order` — the ordering is DB state and
  is wiped by a DB sync in the wrong direction.
- **Uploads** are gitignored; `make push-content` carries them.
- Because there's no production yet, the sync only runs local → staging. Be careful once a real
  production environment exists — the direction guard matters more then.

## Caches

Redis object cache on the droplet; `wp cache flush` clears it. This site is recorded as
**page-uncached** (no nginx FastCGI micro-cache, unlike the ARTHOUSE droplets) — worth re-verifying
before assuming a stale page is a cache problem.

## Traps

1. **WP salts moved out of VCS 2026-08-13.** Real salts live in the gitignored `wp-config-env.php`;
   the tracked `wp-config.php` carries guarded placeholders and **must require the env file above
   the salt block** (PHP is first-wins). Verify by proving the env value won, not that the
   placeholder is gone.
2. **This box cannot send email.** No MTA, no SMTP plugin — `wp_mail()` fails silently. Relevant
   here: a contact form that appears to work will deliver nothing, and you cannot hand Ellen a
   WordPress password-reset link.
3. **Root `composer update` without local ACF auth wipes the mythus mu-plugin.** Update the child
   vendor instead.
4. **`composer update vincentragosta/ix` wipes `ix/node_modules`** — run `npm install` inside the
   `ix` copy afterwards to restore build and test tooling.
5. **Client content updates are additive in intent but get applied as replacements.** Ellen emails a
   section (a résumé, a news blurb) meaning "add these" — pasting it in wholesale silently deletes
   whatever else was there. This shipped: the 2026-08-03 pass cut the résumé from 68 credits to 13,
   wiping ~25 years of history, and she caught it, not us.
   **Before shipping any content rewrite, diff against the pre-rebuild originals in
   `.assets-inbox/db-backups/`** (`resume-186.orig.html`, `contact-164.orig.html`) and confirm the
   new version is a superset. Cross-check any PDF she attaches too — her 2026 résumé PDF held two
   credits that were on neither the old site nor in her email body.
   Post revisions in the DB are the other recovery path: `wp post list --post_type=revision
   --post_parent=<id> --post_status=any`.

## See also

- `akivili/engagements/ellen-harvey-engagement.md` — scope, client history, and the open hosting question
- `vincentragosta.io/RUNBOOK.md` — same droplet, more detail on its shared services
