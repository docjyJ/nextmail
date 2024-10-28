MAKEFLAGS += -j2
COMPOSE_CLI=podman compose

run-dev-mysql: start-mysql watch

run-dev-pgsql: start-pgsql watch

build-all: build-api build-js

install:
	@npm install
	@composer install

start-mysql:
	docker compose -f compose.mysql.yml -p mysql up

stop-mysql:
	docker compose -f compose.mysql.yml -p mysql down

remove-mysql:
	docker compose -f compose.mysql.yml -p mysql down --volumes

fix-mysql:
	docker compose -f compose.mysql.yml -p mysql exec -u 33 nextcloud php occ config:system:set --type bool --value true allow_local_remote_servers
	docker compose -f compose.mysql.yml -p mysql exec stalwart cat /opt/stalwart-mail/etc/config.toml
	docker compose -f compose.mysql.yml -p mysql exec stalwart sed -i -e 's/^\(authentication\.fallback-admin\.user\).*$$/\1="@dmin"/' -e 's/^\(authentication\.fallback-admin\.secret\).*$$/\1="$$6$$utdfyugdsqfyg$$L.KXVq918T7vxnwZYt6u\/zGhGpbHlG7CQgV.PBPQSwm4Q9nrATmpraxgtCFihNQCid5FBh3pDpI8\/MdpH86h\/\/"/' /opt/stalwart-mail/etc/config.toml
	docker compose -f compose.mysql.yml -p mysql exec stalwart cat /opt/stalwart-mail/etc/config.toml

start-pgsql:
	docker compose -f compose.pgsql.yml -p pgsql up

stop-pgsql:
	docker compose -f compose.pgsql.yml -p pgsql down

remove-pgsql:
	docker compose -f compose.pgsql.yml -p pgsql down --volumes

fix-pgsql:
	docker compose -f compose.pgsql.yml -p pgsql exec -u 33 nextcloud php occ config:system:set --type bool --value true allow_local_remote_servers
	docker compose -f compose.pgsql.yml -p pgsql exec stalwart sed -i -e 's/^\(authentication\.fallback-admin\.user\).*$$/\1="@dmin"/' -e 's/^\(authentication\.fallback-admin\.secret\).*$$/\1="$$2a$$12$$QpSVltmUGyfVoeEIaet4nOWv85NNcUCIPWs\/cT15PtBW7No91aTwC"/' /opt/stalwart-mail/etc/config.toml
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
