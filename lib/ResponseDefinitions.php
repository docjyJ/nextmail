<?php

namespace OCA\Nextmail;

/**
 * @psalm-api
 * @psalm-type NextmailServer = array{
 *      id: string,
 *      endpoint: string,
 *      username: string,
 *      health: 'bad_network'|'bad_server'|'invalid'|'success'|'unauthorized',
 *  }
 * @psalm-type NextmailUser = array{
 *     id: string,
 *     displayName: string,
 *     email: ?string,
 *   }
 */
class ResponseDefinitions {
}
