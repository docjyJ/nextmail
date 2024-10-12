<?php

namespace OCA\Stalwart\Services;

use Exception;
use OCA\Stalwart\Models\AccountEntity;

readonly class MysqlService extends ISqlService {


	/**
	 * @throws Exception
	 */
	public function getStalwartConfig(string $cid): string {
		return json_encode([
			[
				'prefix' => "directory.$cid" ,
				'type' => 'Clear',
			],
			[
				'prefix' => "store.$cid" ,
				'type' => 'Clear',
			],
			[
				'assert_empty' => false,
				'prefix' => "store.$cid" ,
				'type' => 'Insert',
				'values' => [
					['type', 'mysql'],
					['host', $this->dbHost],
					['port', strval($this->dbPort)],
					['database', $this->dbName],
					['user', $this->dbUser],
					['password', $this->dbPassword],
					['tls.enabled', 'false'],
					['tls.allow-invalid-certs', 'false'],
					['pool.max-connections', '10'],
					['pool.min-connections', '5'],
					['timeout', '15s'],
					['compression', 'lz4'],
					['purge.frequency', '0 3 *'],
					['read-from-replicas', 'true'],
					['query.name', $this->queryName($cid)],
					['query.members', $this->queryMembers()],
					['query.recipients', $this->queryRecipients()],
					['query.emails', $this->queryEmails()],
					['query.verify', $this->queryVerify()],
					['query.expand', $this->queryExpand()],
					['query.domains', $this->queryDomains()],
				],
			],
			[
				'assert_empty' => false,
				'prefix' => 'directory.' . $cid,
				'type' => 'Insert',
				'values' => [
					['type', 'sql'],
					['store', $cid],
					['columns.class', AccountEntity::COL_TYPE],
					['columns.description', AccountEntity::COL_DISPLAY],
					['columns.secret', AccountEntity::COL_PASSWORD],
					['columns.quota', AccountEntity::COL_QUOTA],
					['cache.entries', '500'],
					['cache.ttl.positive', '1h'],
					['cache.ttl.negative', '10m'],
				],
			],
			[
				'assert_empty' => false,
				'prefix' => null,
				'type' => 'Insert',
				'values' => [
					['storage.directory', $cid],
				],
			],
		], JSON_THROW_ON_ERROR);
	}
}
