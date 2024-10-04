<?php

namespace OCA\Stalwart\Services;

use Exception;
use OCP\IConfig;

class MysqlService implements ISqlService {
	private const QUERY_NAME = <<<SQL
SELECT uid, type, display_name, password, quota FROM :oc_stalwart_users
WHERE config_id = :config_id AND uid = ?
SQL;
	private const QUERY_MEMBERS = <<<SQL
SELECT '' as member_of LIMIT 0;
SQL;
	private const QUERY_RECIPIENTS = <<<SQL
SELECT uid FROM :oc_accounts_data
JOIN :oc_stalwart_users USING (uid)
WHERE config_id = :config_id AND name = 'email' AND value = ?;
SQL;
	private const QUERY_EMAILS = <<<SQL
SELECT value FROM :oc_accounts_data
JOIN :oc_stalwart_users USING (uid)
WHERE config_id = :config_id AND name = 'email' AND uid = ?;
SQL;
	private const QUERY_VERIFY = <<<SQL
SELECT value FROM :oc_accounts_data
JOIN :oc_stalwart_users USING (uid)
WHERE config_id = :config_id AND name = 'email' AND value LIKE CONCAT('%', ?, '%') ORDER BY value LIMIT 5;
SQL;
	private const QUERY_EXPAND = <<<SQL
SELECT '' as value LIMIT 0;
SQL;
	private const QUERY_DOMAINS = <<<SQL
SELECT 1 FROM :oc_accounts_data
JOIN :oc_stalwart_users USING (uid)
WHERE config_id = :config_id AND name = 'email' AND CONCAT('%@', ?) LIMIT 1;
SQL;

	private readonly string $dbType;
	private readonly string $dbHost;
	private readonly int $dbPort;
	private readonly string $dbName;
	private readonly string $dbUser;
	private readonly string $dbPassword;
	private readonly bool $dbTls;
	private readonly string $dbTablePrefix;
	/**
	 * @throws Exception
	 */
	public function __construct(
		private readonly IConfig $config,
	) {
		$this->dbType = $this->config->getSystemValueString('dbtype');
		if ($this->dbType !== 'mysql') {
			throw new Exception('This app only supports MySQL');
		}
		$this->dbHost = $this->config->getSystemValueString('dbhost');
		if ($this->dbHost === '') {
			throw new Exception('No database host configured');
		}
		$this->dbName = $this->config->getSystemValueString('dbname');
		if ($this->dbName === '') {
			throw new Exception('No database name configured');
		}
		$this->dbUser = $this->config->getSystemValueString('dbuser');
		if ($this->dbUser === '') {
			throw new Exception('No database user configured');
		}
		$this->dbPassword = $this->config->getSystemValueString('dbpassword');
		if ($this->dbPassword === '') {
			throw new Exception('No database password configured');
		}
		$this->dbTablePrefix = $this->config->getSystemValueString('dbtableprefix');
		// to prevent SQL injection dbTablePrefix must match /^[\w]+$/
		if (!preg_match('/^\w+$/', $this->dbTablePrefix)) {
			throw new Exception('Invalid database table prefix');
		}
		$this->dbPort = $this->config->getSystemValueInt('dbport', 3306);
		$this->dbTls = false;
	}

	public function getStalwartConfig(int $config_id): array {
		$config_name = $this->dbTablePrefix . $config_id;
		$oc_stalwart_users = $this->dbTablePrefix . 'stalwart_users';
		$query_name = str_replace(':oc_stalwart_users', $oc_stalwart_users, str_replace(':config_id', $config_id, self::QUERY_NAME));
		$query_members = self::QUERY_MEMBERS;
		$query_recipients = str_replace(':oc_stalwart_users', $oc_stalwart_users, str_replace(':oc_accounts_data', $this->dbTablePrefix . 'accounts_data', str_replace(':config_id', $config_id, self::QUERY_RECIPIENTS)));
		$query_emails = str_replace(':oc_stalwart_users', $oc_stalwart_users, str_replace(':oc_accounts_data', $this->dbTablePrefix . 'accounts_data', str_replace(':config_id', $config_id, self::QUERY_EMAILS)));
		$query_verify = str_replace(':oc_stalwart_users', $oc_stalwart_users, str_replace(':oc_accounts_data', $this->dbTablePrefix . 'accounts_data', str_replace(':config_id', $config_id, self::QUERY_VERIFY)));
		$query_expand = self::QUERY_EXPAND;
		$query_domains = str_replace(':oc_stalwart_users', $oc_stalwart_users, str_replace(':oc_accounts_data', $this->dbTablePrefix . 'accounts_data', str_replace(':config_id', $config_id, self::QUERY_DOMAINS)));


		return [
			[
				'prefix' => 'directory.' . $config_name,
				'type' => 'Clear',
			],
			[
				'prefix' => 'store.' . $config_name,
				'type' => 'Clear',
			],
			[
				'assert_empty' => false,
				'prefix' => 'store.' . $config_name,
				'type' => 'Insert',
				'values' => [
					[ 'type', $this->dbType ],
					[ 'host', $this->dbHost ],
					[ 'port', strval($this->dbPort) ],
					[ 'database', $this->dbName ],
					[ 'user', $this->dbUser ],
					[ 'password', $this->dbPassword ],
					[ 'tls.enabled', $this->dbTls ? 'true' : 'false' ],
					[ 'tls.allow-invalid-certs', 'false'],
					[ 'pool.max-connections', '10' ],
					[ 'pool.min-connections', '30' ],
					[ 'timeout', '15s'],
					[ 'compression', 'lz4'],
					[ 'purge.frequency', '0 3 *'],
					['read-from-replicas', 'true'],
					['query.name', $query_name],
					['query.members', $query_members],
					['query.recipients', $query_recipients],
					['query.emails', $query_emails],
					['query.verify', $query_verify],
					['query.expand', $query_expand],
					['query.domains', $query_domains]
				],
			],
			[
				'assert_empty' => false,
				'prefix' => 'directory.' . $config_name,
				'type' => 'Insert',
				'values' => [
					[ 'type', 'sql' ],
					[ 'store', $config_name ],
					[ 'columns.class', 'type' ],
					[ 'columns.description', 'displayName' ],
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
					[ 'storage.directory.', $config_name ],
				],
			],
		];
	}
}
