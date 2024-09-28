<?php

namespace OCA\Stalwart\Db;

use JsonSerializable;
use OCA\Stalwart\ResponseDefinitions;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getEndpoint()
 * @method void setEndpoint(string $endpoint)
 * @method string getUsername()
 * @method void setUsername(string $username)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @psalm-import-type StalwartJsonServerConfig from ResponseDefinitions
 */
class ServerConfig extends Entity implements JsonSerializable {
	private const URL_PATTERN = '/^https?:\\/\\/([a-zA-Z0-9-]+\\.)*[a-zA-Z0-9-]+(:\\d{1,5})?\\/api$/';

	protected string $endpoint;
	protected string $username;
	protected string $password;


	public function __construct() {
		$this->addType('endpoint', 'string');
		$this->addType('username', 'string');
		$this->addType('password', 'string');
		$this->id = -1;
		$this->endpoint = '';
		$this->username = '';
		$this->password = '';
	}

	public function getURL(string $path): string {
		return $this->endpoint . $path;
	}

	public function getAuthorization(): string {
		return 'Basic ' . base64_encode($this->username . ':' . $this->password);
	}

	public function isValid(): bool {
		return $this->username !== '' && $this->password !== '' && preg_match(self::URL_PATTERN, $this->endpoint) === 1;
	}

	/** @return StalwartJsonServerConfig */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'endpoint' => $this->endpoint,
			'username' => $this->username,
			'password' => $this->password,
		];
	}
}
