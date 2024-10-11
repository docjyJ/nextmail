<?php

namespace OCA\Stalwart\Services;

use Exception;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\EmailEntity;
use OCP\IConfig;

readonly class MysqlService implements ISqlService {
	// SELECT name, type, secret, description, quota FROM accounts WHERE name = ? AND active = true
	private const QUERY_NAME = <<<SQL
SELECT uid, type, display_name, password, quota FROM oc_stalwart_accounts
WHERE cid = ':cid' AND uid = ?
SQL;

	// SELECT member_of FROM group_members WHERE name = ?
	private const QUERY_MEMBERS = <<<SQL
SELECT 'TODO'
WHERE 'TODO' = ?
LIMIT 1
SQL;

	// SELECT name FROM emails WHERE address = ? ORDER BY name ASC
	private const QUERY_RECIPIENTS = <<<SQL
SELECT uid FROM oc_stalwart_emails
JOIN oc_stalwart_accounts USING (uid)
WHERE cid = ':cid' AND email = ?
ORDER BY uid
SQL;
	// SELECT address FROM emails WHERE name = ? AND type != 'list' ORDER BY type DESC, address ASC
	private const QUERY_EMAILS = <<<SQL
SELECT email FROM oc_stalwart_emails
JOIN oc_stalwart_accounts USING (uid)
WHERE cid = ':cid' AND uid = ? AND type != 'list'
ORDER BY type DESC, email
SQL;

	// SELECT address FROM emails WHERE address LIKE CONCAT('%', ?, '%') AND type = 'primary' ORDER BY address LIMIT 5
	private const QUERY_VERIFY = <<<SQL
SELECT email FROM oc_stalwart_emails
JOIN oc_stalwart_accounts USING (uid)
WHERE cid = ':cid' AND email LIKE CONCAT('%', ?, '%') AND type = 'primary'
SQL;

	// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = ? AND l.type = 'list' ORDER BY p.address LIMIT 50
	private const QUERY_EXPAND = <<<SQL
SELECT p.email FROM oc_stalwart_emails AS p
JOIN oc_stalwart_emails AS l USING (uid)
JOIN oc_stalwart_accounts USING (uid)
WHERE cid = :cid AND p.type = 'primary' AND l.email = ? AND l.type = 'alias'
SQL;

	// SELECT 1 FROM emails WHERE address LIKE CONCAT('%@', ?) LIMIT 1
	private const QUERY_DOMAINS = <<<SQL
SELECT 1 FROM oc_stalwart_emails
WHERE cid = :cid AND email LIKE CONCAT('%@', ?)
LIMIT 1
SQL;

	/**
	 * @throws Exception
	 */
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @throws Exception
	 */
	public function getStalwartConfig(string $cid): string {
		$dbType = $this->config->getSystemValueString('dbtype');
		if ($dbType !== 'mysql') {
			throw new Exception('This app only supports MySQL');
		}
		$dbHost = $this->config->getSystemValueString('dbhost');
		if ($dbHost === '') {
			throw new Exception('No database host configured');
		}
		$dbName = $this->config->getSystemValueString('dbname');
		if ($dbName === '') {
			throw new Exception('No database name configured');
		}
		$dbUser = $this->config->getSystemValueString('dbuser');
		if ($dbUser === '') {
			throw new Exception('No database user configured');
		}
		$dbPassword = $this->config->getSystemValueString('dbpassword');
		if ($dbPassword === '') {
			throw new Exception('No database password configured');
		}
		$dbTablePrefix = $this->config->getSystemValueString('dbtableprefix');
		// to prevent SQL injection dbTablePrefix must match /^[a-zA-Z0-9_]*$/
		if (!preg_match('/^\w+$/', $dbTablePrefix)) {
			throw new Exception('Invalid database table prefix');
		}
		$dbPort = $this->config->getSystemValueInt('dbport');
		if ($dbPort === 0) {
			$dbPort = 3306;
		}
		$tableAccounts = $dbTablePrefix . AccountEntity::TABLE;
		$tableEmail = $dbTablePrefix . EmailEntity::TABLE;

		return json_encode([
			[
				'prefix' => 'directory.' . $cid,
				'type' => 'Clear',
			],
			[
				'prefix' => 'store.' . $cid,
				'type' => 'Clear',
			],
			[
				'assert_empty' => false,
				'prefix' => 'store.' . $cid,
				'type' => 'Insert',
				'values' => [
					[ 'type', $dbType ],
					[ 'host', $dbHost ],
					[ 'port', strval($dbPort) ],
					[ 'database', $dbName ],
					[ 'user', $dbUser ],
					[ 'password', $dbPassword ],
					[ 'tls.enabled', 'false' ],
					[ 'tls.allow-invalid-certs', 'false'],
					[ 'pool.max-connections', '10' ],
					[ 'pool.min-connections', '5' ],
					[ 'timeout', '15s'],
					[ 'compression', 'lz4'],
					[ 'purge.frequency', '0 3 *'],
					['read-from-replicas', 'true'],
					['query.name', self::parseQuery(self::QUERY_NAME, $cid, $tableAccounts, $tableEmail)],
					['query.members', self::parseQuery(self::QUERY_MEMBERS, $cid, $tableAccounts, $tableEmail)],
					['query.recipients', self::parseQuery(self::QUERY_RECIPIENTS, $cid, $tableAccounts, $tableEmail)],
					['query.emails', self::parseQuery(self::QUERY_EMAILS, $cid, $tableAccounts, $tableEmail)],
					['query.verify', self::parseQuery(self::QUERY_VERIFY, $cid, $tableAccounts, $tableEmail)],
					['query.expand', self::parseQuery(self::QUERY_EXPAND, $cid, $tableAccounts, $tableEmail)],
					['query.domains', self::parseQuery(self::QUERY_DOMAINS, $cid, $tableAccounts, $tableEmail)]
				],
			],
			[
				'assert_empty' => false,
				'prefix' => 'directory.' . $cid,
				'type' => 'Insert',
				'values' => [
					[ 'type', 'sql' ],
					[ 'store', $cid ],
					[ 'columns.class', 'type' ],
					[ 'columns.description', 'display_name' ],
					[ 'columns.secret', 'password' ],
					[ 'columns.quota', 'quota' ],
					[ 'cache.entries', '500' ],
					[ 'cache.ttl.positive', '1h' ],
					[ 'cache.ttl.negative', '10m' ],
				],
			],
			[
				'assert_empty' => false,
				'prefix' => null,
				'type' => 'Insert',
				'values' => [
					[ 'storage.directory', $cid ],
				],
			],
		], JSON_THROW_ON_ERROR);
	}

	private static function parseQuery(string $query, string $cid, string $tableUsers, string $tableAlias): string {
		$query = str_replace("\n", ' ', $query);
		$query = str_replace('oc_stalwart_accounts', $tableUsers, $query);
		$query = str_replace('oc_stalwart_aliases', $tableAlias, $query);
		return str_replace(':cid', $cid, $query);
	}
}
