<?php

namespace OCA\Stalwart\Models;

use JsonSerializable;

class EmailEntity implements JsonSerializable {
	public const TABLE = 'stalwart_emails';

	public function __construct(
		public int $cid,
		public string $uid,
		public string $email,
		public EmailType $type,
	) {
	}

	public function jsonSerialize(): array {
		return [
			'cid' => $this->cid,
			'uid' => $this->uid,
			'email' => $this->email,
			'type' => $this->type->jsonSerialize()
		];
	}

	/** @psalm-suppress PossiblyUnusedMethod */
	public static function fromRow(mixed $value): ?EmailEntity {
		if (is_array($value) && is_int($value['cid']) && is_string($value['uid'])) {
			return new EmailEntity(
				$value['cid'],
				$value['uid'],
				is_string($value['email']) ? $value['email'] : '',
				EmailType::fromRow($value['type']) ?? EmailType::Primary,
			);
		}
		return null;
	}
}
