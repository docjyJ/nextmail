<?php

namespace OCA\Stalwart\Models;

use DateTime;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use OCA\Stalwart\ResponseDefinitions;

/** @psalm-import-type StalwartServerConfig from ResponseDefinitions */
class ConfigEntity implements JsonSerializable {
	public const TABLE = 'stalwart_configs';

	public function __construct(
		public int          $cid,
		public string       $endpoint,
		public string       $username,
		public string       $password,
		public ServerStatus $health,
		public DateTime     $expires,
	) {
	}

	public function jsonSerialize(): array {
		return [
			'cid' => $this->cid,
			'endpoint' => $this->endpoint,
			'username' => $this->username,
			'password' => $this->password,
			'health' => $this->health->jsonSerialize(),
			'expires' => $this->expires->format(DateTimeInterface::ATOM)
		];
	}

	private static function parseDate(mixed $value): DateTime {
		if (is_string($value)) {
			try {
				return new DateTime($value);
			} catch (Exception) {
			}
		}
		return new DateTime();
	}

	public static function fromRow(mixed $value): ?ConfigEntity {
		if (is_array($value) && is_int($value['cid'])) {
			return new ConfigEntity(
				$value['cid'],
				is_string($value['endpoint']) ? $value['endpoint'] : '',
				is_string($value['username']) ? $value['username'] : '',
				is_string($value['password']) ? $value['password'] : '',
				ServerStatus::fromRow($value['health']) ?? ServerStatus::Invalid,
				self::parseDate($value['expires'])
			);
		}
		return null;
	}

	/**
	 * @return StalwartServerConfig
	 */
	public function toData(): array {
		return [
			'id' => $this->cid,
			'endpoint' => $this->endpoint,
			'username' => $this->username,
			'health' => $this->health->value,
		];
	}
}
