<?php

namespace OCA\Nextmail\Models;

use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use ValueError;

readonly class EmailEntity {
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
		if ($account->id !== $value[SchAccount::ID]) {
			throw new ValueError('uid mismatch');
		}
		if (!is_string($value[SchEmail::EMAIL])) {
			throw new ValueError('email must be a string');
		}
		if (!is_string($value[SchEmail::TYPE])) {
			throw new ValueError('type must be a string');
		}
		return new self(
			$account,
			$value[SchEmail::EMAIL],
			EmailType::from($value[SchEmail::TYPE]),
		);
	}
}
