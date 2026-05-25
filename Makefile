# Ellen Harvey — Build system
# Usage: make [target]   (run `make help` for a list)

# ─── Paths ───────────────────────────────────────────────────────────────────

IX_DIR          := $(CURDIR)/wp-content/themes/ix
CHILD_THEME_DIR := $(CURDIR)/wp-content/themes/ellenharvey
MYTHUS_DIR      := $(CURDIR)/wp-content/mu-plugins/mythus

COMPOSER_DIRS := $(CURDIR) $(MYTHUS_DIR) $(IX_DIR) $(CHILD_THEME_DIR)
NPM_DIRS      := $(IX_DIR) $(CHILD_THEME_DIR)

LOCAL_URL := https://ellenharvey.ddev.site

# ─── Phony targets ───────────────────────────────────────────────────────────

.PHONY: help \
	start stop \
	install build watch clean autoload update wp

.DEFAULT_GOAL := help

##@ Help

help: ## Show available targets, grouped by section
	@awk 'BEGIN {FS = ":.*?## "} \
		/^##@ / {sub(/^##@ */, ""); printf "\n\033[1m%s\033[0m\n", $$0; next} \
		/^[a-zA-Z][a-zA-Z0-9_-]*:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' \
		$(MAKEFILE_LIST)

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

##@ Cleanup

clean: ## Remove vendor, node_modules, dist
	@echo "→ Removing vendor and node_modules..."
	rm -rf $(CURDIR)/vendor
	rm -rf $(MYTHUS_DIR)/vendor
	rm -rf $(IX_DIR)/vendor $(IX_DIR)/node_modules $(IX_DIR)/dist
	rm -rf $(CHILD_THEME_DIR)/vendor $(CHILD_THEME_DIR)/node_modules $(CHILD_THEME_DIR)/dist
	@echo "✓ Clean complete"
