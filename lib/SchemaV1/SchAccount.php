<?php

namespace OCA\Nextmail\SchemaV1;

use OCP\DB\Types;

class SchAccount {
	public const ID = 'account_id';
	public const NAME = 'display_name';
	public const HASH = 'hash';
	public const QUOTA = 'quota';
	public const ROLE = 'role';

	public const TABLE = 'nextmail_accounts';
	public const DB = [
		'table' => self::TABLE,
		'columns' => [
			['name' => self::ID, 'type' => Types::STRING, 'length' => 64],
			['name' => SchServer::ID, 'type' => Types::STRING, 'length' => 32],
			['name' => self::NAME, 'type' => Types::STRING, 'length' => 32],
			['name' => self::ROLE, 'type' => Types::STRING, 'length' => 16],
			['name' => self::HASH, 'type' => Types::STRING, 'length' => 256, 'nullable' => true],
			['name' => self::QUOTA, 'type' => Types::INTEGER, 'nullable' => true],
		],
		'primary' => [self::ID],
		'foreign' => [
			['table' => SchServer::TABLE, 'columns' => [SchServer::ID]],
		],
	];
}
