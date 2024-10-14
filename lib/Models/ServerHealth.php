<?php

namespace OCA\Nextmail\Models;

enum ServerHealth: string {
	case Success = 'success';
	case Unauthorized = 'unauthorized';
	case BadServer = 'bad_server';
	case BadNetwork = 'bad_network';
	case Invalid = 'invalid';

	public static function fromMixed(mixed $value): ?self {
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		return null;
	}
}
