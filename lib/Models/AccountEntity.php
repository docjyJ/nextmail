<?php

namespace OCA\Stalwart\Models;

use ValueError;

readonly class AccountEntity {
	public const TABLE = 'stalwart_accounts';
	public const COL_ID = 'uid';
	public const COL_TYPE = 'type';
	public const COL_DISPLAY = 'display_name';
	public const COL_PASSWORD = 'password';
	public const COL_QUOTA = 'quota';

	public function __construct(
		public string       $uid,
		public ConfigEntity $config,
		public string       $displayName,
		public string       $password,
		public AccountType  $type,
		public int          $quota,
	) {
	}

	public static function parse(ConfigEntity $conf, mixed $value): self {
		if (!is_array($value)) {
			throw new ValueError('value must be an array');
		}
		if (!is_string($value[self::COL_ID])) {
			throw new ValueError('uid must be a string');
		}
		if ($conf->cid !== $value[ConfigEntity::COL_ID]) {
			throw new ValueError('cid must be an integer');
		}
		if (!is_string($value[self::COL_DISPLAY])) {
			throw new ValueError('display_name must be a string');
		}
		if (!is_string($value[self::COL_PASSWORD])) {
			throw new ValueError('password must be a string');
		}
		if (!is_string($value[self::COL_TYPE])) {
			throw new ValueError('type must be a string');
		}
		if (!is_int($value[self::COL_QUOTA])) {
			throw new ValueError('quota must be an integer');
		}
		return new self(
			$value[self::COL_ID],
			$conf,
			$value[self::COL_DISPLAY],
			$value[self::COL_PASSWORD],
			AccountType::from($value[self::COL_TYPE]),
			$value[self::COL_QUOTA],
		);
	}
}
