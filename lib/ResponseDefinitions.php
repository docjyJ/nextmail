<?php

namespace OCA\Stalwart;

/**
 * @psalm-api
 * @psalm-type StalwartServerConfig = array{
 *      id: int,
 *      endpoint: string,
 *      username: string,
 *      health: 'bad_network'|'bad_server'|'invalid'|'success'|'unauthorized',
 *  }
 * @psalm-type StalwartServerUser = array{
 *     uid: string,
 *     displayName: string,
 *     email: ?string,
 *   }
 */
class ResponseDefinitions {
}
