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
that droplet and JetHost lapses on its own in December. **Ellen greenlit the migration 2026-08-26**
and gave `Shawgirlnyc@yahoo.com` as the account email; see the engagement doc for the signup flow.

### DNS and mail facts (verified 2026-08-26)

| | |
|---|---|
| Registrar | **Register.com / Network Solutions** — *not* JetHost; already decoupled |
| Domain expiry | **2027-09-30** — no near-term renewal deadline |
| Nameservers | `ns1–4.jethostns.com` — **served by JetHost, dies in December** |
| Live A record | `15.204.159.119` (JetHost) |
| MX | `ellenharvey.net` → `15.204.159.119` — **her mail is on the JetHost box** |

### JetHost account audit (read-only, 2026-08-26)

| | |
|---|---|
| Product | Hosting for WordPress — Plan WP Start, `ellenharvey.net`, Active |
| Customer since | 2011-11-17 |
| **Recurring** | **$96.00/yr — auto-renews 2026-12-15 on a credit card on file** |
| Cancellation | via **"Request Cancellation"** — a request flow, needs lead time |
| Disk / bandwidth | 195 MB of 25 GB · 420 MB |
| WordPress installs | none (the legacy site is hand-coded static HTML) |
| Email accounts | **none** |
| Forwarders | **`ellen@ellenharvey.net` → `eharvey.net@gmail.com`** (the only one) |
| Catch-all | Not Found / discard |
| Addon domains | none — single domain |
| Account NS / IP | `ns1,ns2.use2.jethosting.com` · `15.204.159.119` |

### Cutover checklist (built 2026-08-26 — none of this is done yet)

**1. Legacy URL redirects — the biggest unhandled gap.** The legacy site is 25 years of indexed `.htm`
URLs. **All of them 404 on the new site today.** Every page has a counterpart, so this is redirects,
not missing content. Its `sitemap.xml` (2014-era) is the authoritative inventory — 11 URLs:

| Legacy | New |
|---|---|
| `/index.htm` | `/` |
| `/news.htm` | `/news/` |
| `/resume.htm` | `/resume/` |
| `/reviews.htm` | `/reviews/` |
| `/photos.htm` | `/photos/` |
| `/media.htm` | `/media/` |
| `/contact.htm` | `/contact/` |
| `/resume.pdf` | the 2026 PDF in uploads — this is the **2011** résumé and is the likeliest external link (agents, casting sites) |
| `/ellen1.pdf`, `/ellen2.pdf` | orphaned — nothing on the live site links to them since ~2014, contents unidentified. Decide or leave 404. |

Do these as 301s in the nginx vhost, not a plugin. Drop this into the **production** `server {}`
block — exact-match (`location =`) rules win over the generic `location /` regardless of where they
sit in the file, so placement is free:

```nginx
# ── Legacy .htm redirects (pre-2026 hand-coded site) ─────────────────────────
# Source of truth: the old site's sitemap.xml — 11 URLs, captured 2026-08-26.
# 301 is permanent and gets cached by browsers and Google. Get it right once.
location = /index.htm   { return 301 https://$host/; }
location = /news.htm    { return 301 https://$host/news/; }
location = /resume.htm  { return 301 https://$host/resume/; }
location = /reviews.htm { return 301 https://$host/reviews/; }
location = /photos.htm  { return 301 https://$host/photos/; }
location = /media.htm   { return 301 https://$host/media/; }
location = /contact.htm { return 301 https://$host/contact/; }

# The 2011 résumé PDF — likeliest externally-linked asset (agents, casting).
location = /resume.pdf {
    return 301 https://$host/wp-content/uploads/2026/08/Ellen_Harvey_2026_resume.pdf;
}

# /ellen1.pdf, /ellen2.pdf — single-page image scans, in the 2011 sitemap,
# orphaned since ~2014. Contents unidentified; deliberately left to 404.
```

⚠ **The `/resume.pdf` rule hard-codes the current upload path.** Replacing her résumé PDF changes
that path and silently breaks this redirect. Re-check it any time the PDF is swapped.

**Two adjacent fixes for the same vhost:**

- `location = /robots.txt { ... }` in the current staging vhost has no `try_files`, so it serves from
  disk, finds nothing, and 404s — WordPress's generated robots.txt is unreachable. Harmless while
  `blog_public=0`, wrong once the site is public. Let it fall through to `index.php`.
- The legacy site answers on apex **and** www over both http and https with no canonical. Pick one
  host, 301 the other three, and force https — do that in a separate `server {}` so these `.htm`
  rules only ever run on the canonical host and no request takes two hops.

**2. Flip search visibility at go-live.** `blog_public` is currently **`0`** and the `noindex, nofollow`
meta is confirmed present — correct for staging, fatal if it ships. Set to `1` at cutover and verify
the meta is gone.

**3. Canonicalise the hostname.** The legacy site answers on **all four** of apex/www × http/https,
each returning 200 with no redirect. Pick one canonical host, 301 the rest, and force https.

**Before letting JetHost go, do two more things.**

1. **Rebuild the mail forward.** `ellen@ellenharvey.net` is published on her Contact page and its MX is
   the JetHost box; this droplet cannot send *or* receive mail (trap #2). It is a **pure forward**, so
   **Cloudflare Email Routing replaces it at $0** — no mailbox migration, no paid provider.
2. **Cancel — do not let it lapse.** The plan bills **$96 on 2026-12-15** against a card on file.
   "Lapsing" charges her. Submit the cancellation request once the migration is verified, with room
   before 2026-12-15.

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
