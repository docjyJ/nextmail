<?php

namespace OCA\Nextmail\Services;

use JsonException;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\SchemaV1\Columns;
use OCA\Nextmail\SchemaV1\Tables;
use ValueError;

readonly class StalwartSqlService {
	private function queryName(string $cid): string {
		if (preg_match('/\W/', $cid)) {
			throw new ValueError('The configuration ID is invalid, only word characters allowed to prevent SQL injection');
		}
		$table = $this->db->table(Tables::ACCOUNTS);
		$accountId = Columns::ACCOUNT_ID;
		$role = Columns::ACCOUNT_ROLE;
		$hash = Columns::ACCOUNT_HASH;
		$quota = Columns::ACCOUNT_QUOTA;
		$displayName = Columns::ACCOUNT_NAME;
		$serverId = Columns::SERVER_ID;
		$param = $this->db->param();
		// SELECT name, type, secret, description, quota FROM accounts WHERE name = ? AND active = true
		// SELECT name, type, secret, description, quota FROM accounts WHERE name = $1 AND active = true
		return "SELECT $accountId, $role, $hash, $displayName, $quota FROM $table WHERE $serverId = '$cid' AND $accountId = $param";
	}

	private function queryMembers(): string {
		$table = $this->db->table(Tables::EMAILS);
		$colId = Columns::USER_ID;
		$colMember = Columns::GROUP_ID;
		$param = $this->db->param();
		// SELECT member_of FROM group_members WHERE name = ?
		// SELECT member_of FROM group_members WHERE name = $1
		return "SELECT $colMember FROM $table WHERE $colId = $param LIMIT 0";
	}

	private function queryRecipients(): string {
		$table = $this->db->table(Tables::EMAILS);
		$colId = Columns::ACCOUNT_ID;
		$colEmail = Columns::EMAIL_ID;
		$param = $this->db->param();
		// SELECT name FROM emails WHERE address = ? ORDER BY name ASC
		// SELECT name FROM emails WHERE address = $1 ORDER BY name ASC
		return "SELECT $colId FROM $table WHERE  $colEmail = $param ORDER BY $colId ASC";
	}

	private function queryEmails(): string {
		$table = $this->db->table(Tables::EMAILS);
		$colId = Columns::ACCOUNT_ID;
		$colEmail = Columns::EMAIL_ID;
		$colType = Columns::EMAIL_TYPE;
		$typeList = EmailType::List->value;
		$param = $this->db->param();
		// SELECT address FROM emails WHERE name = ? AND type != 'list' ORDER BY type DESC, address ASC
		// SELECT address FROM emails WHERE name = $1 AND type != 'list' ORDER BY type DESC, address ASC
		return "SELECT $colEmail FROM $table WHERE $colId = $param AND $colType != '$typeList' ORDER BY $colType DESC, $colEmail ASC";
	}

	private function queryVerify(): string {
		$table = $this->db->table(Tables::EMAILS);
		$colEmail = Columns::EMAIL_ID;
		$colType = Columns::EMAIL_TYPE;
		$typePrimary = EmailType::Primary->value;
		$concat = $this->db->concatVerify();
		// SELECT address FROM emails WHERE address LIKE CONCAT('%', ?, '%') AND type = 'primary' ORDER BY address LIMIT 5
		// SELECT address FROM emails WHERE address LIKE '%' || $1 || '%' AND type = 'primary' ORDER BY address LIMIT 5
		return "SELECT $colEmail FROM $table WHERE $colEmail LIKE $concat AND $colType = '$typePrimary' ORDER BY $colEmail LIMIT 5";
	}

	private function queryExpand(): string {
		$table = $this->db->table(Tables::EMAILS);
		$colId = Columns::ACCOUNT_ID;
		$colEmail = Columns::EMAIL_ID;
		$colType = Columns::EMAIL_TYPE;
		$typePrimary = EmailType::Primary->value;
		$typeList = EmailType::List->value;
		$param = $this->db->param();
		// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = ? AND l.type = 'list' ORDER BY p.address LIMIT 50
		// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = $1 AND l.type = 'list' ORDER BY p.address LIMIT 50
		return "SELECT p.$colEmail FROM $table AS p JOIN $table AS l USING ($colId) WHERE p.$colType = '$typePrimary' AND l.$colType = '$typeList' AND l.$colEmail = $param ORDER BY p.$colEmail LIMIT 50";
	}

	private function queryDomains(): string {
		$table = $this->db->table(Tables::EMAILS);
		$colEmail = Columns::EMAIL_ID;
		$concat = $this->db->concatDomains();
		// SELECT 1 FROM emails WHERE address LIKE CONCAT('%@', ?) LIMIT 1
		// SELECT 1 FROM emails WHERE address LIKE '%@' || $1 LIMIT 1
		return "SELECT 1 FROM $table WHERE $colEmail LIKE $concat LIMIT 1";
	}


	public function __construct(
		private ConnexionSqlService $db,
	) {
	}

	/**
	 * @throws JsonException
	 */
	public function getStalwartConfig(string $cid): string {
		return json_encode([
			[
				'prefix' => "directory.$cid",
				'type' => 'Clear',
			],
			[
				'prefix' => "store.$cid",
				'type' => 'Clear',
			],
			[
				'assert_empty' => false,
				'prefix' => "store.$cid",
				'type' => 'Insert',
				'values' => [
					['type', $this->db->type()],
					['host', $this->db->host()],
					['port', $this->db->port()],
					['database', $this->db->database()],
					['user', $this->db->username()],
					['password', $this->db->password()],
					['tls.enabled', 'false'],
					['tls.allow-invalid-certs', 'false'],
					['pool.max-connections', '10'],
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
					['columns.class', Columns::ACCOUNT_ROLE],
					['columns.description', Columns::ACCOUNT_NAME],
					['columns.secret', Columns::ACCOUNT_HASH],
					['columns.quota', Columns::ACCOUNT_QUOTA],
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
