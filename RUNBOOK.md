# Ellen Harvey — Runbook

Operating this project: where it lives, how to deploy, what to purge, what bites.

**Stack** WordPress · Mythus mu-plugin · IX parent + `ellenharvey` child · Timber/Twig · PHP 8.4 · DDEV
**Client** Ellen Harvey (direct, not ARTHOUSE) · **Repo** `vinnyrags/ellenharvey`
**Local** https://ellenharvey.ddev.site · `make help` lists every target

---

## Environments

> **WENT LIVE 2026-08-27 20:24 EDT.** `ellenharvey.net` now serves the WordPress rebuild from a
> DigitalOcean droplet in **Ellen's own account**. Everything below describes the current state; the
> migration history is further down.

| Env | Host | Docroot | Branch | URL |
|---|---|---|---|---|
| local | DDEV | repo root | `main` | https://ellenharvey.ddev.site |
| **staging** | `root@161.35.119.59` | `/var/www/staging.ellenharvey.net` | `main` | https://staging.ellenharvey.net |
| **production** | `root@161.35.119.59` | `/var/www/ellenharvey.net` | `main` | https://ellenharvey.net |
| *retired* | `root@174.138.70.29` | `/var/www/ellenharvey.vincentragosta.io` | — | old preview, still running |

WP core sits in a **`/wp` subdirectory**, so remote wp-cli needs `--path=<docroot>/wp`.

### The droplet

| | |
|---|---|
| Name / ID | `ellenharvey-prod-01` · `595736106` |
| IP | **`161.35.119.59`** · IPv6 **`2604:a880:400:d1:0:4:e2a7:f001`** |
| Account | **Ellen's** — `shawgirlnyc@yahoo.com`, billed to her card |
| Spec | Basic/Regular, 1 GB / 1 vCPU / 25 GB, nyc1, **$6/mo**, no backups |
| Access | Vincent's `id_ed25519`, imported into her account as `vincent-macbook` |
| Swap | 2 GB — a 1 GB box running two WordPress installs needs it |

**Both environments live on this one droplet**, isolated by database, Redis DB number and cache-key
salt — the same pattern as vincentragosta.io:

| | staging | production |
|---|---|---|
| Database | `ellenharvey_stg` / `ellenharvey_stg` | `ellenharvey` / `ellenharvey_user` |
| Redis DB | 1, salt `staging_ellenharvey_` | 0, salt `ellenharvey_` |
| `blog_public` | **0** | **1** |
| Bare repo | `/var/repo/ellenharvey-staging.git` | `/var/repo/ellenharvey-production.git` |

Credentials are in each docroot's gitignored `wp-config-env.php`.

### DNS — Cloudflare, in Ellen's account

Nameservers moved from `dns1/dns2.amhosting.com` to Cloudflare on **2026-08-27**. Because the zone was
built as an exact mirror of the live one first, **the nameserver change was invisible** — nothing
resolved differently and the site never went down. Only afterwards did the apex A record move.

| | |
|---|---|
| Registrar | **Register.com / Network Solutions** — unchanged, nameservers only |
| Registrant email | `shawgirlnyc@yahoo.com` — already correct, nothing to change |
| Domain expiry | **2027-09-30**, auto-renew on. No near-term deadline. |
| Cloudflare account | **Ellen's** — `Shawgirlnyc@yahoo.com`, deliberately *not* Vincent's |
| Zone ID | `2a13613368f151e21ef7e861481736ce` |
| Nameservers | `alexis.ns.cloudflare.com`, `sasha.ns.cloudflare.com` |
| DNSSEC | **off** — verified. Left enabled through an NS change is one of the few ways to take a domain fully offline. |

**Every record is DNS-only (grey cloud), and that is deliberate.** `mail`, `smtp`, `webmail`, `pop3`
and `ftp` *must* stay grey forever: Cloudflare's proxy only handles HTTP/HTTPS ports, so proxying them
breaks those protocols outright. Proxying apex/`www` would also mean the origin IP is hidden, which
changes certificate handling. Cloudflare will nag that the domain "is not fully protected" — ignore it.

| Type | Name | Value |
|---|---|---|
| A | `@`, `www` | **`161.35.119.59`** — the droplet. TTL **60s** for fast cutover/revert. |
| AAAA | `@`, `www`, `staging` | **`2604:a880:400:d1:0:4:e2a7:f001`** — the droplet |
| A | `staging` | `161.35.119.59` |
| A | `mail`, `ftp`, `pop3`, `smtp`, `stats`, `webmail` | `15.204.159.119` — **still JetHost** |
| MX | `@` | `10 mail.ellenharvey.net` |
| TXT | `@` / `_dmarc` | SPF (JetHost) / `v=DMARC1; p=reject; aspf=s` |

⚠ **The staging URL of the OLD preview is not in this project.** `ellenharvey.vincentragosta.io` is an
A record inside the `vincentragosta.io` zone on DigitalOcean DNS. It still resolves and still serves
the old preview. Retire it deliberately once Ellen has signed off, rather than letting it rot.

### IPv6 — resolved 2026-08-27, ~21:00

**Droplet IPv6: `2604:a880:400:d1:0:4:e2a7:f001`.** AAAA records published for apex, `www` and
`staging`. Verified end to end: all pages 200 over forced IPv6, `www` → apex redirect works, IPv4
unaffected, and the address survives a `systemd-networkd` restart.

The gap existed because the droplet was created without IPv6 and Cloudflare's scan missed the legacy
`AAAA → 2607:7700:0:e:0:2:fcc:9f77`, which was then deliberately not recreated. That reasoning was
incomplete: resolvers holding JetHost's cached AAAA kept sending IPv6 visitors to the **old** site
until it expired — a brief old/new split rather than an outage.

**Enabling IPv6 on an existing DigitalOcean droplet is a two-part job.** The API action
(`doctl compute droplet-action enable-ipv6`) only allocates the address and publishes it to the
metadata service; **the OS does not pick it up.** Netplan needs the address and a default route added
by hand:

```yaml
      addresses:
      - "161.35.119.59/20"
      - "2604:a880:400:d1:0:4:e2a7:f001/64"     # added
      routes:
      - to: "0.0.0.0/0"
        via: "161.35.112.1"
      - to: "::/0"                              # added
        via: "2604:a880:400:d1::1"
        on-link: true
```

`netplan apply` is safe here — it did not disturb IPv4 or drop SSH across two attempts.

⚠ **Two things cost time and are worth not repeating.** The netplan file indents list items at
**6 spaces**, and reading it through a `sed 's/^/  /'` pipe makes it look like 8 — a string-replace
built on that misreading silently matches nothing. **Assert your anchors before writing.** And a
"safety" dead-man switch that restores a backup after N seconds will happily undo a change you made
correctly; it masked the real bug for a full round-trip.

### Target Cloudflare zone *(historical — this was the plan, executed 2026-08-27)*

> Superseded by the live DNS table above. Kept because the reasoning — especially the MX trap — is
> the most important thing anyone touching this domain needs to understand.

The live zone as it stood pre-migration, and what each record became. `mail`/`ftp`/`webmail`/`smtp` are cPanel
defaults that exist only to serve JetHost — they go away with it and must **not** be recreated.

| Type | Name | Live value | After |
|---|---|---|---|
| A | `@` | `15.204.159.119` | new droplet IP |
| AAAA | `@` | `2607:7700:0:e:0:2:fcc:9f77` | droplet IPv6, or drop |
| A | `www` | `15.204.159.119` | new droplet IP |
| A | `ftp`,`webmail`,`smtp` | `15.204.159.119` | **drop — JetHost-only** |
| A | `mail` | `15.204.159.119` | **KEEP through the transition** — see below |
| MX | `@` | `10 ellenharvey.net` | **`10 mail.ellenharvey.net` at cutover**, then Cloudflare Email Routing |
| TXT | `@` | `v=spf1 ip4:15.204.166.98 ip4:15.204.159.120 include:spf.guardedhost.com ~all` | keep during transition; Cloudflare Email Routing SPF after |
| TXT | `_dmarc` | `v=DMARC1; p=reject; aspf=s` | **keep as-is** |

> ☠️ **The MX record self-references the domain, and that kills her mail the instant the A record moves.**
>
> Today `MX @ → 10 ellenharvey.net`, and `ellenharvey.net` resolves to the JetHost box, which runs the
> mail. After cutover `ellenharvey.net` resolves to the **droplet**, which has no MTA and cannot
> receive mail (trap #2). Copy that MX across verbatim and mail to `ellen@ellenharvey.net` starts
> disappearing the moment DNS propagates — **silently**, with no bounce she would ever see.
>
> **Fix:** at cutover, keep the `mail` A record on `15.204.159.119` and repoint the MX at it —
> `MX 10 mail.ellenharvey.net`. Her mail keeps flowing through JetHost, which is paid to December,
> while the web moves to the droplet. Swap to Cloudflare Email Routing as a **separate, later step**,
> and only after verifying it end to end.
>
> **Email Routing cannot be enabled on cutover night anyway.** It requires the zone to already be
> active on Cloudflare nameservers, *and* the destination address (`eharvey.net@gmail.com`) to be
> verified by someone clicking a link Cloudflare sends there. Ellen has not confirmed she can still
> access that inbox. Until she does, the `mail`-record bridge above is what keeps her contact address
> alive — do not remove it on the assumption Email Routing will be ready.

`p=reject` with strict alignment is already correct and worth preserving — nothing legitimately sends
as `@ellenharvey.net` (there are no mailboxes), so a strict policy costs nothing and blocks spoofing.
No CAA records exist.

### Legacy site archive

Full mirror of the pre-rebuild site at **`.assets-inbox/legacy-site-archive/`** — 112 files, 26 MB,
captured 2026-08-26: all 7 `.htm` pages, `sitemap.xml`, all 3 PDFs, and all 101 referenced CSS/JS/image
assets, fetched with zero misses.

⚠ **`.assets-inbox/` is gitignored, so this lives only on the local machine.** The same is true of the
`db-backups/` originals that trap #5 names as the résumé recovery path — that recovery route exists on
one laptop and nowhere else. Worth copying somewhere durable before JetHost is cancelled, after which
the source is gone for good.

### Rebuild content parity — verified 2026-08-26

Checked because trap #5 had already bitten once on the résumé. **The galleries are clean.** Every
legacy gallery survived with its exact photo count:

`Big Love` 7 · `The Phantom of the Opera` 10 · `How To Succeed` 7 · `Mary Poppins` 5 ·
`High School Musical` 6 · `Mamma Mia!` 5 · `Phantom` 18 · `The Music Man` 10 — plus the new
`Cabaret` 5 and `Wicked` 2. All 68 legacy photos present, and every legacy page (including Media and
Reviews) has a counterpart. The only legacy gap is URLs, not content.

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

### Go-live record — 2026-08-27

Sequence actually executed, in order. Worth keeping because the ordering is what made it safe:

1. Droplet created in **her** DO account via API; Vincent's SSH key imported
2. Provisioned to match source — PHP 8.4, MariaDB 10.11, nginx 1.24, Redis 7, Node 20.20.2, 2 GB swap
3. Staging built; code deployed **through the post-receive hook** rather than rsync, proving the pipeline
4. Content migrated server-to-server (not via laptop); 339 URL replacements
5. Cloudflare zone built as an **exact mirror** of the live zone, all records DNS-only
6. MX repointed apex → `mail.ellenharvey.net` *before* anything moved — same server, but it frees the apex
7. Nameservers moved at Register.com. **Invisible — nothing resolved differently, site never went down**
8. Staging certificate via HTTP-01; **production certificate via DNS-01 using the Cloudflare API token,
   issued before any traffic moved** — so there was never a window of certificate warnings
9. Production built and verified end-to-end using `curl --resolve`, with live DNS still on JetHost
10. Apex + `www` A records flipped. Live at **20:24 EDT**

Verified after: all pages 200, 71 résumé rows, 0 stale URLs, legacy redirects 301, `www` → apex,
hardening 403s, valid certificate, all four major public resolvers correct.

**What went wrong, and it was worth catching:**

- **cPanel's Zone Editor is not authoritative for this domain.** A test record added there appeared on
  `ns1/ns2.use2.jethosting.com` but *not* on the delegated `jethostns.com` nameservers. Had we
  attempted the original plan — flip the A record in cPanel — it would have silently done nothing on
  a live site at midnight. Found for free because we tested with a throwaway `staging` record first.
- **The IPv6 gap** (see above), which produced a brief old/new split for some visitors.
- Repeated **local DNS-cache false alarms** — the operator's machine kept showing the old site while
  every public resolver was correct. Verify from an independent vantage point (the droplet itself, or
  `curl --resolve`) before believing a failure is real.

### Cutover checklist *(completed 2026-08-27 — kept for reference)*

**1. Legacy URL redirects — ✅ BUILT AND LIVE ON STAGING (2026-08-26).** The legacy site is 25 years of
indexed `.htm` URLs, and all of them used to 404 on the rebuild. Every page has a counterpart, so this
was redirects, not missing content. The old `sitemap.xml` (2014-era) was the authoritative inventory —
11 URLs:

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

**Where this lives now:** `/etc/nginx/snippets/ellenharvey-legacy-redirects.conf` on the staging
droplet, pulled into the vhost with `include snippets/ellenharvey-legacy-redirects.conf;` just below
`client_max_body_size`. It follows the same pattern as `wp-hardening.conf`, so **carrying it to the
production droplet is a file copy plus one include line** — don't retype it. Pre-change vhost backup
is at `/root/ellenharvey.vhost.bak-20260826-202115`.

Verified on staging: all 8 rules return 301 and resolve to 200, `ellen1/2.pdf` still 404 by design,
and every live page plus the other five sites on the shared droplet were unaffected by the reload.

The contents, for reference — exact-match (`location =`) rules win over the generic `location /`
regardless of where they sit in the file, so placement is free:

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

Git remotes, **reorganised 2026-08-28** (the misleading `production` → old-droplet mapping is gone):

| Remote | Target | Effect |
|---|---|---|
| `origin` | GitHub | deploys nothing |
| `staging` | `root@161.35.119.59:/var/repo/ellenharvey-staging.git` | `develop` → https://staging.ellenharvey.net |
| `production` | `root@161.35.119.59:/var/repo/ellenharvey-production.git` | `main` → **https://ellenharvey.net (LIVE)** |
| `preview` | `root@174.138.70.29:/var/repo/ellenharvey.git` | the retired preview on Vincent's droplet — **no target deploys here** |

### Preview retirement — phase 1 done 2026-08-28

`ellenharvey.vincentragosta.io` now **301s to https://ellenharvey.net**, path preserved
(`/photos/` → `/photos/`). Verified end to end: 200 at the far end, over both http and https.

Redirected rather than deleted because **the preview URL was emailed to Ellen on 2026-08-13 and is
still in her inbox.** A dead page at a link we sent her is a worse outcome than a redirect — she is
the client whose previous developer left her unable to reach her own site.

The new vhost has **no php handler, no root, no try_files** — everything 301s, so nothing under
`/var/www/ellenharvey.vincentragosta.io` is reachable at all. `wp-config-env.php` is unserveable by
construction rather than by a deny rule that has to be ordered correctly.

**Still in place, deliberately:** the 444M docroot (57M uploads), the `ellenharvey` /
`ellenharvey_user` database, and the `/var/repo/ellenharvey.git` bare repo. They are a warm copy of
her site while her $6 droplet runs **with no backups**. Its value decays as she edits live content.

**Phase 2 — full teardown, scheduled alongside the JetHost cancellation (before 2026-12-15):**

1. Pull a final backup off the droplet — DB dump + uploads tarball
2. Drop database `ellenharvey` and user `ellenharvey_user`
3. Remove `/var/www/ellenharvey.vincentragosta.io` and `/var/repo/ellenharvey.git`
4. `certbot delete --cert-name ellenharvey.vincentragosta.io`
5. Remove the vhost + its `sites-enabled` symlink, `nginx -t`, reload
6. **Remove the DNS A record LAST** — `ellenharvey` in the `vincentragosta.io` zone (DigitalOcean)
7. Drop the `preview` git remote locally

> ⚠️ **Order matters at step 6.** Delete the A record while certbot still holds the cert and the
> next renewal fails on a name it cannot validate — which surfaces as noise across the whole
> renewal run, not just this one cert. Cert first, DNS after.
>
> Rollback for phase 1: `/root/nginx-backups/20260828-031311/ellenharvey.vincentragosta.io.pre-retire`

### Branch → environment

**`develop` → staging. `main` → production.** Matching vincentragosta.io. Use the Makefile:

```bash
make deploy-staging      # refuses unless you are on develop
make deploy-production   # merges develop → main --ff-only, then pushes
```

> ☠️ **The hooks are branch-gated, and a wrong-branch push fails silently.** Each hook skips any ref
> that is not its branch (`[ "$branch" != "develop" ] && continue`). Push `main` to staging and the
> ref moves, the hook prints "Branch main — skipping", and **nothing deploys** — with a perfectly
> successful-looking `git push`. Both Makefile targets check the branch before pushing, and compare
> the remote ref to the local SHA afterwards rather than printing an unearned ✓.

The staging hook was changed from `main` to `develop` on 2026-08-28; before that both environments
deployed `main`, which made a staging/production split impossible. Hook backups:
`/root/hook-backups/` on the droplet.

Both hooks run composer for root and child theme, `npm ci && npm run build` for IX
and the child, flush caches, and chown. They need `/root/.composer-auth.json` and
`/root/.config/composer/auth.json` for the ACF Pro and satis credentials — **both were copied to the
new droplet**; without them composer silently drops the mythus mu-plugin.

**Code and content deploy separately, and this is the thing to remember here:**

```bash
make deploy-production   # code only
make push-content        # database + uploads → staging
```

Content is the **database**, not git. A code deploy will not move a page, a gallery item, or an
image. If a change looks missing after a deploy, check which of the two you actually ran.

## Emergency revert

**`.assets-inbox/REVERT-GOLIVE.sh`** — points `ellenharvey.net` and `www` back at JetHost
(`15.204.159.119`) via the Cloudflare API. TTL is 60s, so it takes effect in about a minute.

This works **only while the original site is still on JetHost**, which is true until cancellation
(target mid-November). After that there is nothing to revert to — the archive in
`.assets-inbox/legacy-site-archive/` is a static copy, not a running site.

Nothing else needs reverting: the nameserver move is invisible either way, and mail never moved.

## Content vs code

- **Gallery items** are a custom post type ordered by `menu_order` — the ordering is DB state and
  is wiped by a DB sync in the wrong direction.
- **Uploads** are gitignored; `make push-content` carries them.
- **There are now two environments on one droplet.** The direction guard matters: a careless sync can
  overwrite production with staging content, or push `blog_public=0` onto the live site and
  quietly de-index it.

## Caches

Redis object cache on the droplet; `wp cache flush` clears it. This site is recorded as
**page-uncached** (no nginx FastCGI micro-cache, unlike the ARTHOUSE droplets) — worth re-verifying
before assuming a stale page is a cache problem.

## Security posture

Applied to the new droplet 2026-08-27, at parity with the rest of the fleet. **It was missing at
first** — the vhosts were written from scratch and omitted the hardening include, so the site was
briefly exposed to all three of the incidents that snippet exists to prevent.

- **`snippets/wp-hardening.conf`** included at the top of *both* server blocks. Blocks
  `/xmlrpc.php` **and** `/wp/xmlrpc.php` (the Bedrock-layout path that was open fleet-wide), PHP
  execution anywhere under `uploads/` (the Shucked incident, 2026-08-25), and `/scripts/` (the CBA
  credential leak, 2026-08-24). All three verified returning 403 by live probe, including writing a
  real PHP file into `uploads/` and confirming it would not execute.
- **`conf.d/wp-security.conf`** — defines the `wp_login` rate-limit zone the snippet references.
  Without it nginx fails config-test, so it must be copied alongside.
- **`conf.d/00-cloudflare-realip.conf`** — copied for parity. Inert while records are DNS-only.
- **`fail2ban`** active with the sshd jail.
- **UFW** allowing only OpenSSH and Nginx Full.

### Staging is noindexed in four layers

`blog_public=0` alone is **not sufficient**, and this is worth internalising fleet-wide:

> **Modern WordPress ignores `blog_public` when generating `robots.txt`.** Read `do_robots()` in
> `wp-includes/functions.php` — it reads the option into `$public` and then never branches on it. A
> private site serves the same permissive robots.txt as a public one.

So staging carries: `blog_public=0`, the `noindex, nofollow` meta tag, an `X-Robots-Tag` header at
server level **and repeated inside the static-asset location** (a `location` with its own
`add_header` discards every inherited one), plus a hard-coded `Disallow: /` returned by nginx.

**Production has none of this, deliberately** — `blog_public=1`, no noindex, and robots.txt
advertising her sitemap. Never copy staging's suppression onto the live site.

## Traps

1. **WP salts moved out of VCS 2026-08-13.** Real salts live in the gitignored `wp-config-env.php`;
   the tracked `wp-config.php` carries guarded placeholders and **must require the env file above
   the salt block** (PHP is first-wins). Verify by proving the env value won, not that the
   placeholder is gone.
2. **Neither box can send email.** No MTA, no SMTP plugin — `wp_mail()` fails silently. A contact
   form that appears to work delivers nothing, and **you cannot hand Ellen a password-reset link** —
   her existing password is the only copy, and resets go through `wp user update` over wp-cli.
   Her Contact page uses a `mailto:` link rather than a form, so there is no visitor-facing
   dependency today.
   **This is fleet-wide and is a tracked follow-up** — an SMTP relay (Resend/Postmark free tier)
   would fix it everywhere and remove the "only Vincent can reset her password" dependency.
   *Note this is entirely separate from her actual email*, which is governed by the MX record and
   never touched by the website.
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

## Follow-ups

Ordered by deadline, not size. Nothing here is urgent tonight; all of it was deliberately deferred
from go-live rather than forgotten.

**⏰ Cancel JetHost — target mid-November 2026.** Vincent committed to this in writing, so if it slips
Ellen loses **$96** on her own card and it's his miss. December 15 is the *due* date, not necessarily
the *charge* date — WHMCS often generates invoices 7–30 days ahead and captures then. **Cancel at end
of billing period, not immediately.** Prerequisite: her mail must be off JetHost first, since the
`mail` A record is what keeps `ellen@ellenharvey.net` alive.

**Move her mail off JetHost.** `ellen@ellenharvey.net` is a pure forward to `eharvey.net@gmail.com`
(she confirmed that destination 2026-08-27). **Cloudflare Email Routing replaces it for $0.** Requires
her to click a verification link Cloudflare sends to that Gmail. Blocks the JetHost cancellation above.

**Fix the deploy remotes and Makefile.** `production` still points at the retired droplet and the
Makefile's `STAGING_HOST` is the old box. Actively misleading.

**Retire `ellenharvey.vincentragosta.io`** once Ellen has signed off on the live site.

**Consider proxying through Cloudflare (orange cloud).** Not done, and not urgent — but worth a
deliberate decision later. Free tier gives DDoS protection, a WAF, and edge caching, and caching in
particular is worth something on a 1 GB box. The real-IP config (`conf.d/00-cloudflare-realip.conf`)
is already installed, so logs and fail2ban would still see genuine client IPs.

Three things must be right before flipping any record orange:

1. **`mail`, `smtp`, `webmail`, `pop3`, `ftp` stay grey forever.** Cloudflare's proxy only handles
   HTTP/HTTPS ports — proxying them breaks those protocols outright.
2. **SSL mode must be Full (strict).** New zones often default to Flexible, which either loops or
   leaves the Cloudflare→origin leg unencrypted. The origin has a valid Let's Encrypt cert, so strict
   works.
3. **Certificate renewal is unaffected** — production uses DNS-01 via the Cloudflare API token, which
   doesn't care about proxy state. (Staging uses HTTP-01 and would need watching.)

Note the fleet does **not** proxy: `arthousenewyork.com`, `aviewfromthebridgebroadway.com`,
`celebrityautobiography.com` and `shucked.com` are all DNS-only. Ellen is not an outlier here.

### Fleet-wide, found during this migration

**🔓 nginx location ordering leaves `wp-config-env.php` unprotected on four vhosts.** The
`location ~ wp-config-env\.php { deny all; }` rule sits *after* the generic `location ~ \.php$`.
nginx matches regex locations in order and the first wins, so the file is handed to PHP-FPM and the
deny rule is dead config. **Fixed on Ellen's two vhosts 2026-08-27; still present on the old droplet:**

```
ellenharvey.vincentragosta.io   php=19, deny=28
staging.vincentragosta.io       php=22, deny=31
vincentragosta.dev              php=16, deny=25
vincentragosta.io               php=18, deny=28   ← production
```

**Nothing is leaking today** — PHP executes the file and it emits no output, so the DB password and
salts stay put. It is a latent exposure: the protection is not actually in force, and it becomes a
real disclosure if PHP-FPM is down or the handler changes. Precisely how the Shucked uploads issue
went from theoretical to a 200.

Fix is mechanical: move that one line above the PHP block, `nginx -t`, reload. **The ARTHOUSE droplets
have not been swept and probably share it.** This is the same lesson already written into
`wp-hardening.conf` for xmlrpc — the comment exists because the fleet learned it once already.

**🚨 `staging.itzenzo.tv` is publicly indexable.** No `X-Robots-Tag`, no robots meta, no robots.txt,
returns 200. A staging copy of the storefront is crawlable — duplicate content against `itzenzo.tv`
plus whatever test data it holds. One-line nginx fix. Written up in `vincentragosta.io/RUNBOOK.md`.

**`staging.vincentragosta.io` robots.txt** serves an HTML 404 — the same bare `location = /robots.txt`
bug. Cosmetic, since its header does the real work, but fix it when nearby.

**`wp_mail()` is broken fleet-wide.** No MTA on any droplet, so every site fails silently on password
resets, admin notices, and contact forms. An SMTP relay on a free tier fixes it everywhere. This is
the same finding as `droplet-no-outbound-mail`, now with a concrete client-facing consequence: Ellen
cannot recover her own password without Vincent.

## See also

- `akivili/engagements/ellen-harvey-engagement.md` — scope, client history, and the open hosting question
- `vincentragosta.io/RUNBOOK.md` — same droplet, more detail on its shared services
