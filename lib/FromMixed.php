<?php

namespace OCA\Stalwart;

use DateTimeImmutable;
use Exception;

class FromMixed {
	public static function int(mixed $value): ?int {
		return is_int($value) ? $value : null;
	}

	public static function string(mixed $value): ?string {
		return is_string($value) ? $value : null;
	}

	public static function dateTime(mixed $value): ?DateTimeImmutable {
		try {
			return is_string($value) ? new DateTimeImmutable($value) : null;
		} catch (Exception) {
			return null;
		}
	}
}
