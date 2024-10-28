<?php

namespace OCA\Nextmail\SchemaV1;

use OCP\DB\Types;

class SchEmail {
	public const EMAIL = 'email';
	public const TYPE = 'type';

	public const TABLE = 'nextmail_emails';
	public const DB = [
		'table' => self::TABLE,
		'columns' => [
			['name' => SchAccount::ID, 'type' => Types::STRING, 'length' => 64],
			['name' => self::EMAIL, 'type' => Types::STRING, 'length' => 128],
			['name' => self::TYPE, 'type' => Types::STRING, 'length' => 32],
		],
		'primary' => [SchAccount::ID, self::EMAIL],
		'foreign' => [
			['table' => SchAccount::TABLE, 'columns' => [SchAccount::ID], 'onDelete' => 'CASCADE'],
		],
	];
}
