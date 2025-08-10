# Quest Makefile

# Variables
app_name=nextcloudquest
project_dir=.
build_dir=$(project_dir)/build
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates

# Development
.PHONY: dev
dev:
	npm run dev

.PHONY: build
build:
	npm run build

.PHONY: watch
watch:
	npm run dev

# Dependencies
.PHONY: install-deps
install-deps:
	composer install
	npm ci

.PHONY: update-deps
update-deps:
	composer update
	npm update

# Linting
.PHONY: lint
lint:
	npm run lint
	npm run stylelint
	composer run cs:check

.PHONY: lint-fix
lint-fix:
	npm run lint:fix
	npm run stylelint:fix
	composer run cs:fix

# Testing
.PHONY: test
test:
	composer run test:unit
	composer run test:integration

.PHONY: test-unit
test-unit:
	composer run test:unit

.PHONY: test-integration
test-integration:
	composer run test:integration

# Build package
.PHONY: appstore
appstore:
	rm -rf $(build_dir)
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/.git \
	--exclude=/.github \
	--exclude=/build \
	--exclude=/node_modules \
	--exclude=/vendor \
	--exclude=/.gitignore \
	--exclude=/.eslintrc.js \
	--exclude=/composer.json \
	--exclude=/composer.lock \
	--exclude=/package.json \
	--exclude=/package-lock.json \
	--exclude=/Makefile \
	--exclude=/webpack.config.js \
	--exclude=/tests \
	--exclude=/src \
	$(project_dir)/ $(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)

# Clean
.PHONY: clean
clean:
	rm -rf $(build_dir)
	rm -rf node_modules
	rm -rf vendor
	rm -rf js/*

# Full clean and rebuild
.PHONY: distclean
distclean: clean
	rm -rf composer.lock
	rm -rf package-lock.json

.PHONY: ci
ci: install-deps lint test build