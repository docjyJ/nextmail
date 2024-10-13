<?php

namespace OCA\Nextmail\Models;

use OCA\Nextmail\Schema\Columns;
use ValueError;

readonly class AccountEntity {
	public function __construct(
		public string       $uid,
		public ConfigEntity $config,
		public string       $displayName,
		public ?string       $password,
		public AccountRole  $type,
		public ?int          $quota,
	) {
	}

	public static function parse(ConfigEntity $conf, mixed $value): self {
		if (!is_array($value)) {
			throw new ValueError('value must be an array');
		}
		if (!is_string($value[Columns::ACCOUNT_ID])) {
			throw new ValueError('uid must be a string');
		}
		if ($conf->id !== $value[Columns::SERVER_ID]) {
			throw new ValueError('cid must be an integer');
		}
		if (!is_string($value[Columns::ACCOUNT_NAME])) {
			throw new ValueError('display_name must be a string');
		}
		if (!is_string($value[Columns::ACCOUNT_ROLE])) {
			throw new ValueError('type must be a string');
		}
		return new self(
			$value[Columns::ACCOUNT_ID],
			$conf,
			$value[Columns::ACCOUNT_NAME],
			is_string($value[Columns::ACCOUNT_HASH]) ? $value[Columns::ACCOUNT_HASH] : null,
			AccountRole::from($value[Columns::ACCOUNT_ROLE]),
			is_int($value[Columns::ACCOUNT_QUOTA]) ? $value[Columns::ACCOUNT_QUOTA] : null,
		);
	}
}
