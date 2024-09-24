<?php


namespace OCA\Stalwart\Models;

use JsonSerializable;

interface JSONModel extends JsonSerializable {
	public function jsonSerialize(): array;
	public static function jsonDeserialize(array $input): ?self;
}
