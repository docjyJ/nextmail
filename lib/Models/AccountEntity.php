<?php

namespace OCA\Stalwart\Models;

class AccountEntity {
	public const TABLE = 'stalwart_accounts';

	public function __construct(
		public ConfigEntity $config,
		public string       $uid,
		public string       $displayName = '',
		public string       $password = '',
		public AccountType  $type = AccountType::Individual,
		public int          $quota = 0,
	) {
	}
}
