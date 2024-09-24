<?php

namespace OCA\Stalwart\Models;

class StalwartServer extends MailServer {
	private const URL_PATTERN = '/^https?:\\/\\/([a-zA-Z0-9-]+\\.)*[a-zA-Z0-9-]+(:\\d{1,5})?\\/api$/';
	private function __construct(
		private string $endpoint,
		private string $username,
		private string $password,
	) {
	}

	public function getURL(string $path): string {
		return $this->endpoint . $path;
	}

	public function getAuthorization(): string {
		return 'Basic ' . base64_encode($this->username . ':' . $this->password);
	}

	public function setEndpoint(string $endpoint): void {
		$this->endpoint = $endpoint;
	}

	public function setCredentials(string $username, string $password): void {
		$this->username = $username;
		if ($password !== '') {
			$this->password = $password;
		}
	}

	public function isValid(): bool {
		return $this->username !== '' && $this->password !== '' && preg_match(self::URL_PATTERN, $this->endpoint) === 1;
	}

	/** @return array{endpoint: string, username: string, password: string} */
	public function jsonSerialize(): array {
		return [
			'endpoint' => $this->endpoint,
			'username' => $this->username,
			'password' => $this->password,
		];
	}

	public static function default(): StalwartServer {
		return new StalwartServer('', '', '');
	}

	public static function jsonDeserialize(array $input): ?self {
		if (!(key_exists('endpoint', $input) && is_string($input['endpoint']))) {
			return null;
		}
		if (!(key_exists('username', $input) && is_string($input['username']))) {
			return null;
		}
		if (!(key_exists('password', $input) && is_string($input['password']))) {
			return null;
		}
		return new StalwartServer($input['endpoint'], $input['username'], $input['password']);
	}
}
