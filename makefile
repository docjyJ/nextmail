MAKEFLAGS += -j2


run-dev: start watch

build-all: build-api build-js

install:
	@npm install
	@composer install

start:
	podman compose up

stop:
	podman compose stop

watch:
	npm run watch

remove:
	podman compose down

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
