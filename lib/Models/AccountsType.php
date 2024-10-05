<?php

namespace OCA\Stalwart\Models;

use JsonSerializable;

enum AccountsType: string implements JsonSerializable {
	case Individual = 'individual';
	case Group = 'group';

	public function jsonSerialize(): string {
		return $this->value;
	}

	public static function fromRow(mixed $value): ?AccountsType {
		return is_string($value) ? match ($value) {
			'individual' => AccountsType::Individual,
			'group' => AccountsType::Group,
			default => null
		} : null;
	}
}
