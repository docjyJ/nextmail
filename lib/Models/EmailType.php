<?php

namespace OCA\Stalwart\Models;

enum EmailType: string {
	case Primary = 'primary';
	case Alias = 'alias';
	case List = 'list';

	public static function fromMixed(mixed $value): ?self {
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		return null;
	}
}
