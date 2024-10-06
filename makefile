MAKEFLAGS += -j2
COMPOSE_CLI=podman compose

run-dev-mysql: start-mysql watch

build-all: build-api build-js

install:
	@npm install
	@composer install

start-mysql:
	$(COMPOSE_CLI) -f compose.mysql.yml up

stop-mysql:
	$(COMPOSE_CLI) -f compose.mysql.yml down

remove-mysql:
	$(COMPOSE_CLI) -f compose.mysql.yml down --volumes

fix-mysql:
	$(COMPOSE_CLI) -f compose.mysql.yml exec -u 33 nextcloud php occ config:system:set --type bool --value true allow_local_remote_servers

watch:
	npm run watch

lint-php:
	composer run lint
	composer run cs:fix
	composer run psalm

build-api: lint-php
	composer run openapi

lint-js:
	npm run stylelint:fix
	npm run lint:fix

build-js: lint-js
	npm run build
