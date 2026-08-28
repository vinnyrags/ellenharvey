# Ellen Harvey — Build system
# Usage: make [target]   (run `make help` for a list)

# ─── Paths ───────────────────────────────────────────────────────────────────

IX_DIR          := $(CURDIR)/wp-content/themes/ix
CHILD_THEME_DIR := $(CURDIR)/wp-content/themes/ellenharvey
MYTHUS_DIR      := $(CURDIR)/wp-content/mu-plugins/mythus

COMPOSER_DIRS := $(CURDIR) $(MYTHUS_DIR) $(IX_DIR) $(CHILD_THEME_DIR)
NPM_DIRS      := $(IX_DIR) $(CHILD_THEME_DIR)

LOCAL_URL   := https://ellenharvey.ddev.site
UPLOADS_DIR := $(CURDIR)/wp-content/uploads

# ─── Staging (ellenharvey.vincentragosta.io) ─────────────────────────────────
# Hosted on the vincentragosta.io droplet. Code deploys via `git push
# production main` (post-receive hook runs composer/npm/build). Content
# (DB + uploads) is pushed separately — it lives in the DB, not in git.
# Both environments live on Ellen's droplet (161.35.119.59), isolated by
# database, Redis DB number and cache-key salt. develop -> staging, main -> production.
#
# The old preview at ellenharvey.vincentragosta.io still exists on Vincent's
# droplet and is reachable as the `preview` git remote. It is NOT deployed to by
# any target here; retire it once Ellen no longer references that link.

STAGING_HOST := root@161.35.119.59
STAGING_DIR  := /var/www/staging.ellenharvey.net
STAGING_WP   := $(STAGING_DIR)/wp
STAGING_URL  := https://staging.ellenharvey.net

PRODUCTION_HOST := root@161.35.119.59
PRODUCTION_DIR  := /var/www/ellenharvey.net
PRODUCTION_WP   := $(PRODUCTION_DIR)/wp
PRODUCTION_URL  := https://ellenharvey.net

# ─── Phony targets ───────────────────────────────────────────────────────────

.PHONY: help \
	start stop \
	install build watch clean autoload update wp \
	deploy-staging deploy-production push-content pull-content ssh

.DEFAULT_GOAL := help

##@ Help

help: ## Show available targets, grouped by section
	@awk 'BEGIN {FS = ":.*?## "} \
		/^##@ / {sub(/^##@ */, ""); printf "\n\033[1m%s\033[0m\n", $$0; next} \
		/^[a-zA-Z][a-zA-Z0-9_-]*:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' \
		$(MAKEFILE_LIST)

##@ Setup

setup: ## First-run: auth.json + DDEV config, then start + pull content from production
	@test -f auth.json || cp ../vincentragosta.io/auth.json ./auth.json 2>/dev/null || { echo "✗ auth.json missing (needed for ACF Pro) — copy it from another Mythus/IX project"; exit 1; }
	@test -d .ddev || ddev config --project-type=wordpress --docroot="" --project-name=ellenharvey
	@$(MAKE) start
	@$(MAKE) pull-content
	@echo "✓ Setup complete — $(LOCAL_URL)"

##@ Local environment

start: ## Start DDEV, install deps, build assets
	@echo "Starting DDEV environment..."
	ddev start
	@$(MAKE) install
	@$(MAKE) build
	@echo ""
	@echo "✓ Project is running at $(LOCAL_URL)"

stop: ## Stop DDEV
	ddev stop

##@ Dependencies

install: ## Composer install (root) + npm install in IX and child theme
	@echo "→ Composer install (project root)..."
	ddev composer install
	@for dir in $(NPM_DIRS); do \
		if [ -d "$$dir" ]; then \
			echo "→ npm install in $$dir..."; \
			ddev exec --dir /var/www/html$${dir#$(CURDIR)} npm install; \
		fi; \
	done
	@echo "✓ Dependencies installed"

autoload: ## Regenerate composer autoloaders for root, Mythus, IX, child theme
	@echo "Regenerating autoloaders..."
	@for dir in $(COMPOSER_DIRS); do \
		if [ -f "$$dir/composer.json" ]; then \
			echo "→ $$dir"; \
			(cd $$dir && composer dump-autoload --no-interaction) || exit $$?; \
		fi; \
	done
	@echo "✓ Autoloaders regenerated"

update: ## Update composer dependencies (root + Mythus + IX + child)
	@echo "Updating dependencies..."
	@for dir in $(COMPOSER_DIRS); do \
		if [ -f "$$dir/composer.json" ]; then \
			echo "→ $$dir"; \
			(cd $$dir && composer update --no-interaction) || exit $$?; \
		fi; \
	done
	@echo "✓ Dependencies updated"

##@ Build

build: ## Build all theme assets (IX parent first, then child)
	@echo "→ Building IX parent theme assets..."
	ddev exec --dir /var/www/html/wp-content/themes/ix npm run build
	@echo "→ Building child theme assets..."
	ddev exec --dir /var/www/html/wp-content/themes/ellenharvey npm run build
	@echo "✓ Build complete"

watch: ## Watch + rebuild child theme assets on change
	ddev exec --dir /var/www/html/wp-content/themes/ellenharvey npm run start

##@ WordPress

# Pass-through to ddev wp. Usage: make wp ARGS="post-type list"
wp: ## Run a WP-CLI command (use ARGS="...")
	$(if $(ARGS),,$(error Usage: make wp ARGS="post-type list"))
	ddev wp $(ARGS)

##@ Deploy

# The server-side post-receive hooks are branch-gated: staging deploys ONLY
# `develop`, production deploys ONLY `main`. Pushing the wrong branch moves the
# ref and the hook skips it, so nothing appears to happen. That is why each
# target checks the branch before pushing.
#
# Neither target prints a success banner it has not earned — both compare the
# remote ref against the local SHA afterwards. An unconditional "✓ deployed"
# is how a no-op push gets mistaken for a release.

deploy-staging: ## Deploy develop → staging.ellenharvey.net
	@if [ "$$(git rev-parse --abbrev-ref HEAD)" != "develop" ]; then \
		echo "✗ Refusing to deploy: not on 'develop' (on '$$(git rev-parse --abbrev-ref HEAD)')."; exit 1; \
	fi
	@echo "→ Pushing develop to GitHub (origin)..."
	git push origin develop
	@echo "→ Pushing develop to staging (auto-builds on server)..."
	git push staging develop
	@REMOTE=$$(git ls-remote staging develop | cut -f1); LOCAL=$$(git rev-parse develop); \
	if [ "$$REMOTE" = "$$LOCAL" ]; then echo "✓ staging is at $$LOCAL — verify at $(STAGING_URL)"; \
	else echo "✗ MISMATCH — staging=$$REMOTE local=$$LOCAL"; exit 1; fi

deploy-production: ## Merge develop → main and deploy to ellenharvey.net
	@echo "→ Merging develop into main..."
	git checkout main
	git merge develop --ff-only
	@echo "→ Pushing main to GitHub (origin)..."
	git push origin main
	@echo "→ Pushing main to production (auto-builds on server)..."
	git push production main
	git checkout develop
	@REMOTE=$$(git ls-remote production main | cut -f1); LOCAL=$$(git rev-parse main); \
	if [ "$$REMOTE" = "$$LOCAL" ]; then echo "✓ production is at $$LOCAL — verify at $(PRODUCTION_URL)"; \
	else echo "✗ MISMATCH — production=$$REMOTE local=$$LOCAL"; exit 1; fi

push-content: ## Push local DB + uploads to staging (overwrites staging content)
	@echo "→ Exporting local database..."
	ddev export-db --gzip=false --file=/tmp/eh-export.sql
	@echo "→ Uploading + importing database on staging..."
	scp -q /tmp/eh-export.sql $(STAGING_HOST):/tmp/eh-export.sql
	ssh $(STAGING_HOST) "wp db import /tmp/eh-export.sql --path=$(STAGING_WP) --allow-root"
	@echo "→ Rewriting URLs ($(LOCAL_URL) → $(STAGING_URL))..."
	ssh $(STAGING_HOST) "wp search-replace '$(LOCAL_URL)' '$(STAGING_URL)' --path=$(STAGING_WP) --allow-root --precise --all-tables --quiet"
	@echo "→ Discouraging search engines (staging mirror) + flushing..."
	ssh $(STAGING_HOST) "wp option update blog_public 0 --path=$(STAGING_WP) --allow-root --quiet && wp cache flush --path=$(STAGING_WP) --allow-root --quiet && wp rewrite flush --hard --path=$(STAGING_WP) --allow-root --quiet"
	@echo "→ Syncing uploads..."
	rsync -az --delete $(UPLOADS_DIR)/ $(STAGING_HOST):$(STAGING_DIR)/wp-content/uploads/
	ssh $(STAGING_HOST) "chown -R www-data:www-data $(STAGING_DIR)/wp-content/uploads"
	@echo "→ Cleaning up..."
	rm -f /tmp/eh-export.sql
	ssh $(STAGING_HOST) "rm -f /tmp/eh-export.sql"
	@echo "✓ Content pushed to $(STAGING_URL)"

pull-content: ## Pull staging DB + uploads to local DDEV (overwrites local content)
	@echo "→ Exporting staging database..."
	ssh $(STAGING_HOST) "wp db export /tmp/eh-export.sql --path=$(STAGING_WP) --allow-root"
	scp -q $(STAGING_HOST):/tmp/eh-export.sql /tmp/eh-export.sql
	@echo "→ Importing into DDEV..."
	ddev import-db --file=/tmp/eh-export.sql
	@echo "→ Rewriting URLs ($(STAGING_URL) → $(LOCAL_URL))..."
	ddev wp search-replace '$(STAGING_URL)' '$(LOCAL_URL)' --precise --all-tables --quiet
	ddev wp option update blog_public 1 --quiet
	ddev wp cache flush --quiet && ddev wp rewrite flush --hard --quiet
	@echo "→ Syncing uploads..."
	rsync -az --delete $(STAGING_HOST):$(STAGING_DIR)/wp-content/uploads/ $(UPLOADS_DIR)/
	@echo "→ Cleaning up..."
	rm -f /tmp/eh-export.sql
	ssh $(STAGING_HOST) "rm -f /tmp/eh-export.sql"
	@echo "✓ Local synced from $(STAGING_URL)"

ssh: ## SSH into the staging deploy directory
	ssh -t $(STAGING_HOST) "cd $(STAGING_DIR) && bash"

##@ Cleanup

clean: ## Remove vendor, node_modules, dist
	@echo "→ Removing vendor and node_modules..."
	rm -rf $(CURDIR)/vendor
	rm -rf $(MYTHUS_DIR)/vendor
	rm -rf $(IX_DIR)/vendor $(IX_DIR)/node_modules $(IX_DIR)/dist
	rm -rf $(CHILD_THEME_DIR)/vendor $(CHILD_THEME_DIR)/node_modules $(CHILD_THEME_DIR)/dist
	@echo "✓ Clean complete"
