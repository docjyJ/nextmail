<?php

namespace OCA\Nextmail\SchemaV1;

use OCP\DB\Types;

class SchMember {
	public const USER_ID = 'user';
	public const GROUP_ID = 'group';


	public const TABLE = 'nextmail_members';
	public const DB = [
		'table' => self::TABLE,
		'columns' => [
			['name' => self::USER_ID, 'type' => Types::STRING, 'length' => 64],
			['name' => self::GROUP_ID, 'type' => Types::STRING, 'length' => 64],
		],
		'primary' => [self::USER_ID, self::GROUP_ID],
		'foreign' => [
			['table' => SchAccount::TABLE, 'columns' => [self::USER_ID]],
			['table' => SchAccount::TABLE, 'columns' => [self::GROUP_ID]],
		],
	];
}
