<?php

namespace OCA\Stalwart\Models;

use ValueError;

readonly class AccountEntity {
	public const TABLE = 'stalwart_accounts';

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
		if (!is_string($value['uid'])) {
			throw new ValueError('uid must be a string');
		}
		if ($value['cid'] !== $conf->cid) {
			throw new ValueError('cid must be an integer');
		}
		if (!is_string($value['display_name'])) {
			throw new ValueError('display_name must be a string');
		}
		if (!is_string($value['password'])) {
			throw new ValueError('password must be a string');
		}
		if (!is_string($value['type'])) {
			throw new ValueError('type must be a string');
		}
		if (!is_int($value['quota'])) {
			throw new ValueError('quota must be an integer');
		}
		return new self(
			$value['uid'],
			$conf,
			$value['display_name'],
			$value['password'],
			AccountType::from($value['type']),
			$value['quota']
		);
	}
}
