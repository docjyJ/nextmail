<?php

namespace OCA\Stalwart\Models;

use JsonSerializable;

class AccountEntity implements JsonSerializable {
	public const TABLE = 'stalwart_accounts';

	public function __construct(
		public int          $cid,
		public string       $uid,
		public AccountsType $type,
		public string       $displayName,
		public string       $password,
		public int          $quota,
	) {
	}

	public function jsonSerialize(): array {
		return [
			'cid' => $this->cid,
			'uid' => $this->uid,
			'type' => $this->type->jsonSerialize(),
			'display_name' => $this->displayName,
			'password' => $this->password,
			'quota' => $this->quota,
		];
	}

	public static function fromRow(mixed $value): ?AccountEntity {
		if (is_array($value) && is_int($value['cid']) && is_string($value['uid'])) {
			return new AccountEntity(
				$value['cid'],
				$value['uid'],
				AccountsType::fromRow($value['type']) ?? AccountsType::Individual,
				is_string($value['display_name']) ? $value['display_name'] : '',
				is_string($value['password']) ? $value['password'] : '',
				is_int($value['quota']) ? $value['quota'] : 0,
			);
		}
		return null;
	}
}
