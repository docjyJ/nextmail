<?php

namespace OCA\Nextmail\Services;

use OCP\IConfig;
use ValueError;

readonly class ConnexionSqlService {
	private string $host;
	private int $port;
	private string $database;
	private string $username;
	private string $password;
	private string $prefix;
	private SqlType $type;

	public function __construct(IConfig $config) {
		$this->type = SqlType::from($config->getSystemValueString('dbtype'));

		$host = $config->getSystemValueString('dbhost');
		if ($host === '') {
			throw new ValueError('Value for dbhost is empty');
		}
		$this->host = $host;

		$port = $config->getSystemValueInt('dbport');
		$this->port = $port !== 0 ? $port : match ($this->type) {
			SqlType::Mysql => 3306,
			SqlType::Pgsql => 5432
		};

		$database = $config->getSystemValueString('dbname');
		if ($database === '') {
			throw new ValueError('Value for dbname is empty');
		}
		$this->database = $database;

		$this->username = $config->getSystemValueString('dbuser');
		$this->password = $config->getSystemValueString('dbpassword');
		$this->prefix = $config->getSystemValueString('dbtableprefix');
	}

	public function table(string $name): string {
		return $this->prefix . $name;
	}


	public function param(): string {
		return match ($this->type) {
			SqlType::Mysql => '?',
			SqlType::Pgsql => '$1',
		};
	}

	public function concatVerify(): string {
		return match ($this->type) {
			SqlType::Mysql => "CONCAT('%', ?, '%')",
			SqlType::Pgsql => "'%' || $1 || '%'",
		};
	}

	public function concatDomains(): string {
		return match ($this->type) {
			SqlType::Mysql => "CONCAT('%@', ?)",
			SqlType::Pgsql => "'%@' || $1",
		};
	}

	public function host(): string {
		return $this->host;
	}

	public function port(): string {
		return strval($this->port);
	}

	public function database(): string {
		return $this->database;
	}

	public function username(): string {
		return $this->username;
	}

	public function password(): string {
		return $this->password;
	}

	public function type(): string {
		return match ($this->type) {
			SqlType::Mysql => 'mysql',
			SqlType::Pgsql => 'postgresql',
		};
	}
}
