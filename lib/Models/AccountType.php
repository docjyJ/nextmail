<?php

namespace OCA\Stalwart\Models;

enum AccountType: string {
	case Individual = 'individual';
	case Group = 'group';

	public static function fromMixed(mixed $value): ?self {
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		return null;
	}
}
