<?php

namespace OCA\Stalwart\Models;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use OCA\Stalwart\FromMixed;
use OCA\Stalwart\ResponseDefinitions;

/** @psalm-import-type StalwartServerConfig from ResponseDefinitions */
class ConfigEntity {
	public const TABLE = 'stalwart_configs';
	private const URL_PATTERN = '/^https?:\\/\\/([a-z0-9-]+\\.)*[a-z0-9-]+(:\\d{1,5})?\\/api$/';


	public function __construct(
		public readonly int $cid,
		public readonly string $endpoint = '',
		public readonly string $username = '',
		public readonly string $password = '',
		public readonly ServerStatus $health = ServerStatus::Invalid,
		public readonly DateTimeImmutable $expires = new DateTimeImmutable(),
	) {
	}

	public static function fromMixed(mixed $value): ?self {
		return is_array($value)
			&& ($cid = FromMixed::int($value['cid'])) !== null
			? new self(
				$cid,
				FromMixed::string($value['endpoint']) ?? '',
				FromMixed::string($value['username']) ?? '',
				FromMixed::string($value['password']) ?? '',
				ServerStatus::fromMixed($value['health']) ?? ServerStatus::Invalid,
				FromMixed::dateTime($value['expires']) ?? new DateTimeImmutable(),
			) : null;
	}

	/** @return StalwartServerConfig */
	public function toArrayData(): array {
		return [
			'id' => $this->cid,
			'endpoint' => $this->endpoint,
			'username' => $this->username,
			'health' => $this->health->value,
		];
	}

	public function getUrl(string $subpart = ''): ?string {
		return preg_match(self::URL_PATTERN, $this->endpoint) === 1
			? $this->endpoint . $subpart
			: null;
	}

	public function getBasicAuth(): ?string {
		return $this->username !== '' && $this->password !== ''
			? 'Basic ' . base64_encode($this->username . ':' . $this->password)
			: null;
	}

	public function hasExpired(): bool {
		return $this->expires < new DateTime();
	}

	public function updateCredential(string $endpoint, string $username, string $password): self {
		return new self(
			$this->cid,
			$endpoint,
			$username,
			$password !== '' ? $password : $this->password,
			$this->health,
			$this->expires
		);
	}

	public function updateHealth(ServerStatus $health): self {
		$date = new DateTimeImmutable();
		$interval = $health === ServerStatus::Success
			? new DateInterval('P1D')
			: new DateInterval('PT1H');
		return new self(
			$this->cid,
			$this->endpoint,
			$this->username,
			$this->password,
			$health,
			$date->add($interval)
		);
	}
}
