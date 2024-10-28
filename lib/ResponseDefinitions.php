<?php

namespace OCA\Nextmail;

/**
 * @psalm-api
 * @psalm-type NextmailServer = array{
 *      id: string,
 *      name: string,
 *      endpoint: string,
 *      username: string,
 *      health: 'bad_network'|'bad_server'|'invalid'|'success'|'unauthorized',
 *  }
 * @psalm-type NextmailUser = array{
 *     id: string,
 *     server_id: ?string,
 *     name: string,
 *     email: ?string,
 *     admin: bool,
 *     quota: ?int,
 *   }
 */
class ResponseDefinitions {
}
