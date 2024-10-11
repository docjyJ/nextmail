<?php

namespace OCA\Stalwart\Models;

use ValueError;

readonly class EmailEntity {
	public const TABLE = 'stalwart_emails';

	public function __construct(
		public AccountEntity $account,
		public string $email,
		public EmailType $type,
	) {
	}

	public static function parse(AccountEntity $account, mixed $value): self {
		if (!is_array($value)) {
			throw new ValueError('value must be an array');
		}
		if ($value['uid'] !== $account->uid) {
			throw new ValueError('uid mismatch');
		}
		if (!is_string($value['email'])) {
			throw new ValueError('email must be a string');
		}
		if (!is_string($value['type'])) {
			throw new ValueError('type must be a string');
		}
		return new self(
			$account,
			$value['email'],
			EmailType::from($value['type'])
		);
	}
}
