<?php

namespace OCA\Stalwart\Models;

class EmailEntity {
	public const TABLE = 'stalwart_emails';

	public function __construct(
		public AccountEntity $account,
		public string $email,
		public EmailType $type,
	) {
	}
}
