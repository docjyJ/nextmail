<?php

namespace OCA\Nextmail\Services;

use JsonException;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchMember;
use OCA\Nextmail\SchemaV1\SchServer;
use ValueError;

readonly class StalwartSqlService {
	private function queryName(string $server_id): string {
		if (preg_match('/\W/', $server_id)) {
			throw new ValueError('The configuration ID is invalid, only word characters allowed to prevent SQL injection');
		}
		$table = $this->db->table(SchAccount::TABLE);
		$accountId = SchAccount::ID;
		$role = SchAccount::ROLE;
		$hash = SchAccount::HASH;
		$quota = SchAccount::QUOTA;
		$displayName = SchAccount::NAME;
		$serverId = SchServer::ID;
		$param = $this->db->param();
		// SELECT name, type, secret, description, quota FROM accounts WHERE name = ? AND active = true
		// SELECT name, type, secret, description, quota FROM accounts WHERE name = $1 AND active = true
		return "SELECT $accountId, $role, $hash, $displayName, $quota FROM $table WHERE $serverId = '$server_id' AND $accountId = $param";
	}

	private function queryMembers(): string {
		$table = $this->db->table(SchMember::TABLE);
		$colId = SchMember::USER_ID;
		$colMember = SchMember::GROUP_ID;
		$param = $this->db->param();
		// SELECT member_of FROM group_members WHERE name = ?
		// SELECT member_of FROM group_members WHERE name = $1
		return "SELECT $colMember FROM $table WHERE $colId = $param LIMIT 0";
	}

	private function queryRecipients(): string {
		$table = $this->db->table(SchEmail::TABLE);
		$colId = SchAccount::ID;
		$colEmail = SchEmail::EMAIL;
		$param = $this->db->param();
		// SELECT name FROM emails WHERE address = ? ORDER BY name ASC
		// SELECT name FROM emails WHERE address = $1 ORDER BY name ASC
		return "SELECT $colId FROM $table WHERE  $colEmail = $param ORDER BY $colId ASC";
	}

	private function queryEmails(): string {
		$table = $this->db->table(SchEmail::TABLE);
		$colId = SchAccount::ID;
		$colEmail = SchEmail::EMAIL;
		$colType = SchEmail::TYPE;
		$typeList = EmailType::List->value;
		$param = $this->db->param();
		// SELECT address FROM emails WHERE name = ? AND type != 'list' ORDER BY type DESC, address ASC
		// SELECT address FROM emails WHERE name = $1 AND type != 'list' ORDER BY type DESC, address ASC
		return "SELECT $colEmail FROM $table WHERE $colId = $param AND $colType != '$typeList' ORDER BY $colType DESC, $colEmail ASC";
	}

	private function queryVerify(): string {
		$table = $this->db->table(SchEmail::TABLE);
		$colEmail = SchEmail::EMAIL;
		$colType = SchEmail::TYPE;
		$typePrimary = EmailType::Primary->value;
		$concat = $this->db->concatVerify();
		// SELECT address FROM emails WHERE address LIKE CONCAT('%', ?, '%') AND type = 'primary' ORDER BY address LIMIT 5
		// SELECT address FROM emails WHERE address LIKE '%' || $1 || '%' AND type = 'primary' ORDER BY address LIMIT 5
		return "SELECT $colEmail FROM $table WHERE $colEmail LIKE $concat AND $colType = '$typePrimary' ORDER BY $colEmail LIMIT 5";
	}

	private function queryExpand(): string {
		$table = $this->db->table(SchEmail::TABLE);
		$colId = SchAccount::ID;
		$colEmail = SchEmail::EMAIL;
		$colType = SchEmail::TYPE;
		$typePrimary = EmailType::Primary->value;
		$typeList = EmailType::List->value;
		$param = $this->db->param();
		// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = ? AND l.type = 'list' ORDER BY p.address LIMIT 50
		// SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = $1 AND l.type = 'list' ORDER BY p.address LIMIT 50
		return "SELECT p.$colEmail FROM $table AS p JOIN $table AS l USING ($colId) WHERE p.$colType = '$typePrimary' AND l.$colType = '$typeList' AND l.$colEmail = $param ORDER BY p.$colEmail LIMIT 50";
	}

	private function queryDomains(): string {
		$table = $this->db->table(SchEmail::TABLE);
		$colEmail = SchEmail::EMAIL;
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
	public function getStalwartConfig(string $server_id): string {
		return json_encode([
			[
				'prefix' => "directory.$server_id",
				'type' => 'Clear',
			],
			[
				'prefix' => "store.$server_id",
				'type' => 'Clear',
			],
			[
				'assert_empty' => false,
				'prefix' => "store.$server_id",
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
					['query.name', $this->queryName($server_id)],
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
				'prefix' => 'directory.' . $server_id,
				'type' => 'Insert',
				'values' => [
					['type', 'sql'],
					['store', $server_id],
					['columns.class', SchAccount::ROLE],
					['columns.description', SchAccount::NAME],
					['columns.secret', SchAccount::HASH],
					['columns.quota', SchAccount::QUOTA],
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
					['storage.directory', $server_id],
				],
			],
		], JSON_THROW_ON_ERROR);
	}
}
