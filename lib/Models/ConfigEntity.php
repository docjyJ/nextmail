<?php

namespace OCA\Nextmail\Models;

use JsonSerializable;
use OCA\Nextmail\ResponseDefinitions;
use OCA\Nextmail\SchemaV1\Columns;
use ValueError;

/** @psalm-import-type NextmailServerConfig from ResponseDefinitions */
readonly class ConfigEntity implements JsonSerializable {
	private const URL_PATTERN = '/^https?:\\/\\/([a-z0-9-]+\\.)*[a-z0-9-]+(:\\d{1,5})?\\/api$/';


	public function __construct(
		public string       $id,
		public string       $endpoint,
		public string       $username,
		public string       $password,
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
		if (!is_string($value[Columns::SERVER_ID])) {
			throw new ValueError('cid must be a string');
		}
		if (!is_string($value[Columns::SERVER_ENDPOINT])) {
			throw new ValueError('endpoint must be a string');
		}
		if (!is_string($value[Columns::SERVER_USERNAME])) {
			throw new ValueError('username must be a string');
		}
		if (!is_string($value[Columns::SERVER_PASSWORD])) {
			throw new ValueError('password must be a string');
		}
		if (!is_string($value[Columns::SERVER_HEALTH])) {
			throw new ValueError('health must be a string');
		}
		return new self(
			$value[Columns::SERVER_ID],
			$value[Columns::SERVER_ENDPOINT],
			$value[Columns::SERVER_USERNAME],
			$value[Columns::SERVER_PASSWORD],
			ServerStatus::from($value[Columns::SERVER_HEALTH]),
		);
	}

	/** @return NextmailServerConfig */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
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
			$this->id,
			$endpoint,
			$username,
			$password !== '' ? $password : $this->password,
			$this->health,
		);
	}

	public function updateHealth(ServerStatus $health): self {
		return new self(
			$this->id,
			$this->endpoint,
			$this->username,
			$this->password,
			$health,
		);
	}
}
