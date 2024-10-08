<?php

namespace OCA\Stalwart\Models;

use OCA\Stalwart\FromMixed;

class EmailEntity {
	public const TABLE = 'stalwart_emails';

	public function __construct(
		public readonly AccountEntity $account,
		public readonly string $email,
		public readonly EmailType $type,
	) {
	}

	public static function fromMixed(AccountEntity $account, mixed $value): ?self {
		return is_array($value)
			&& $account->config->cid === $value['cid']
			&& $account->uid === $value['uid']
			&& ($email = FromMixed::string($value['email'])) !== null
			&& ($type = EmailType::fromMixed($value['type'])) !== null
			? new self($account, $email, $type) : null;
	}
}
