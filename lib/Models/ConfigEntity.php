<?php

namespace OCA\Stalwart\Models;

use JsonSerializable;
use OCA\Stalwart\ResponseDefinitions;
use ValueError;

/** @psalm-import-type StalwartServerConfig from ResponseDefinitions */
readonly class ConfigEntity implements JsonSerializable {
	public const TABLE = 'stalwart_configs';
	public const COL_ID = 'cid';
	public const COL_ENDPOINT = 'endpoint';
	public const COL_USERNAME = 'username';
	public const COL_PASSWORD = 'password';
	public const COL_HEALTH = 'health';
	private const URL_PATTERN = '/^https?:\\/\\/([a-z0-9-]+\\.)*[a-z0-9-]+(:\\d{1,5})?\\/api$/';


	public function __construct(
		public string $cid,
		public string $endpoint,
		public string $username,
		public string $password,
		public ServerStatus $health,
	) {
	}

	public static function newEmpty(): self {
		return new self(str_replace('.', '_', uniqid('nc_', true)), '', '', '', ServerStatus::Invalid);
	}

	public static function parse(mixed $value): ConfigEntity {
		if (!is_array($value)) {
			throw new ValueError('value must be an array');
		}
		if (!is_string($value[self::COL_ID])) {
			throw new ValueError('cid must be a string');
		}
		if (!is_string($value[self::COL_ENDPOINT])) {
			throw new ValueError('endpoint must be a string');
		}
		if (!is_string($value[self::COL_USERNAME])) {
			throw new ValueError('username must be a string');
		}
		if (!is_string($value[self::COL_PASSWORD])) {
			throw new ValueError('password must be a string');
		}
		if (!is_string($value[self::COL_HEALTH])) {
			throw new ValueError('health must be a string');
		}
		return new self(
			$value[self::COL_ID],
			$value[self::COL_ENDPOINT],
			$value[self::COL_USERNAME],
			$value[self::COL_PASSWORD],
			ServerStatus::from($value[self::COL_HEALTH]),
		);
	}

	/** @return StalwartServerConfig */
	public function jsonSerialize(): array {
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

	public function updateCredential(string $endpoint, string $username, string $password): self {
		return new self(
			$this->cid,
			$endpoint,
			$username,
			$password !== '' ? $password : $this->password,
			$this->health,
		);
	}

	public function updateHealth(ServerStatus $health): self {
		return new self(
			$this->cid,
			$this->endpoint,
			$this->username,
			$this->password,
			$health,
		);
	}
}
