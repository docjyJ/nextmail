<?php

namespace OCA\Stalwart\Models;

use OCA\Stalwart\FromMixed;

class AccountEntity {
	public const TABLE = 'stalwart_accounts';

	public function __construct(
		public readonly ConfigEntity $config,
		public readonly string       $uid,
		public readonly string       $displayName = '',
		public readonly string       $password = '',
		public readonly AccountType  $type = AccountType::Individual,
		public readonly int          $quota = 0,
	) {
	}

	public static function fromMixed(ConfigEntity $conf, mixed $value): ?self {
		return is_array($value)
			&& $value['cid'] === $conf->cid
			&& ($uid = FromMixed::string($value['uid'])) !== null
			? new self(
				$conf,
				$uid,
				FromMixed::string($value['display_name']) ?? '',
				FromMixed::string($value['password']) ?? '',
				AccountType::fromMixed($value['type']) ?? AccountType::Individual,
				FromMixed::int($value['quota']) ?? 0,
			) : null;
	}
}
