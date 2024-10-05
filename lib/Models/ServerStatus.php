<?php

namespace OCA\Stalwart\Models;

use JsonSerializable;

enum ServerStatus: string implements JsonSerializable {
	case Success = 'success';
	case NoAdmin = 'unprivileged';
	case Unauthorized = 'unauthorized';
	case BadServer = 'bad_server';
	case BadNetwork = 'bad_network';
	case Invalid = 'invalid';

	public function jsonSerialize(): string {
		return $this->value;
	}
	public static function fromRow(mixed $value): ?ServerStatus {
		return is_string($value) ? match ($value) {
			'success' => ServerStatus::Success,
			'unprivileged' => ServerStatus::NoAdmin,
			'unauthorized' => ServerStatus::Unauthorized,
			'bad_server' => ServerStatus::BadServer,
			'bad_network' => ServerStatus::BadNetwork,
			'invalid' => ServerStatus::Invalid,
			default => null
		} : null;
	}
}
