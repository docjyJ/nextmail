<?php

namespace OCA\Nextmail;

/**
 * @psalm-api
 * @psalm-type NextmailServerConfig = array{
 *      id: string,
 *      endpoint: string,
 *      username: string,
 *      health: 'bad_network'|'bad_server'|'invalid'|'success'|'unauthorized',
 *  }
 * @psalm-type NextmailServerUser = array{
 *     id: string,
 *     displayName: string,
 *     email: ?string,
 *   }
 */
class ResponseDefinitions {
}
