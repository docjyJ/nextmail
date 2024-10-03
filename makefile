MAKEFLAGS += -j2
COMPOSE_CLI=podman compose

run-dev-81-30-mysql: start-81-30-mysql watch

build-all: build-api build-js

install:
	@npm install
	@composer install

start-81-30-mysql:
	PHP_VERSION=81 HUB_VERSION=30 SQL=mysql $(COMPOSE_CLI) up main

stop-81-30-mysql:
	PHP_VERSION=81 HUB_VERSION=30 SQL=mysql $(COMPOSE_CLI) down

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
