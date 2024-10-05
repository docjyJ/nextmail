<?php

namespace OCA\Stalwart\Models;

use JsonSerializable;

enum EmailType: string implements JsonSerializable {
	case Primary = 'primary';
	case Alias = 'alias';
	case List = 'list';

	public function jsonSerialize(): string {
		return $this->value;
	}

	public static function fromRow(mixed $value): ?EmailType {
		return is_string($value) ? match ($value) {
			'primary' => EmailType::Primary,
			'alias' => EmailType::Alias,
			'list' => EmailType::List,
			default => null
		} : null;
	}
}
