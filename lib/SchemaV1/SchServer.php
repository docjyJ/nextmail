<?php

namespace OCA\Nextmail\SchemaV1;

use OCP\DB\Types;

class SchServer {
	public const ID = 'server_id';
	public const ENDPOINT = 'endpoint';
	public const USERNAME = 'username';
	public const PASSWORD = 'password';
	public const HEALTH = 'health';

	public const TABLE = 'nextmail_servers';
	public const DB = [
		'table' => self::TABLE,
		'columns' => [
			['name' => self::ID, 'type' => Types::STRING, 'length' => 32],
			['name' => self::ENDPOINT, 'type' => Types::STRING, 'length' => 128],
			['name' => self::USERNAME, 'type' => Types::STRING, 'length' => 128],
			['name' => self::PASSWORD, 'type' => Types::STRING, 'length' => 128],
			['name' => self::HEALTH, 'type' => Types::STRING, 'length' => 32],
		],
		'primary' => [self::ID],
		'foreign' => [],
	];
}
