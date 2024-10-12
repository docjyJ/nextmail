<?php

namespace OCA\Stalwart\Services;

use Exception;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\ConfigEntity;
use OCA\Stalwart\Models\EmailEntity;
use OCA\Stalwart\Models\EmailType;
use OCP\IConfig;

abstract readonly class ISqlService {
	/**
	 * @throws Exception
	 */
	protected function queryName(string $cid): string {
		if (preg_match('/\W/', $cid)) {
			throw new Exception('The configuration ID is invalid, only word characters allowed to prevent SQL injection');
		}
		$table = $this->dbNcPrefix . AccountEntity::TABLE;
		$colId = AccountEntity::COL_ID;
		$colType = AccountEntity::COL_TYPE;
		$colDisplay = AccountEntity::COL_DISPLAY;
		$colPassword = AccountEntity::COL_PASSWORD;
		$colQuota = AccountEntity::COL_QUOTA;
		$colConfig = ConfigEntity::COL_ID;
		$param = $this->dbParam;
		// SELECT name, type, secret, description, quota FROM accounts WHERE name = ? AND active = true
		// SELECT name, type, secret, description, quota FROM accounts WHERE name = $1 AND active = true
		return "SELECT $colId, $colType, $colDisplay, $colPassword, $colQuota FROM $table WHERE $colConfig = '$cid' AND $colId = $param";
	}

	// SELECT member_of FROM group_members WHERE name = ?
	// SELECT member_of FROM group_members WHERE name = $1
	protected function queryMembers(): string {
		$table = $this->dbNcPrefix . AccountEntity::TABLE;
		$colId = AccountEntity::COL_ID;
		$colMember = AccountEntity::COL_ID;
		$param = $this->dbParam;
		return "SELECT $colMember FROM $table WHERE $colId = $param LIMIT 0";
	}

	protected function queryRecipients(): string {
		$table = $this->dbNcPrefix . AccountEntity::TABLE;
		$colId = AccountEntity::COL_ID;
		$colEmail = EmailEntity::COL_EMAIL;
		$param = $this->dbParam;
		// SELECT name FROM emails WHERE address = ? ORDER BY name ASC
		// SELECT name FROM emails WHERE address = $1 ORDER BY name ASC
		return "SELECT $colId FROM $table WHERE  $colEmail = $param ORDER BY $colId ASC";
	}

	protected function queryEmails(): string {
		$table = $this->dbNcPrefix . EmailEntity::TABLE;
		$colId = AccountEntity::COL_ID;
		$colEmail = EmailEntity::COL_EMAIL;
		$colType = EmailEntity::COL_TYPE;
		$typeList = EmailType::List->value;
		$param = $this->dbParam;
		// SELECT address FROM emails WHERE name = ? AND type != 'list' ORDER BY type DESC, address ASC
		// SELECT address FROM emails WHERE name = $1 AND type != 'list' ORDER BY type DESC, address ASC
		return "SELECT $colEmail FROM $table WHERE $colId = $param AND $colType != '$typeList' ORDER BY $colType DESC, $colEmail ASC";
	}

	protected function queryVerify(): string {
		$table = $this->dbNcPrefix . EmailEntity::TABLE;
		$colEmail = EmailEntity::COL_EMAIL;
		$colType = EmailEntity::COL_TYPE;
		$typePrimary = EmailType::Primary->value;
		$concat = $this->concatVerify;
		// SELECT address FROM emails WHERE address LIKE CONCAT('%', ?, '%') AND type = 'primary' ORDER BY address LIMIT 5
		// SELECT address FROM emails WHERE address LIKE '%' || $1 || '%' AND type = 'primary' ORDER BY address LIMIT 5
		return "SELECT $colEmail FROM $table WHERE $colEmail LIKE $concat AND $colType = '$typePrimary' ORDER BY $colEmail LIMIT 5";
	}

	protected function queryExpand(): string {
		$table = $this->dbNcPrefix . EmailEntity::TABLE;
		$colId = AccountEntity::COL_ID;
		$colEmail = EmailEntity::COL_EMAIL;
		$colType = EmailEntity::COL_TYPE;
		$typePrimary = EmailType::Primary->value;
		$typeList = EmailType::List->value;
		$param = $this->dbParam;
		// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = ? AND l.type = 'list' ORDER BY p.address LIMIT 50
		// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = $1 AND l.type = 'list' ORDER BY p.address LIMIT 50
		return "SELECT p.$colEmail FROM $table AS p JOIN $table AS l USING ($colId) WHERE p.$colType = '$typePrimary' AND l.$colType = '$typeList' AND l.$colEmail = $param ORDER BY p.$colEmail LIMIT 50";
	}

	protected function queryDomains(): string {
		$table = $this->dbNcPrefix . EmailEntity::TABLE;
		$colEmail = EmailEntity::COL_EMAIL;
		$concat = $this->concatDomains;
		// SELECT 1 FROM emails WHERE address LIKE CONCAT('%@', ?) LIMIT 1
		// SELECT 1 FROM emails WHERE address LIKE '%@' || $1 LIMIT 1
		return "SELECT 1 FROM $table WHERE $colEmail LIKE $concat LIMIT 1";
	}

	protected string $dbHost;
	protected int $dbPort;
	protected string $dbName;
	protected string $dbUser;
	protected string $dbPassword;
	private string $dbNcPrefix;
	private string $dbParam;
	private string $concatVerify;
	private string $concatDomains;

	/**
	 * @throws Exception
	 */
	public function __construct(IConfig $config) {
		$host = $config->getSystemValueString('dbhost');
		if ($host === '') {
			throw new Exception('The database host looks empty but it should be set');
		}
		$this->dbHost = $host;

		$name = $config->getSystemValueString('dbname');
		if ($name === '') {
			throw new Exception('The database name looks empty but it should be set');
		}
		$this->dbName = $name;

		$prefix = $config->getSystemValueString('dbtableprefix');
		if (preg_match('/\W/', $prefix)) {
			throw new Exception('The database table prefix is invalid, only word characters allowed to prevent SQL injection');
		}
		$this->dbNcPrefix = $prefix;

		$port = $config->getSystemValueInt('dbport');
		$type = $config->getSystemValueString('dbtype');
		if ($type == 'mysql') {
			$this->dbPort = $port === 0 ? 3306 : $port;
			$this->dbParam = '?';
			$this->concatVerify = "CONCAT('%', ?, '%')";
			$this->concatDomains = "CONCAT('%@', ?)";
		} elseif ($type == 'pgsql') {
			$this->dbPort = $port === 0 ? 5432 : $port;
			$this->dbParam = '$1';
			$this->concatVerify = "'%' || $1 || '%'";
			$this->concatDomains = "'%@' || $1";
		} else {
			throw new Exception('This app only supports MySQL and PostgreSQL');
		}
		$this->dbUser = $config->getSystemValueString('dbuser');
		$this->dbPassword = $config->getSystemValueString('dbpassword');
	}

	abstract public function getStalwartConfig(string $cid): string;
}
