<?php

declare(strict_types=1);

namespace OCA\Nextmail\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchMember;
use OCA\Nextmail\SchemaV1\SchServer;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass */
class Version000100Date20240914153000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IUserManager $um,
		private readonly IDBConnection $db,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		$schema = $schemaClosure();
		/** @var list<array{table: string, columns: list<array{name: string, type: string, length?: int, nullable?: bool}>, primary: list<string>, foreign: list<array{table: string, columns: list<string>, onDelete: string}>}> $schemaData */
		$schemaData = [SchServer::DB, SchAccount::DB, SchEmail::DB, SchMember::DB];
		foreach ($schemaData as $data) {
			if (!$schema->hasTable($data['table'])) {
				$table = $schema->createTable($data['table']);
				foreach ($data['columns'] as $colData) {
					$col = $table->addColumn($colData['name'], $colData['type']);
					$col->setNotnull(!($colData['nullable'] ?? false));
					if (isset($colData['length'])) {
						$col->setLength($colData['length']);
					}
				}
				$table->setPrimaryKey($data['primary']);
				foreach ($data['foreign'] as $foreignData) {
					$foreign = $schema->getTable($foreignData['table']);
					$key = $foreign->getPrimaryKey()?->getColumns();
					assert($key !== null);
					$name = $foreign->getName();
					$table->addForeignKeyConstraint($name, $foreignData['columns'], $key, ['onDelete' => $foreignData['onDelete']]);
				}
			}
		}
		return $schema;
	}

	/** @throws Exception */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$this->db->beginTransaction();
		try {
			$query = $this->db->getQueryBuilder()
				->select(SchAccount::ID)
				->from(SchAccount::TABLE)
				->where($this->db->getQueryBuilder()->expr()->in(SchAccount::ROLE, [
					$this->db->getQueryBuilder()->createNamedParameter(AccountRole::User->value),
					$this->db->getQueryBuilder()->createNamedParameter(AccountRole::Admin->value),
				]))
				->executeQuery()->fetchAll();
			$ids = [];
			foreach (array_keys($query) as $key) {
				if (is_array($query[$key])) {
					if (is_string($query[$key][SchAccount::ID])) {
						$ids[] = $query[$key][SchAccount::ID];
					}
				}
			}

			foreach ($this->um->search('') as $i_user) {
				$uid = $i_user->getUID();
				$email = $i_user->getEMailAddress();
				$hash = preg_replace('/^[^$]*/', '', $i_user->getPasswordHash() ?? '') ?? '';
				$name = $i_user->getDisplayName();

				$q = $this->db->getQueryBuilder();
				$hash_param = $hash !== '' ? $q->createNamedParameter($hash) : $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL);
				if (in_array($uid, $ids)) {
					$ids = array_filter($ids, fn ($x) => $x !== $uid);
					$q->update(SchAccount::TABLE)
						->set(SchAccount::NAME, $q->createNamedParameter($name))
						->set(SchAccount::HASH, $hash_param)
						->where($q->expr()->eq(SchAccount::ID, $q->createNamedParameter($uid)))
						->executeStatement();
				} else {
					$q->insert(SchAccount::TABLE)
						->values([
							SchAccount::ID => $q->createNamedParameter($uid),
							SchAccount::NAME => $q->createNamedParameter($name),
							SchAccount::ROLE => $q->createNamedParameter(AccountRole::User->value),
							SchAccount::HASH => $hash_param,
							SchAccount::QUOTA => $q->createNamedParameter(null, IQueryBuilder::PARAM_NULL),
						])->executeStatement();
				}


				$q = $this->db->getQueryBuilder();
				$q->delete(SchEmail::TABLE)
					->where($q->expr()->eq(SchEmail::TYPE, $q->createNamedParameter(EmailType::Primary->value)))
					->andWhere($q->expr()->eq(SchAccount::ID, $q->createNamedParameter($uid)))
					->executeStatement();
				if ($email !== null) {
					$q->insert(SchEmail::TABLE)
						->values([
							SchAccount::ID => $q->createNamedParameter($uid),
							SchEmail::EMAIL => $q->createNamedParameter($email),
							SchEmail::TYPE => $q->createNamedParameter(EmailType::Primary->value),
						])->executeStatement();
				}
			}
			foreach ($ids as $id) {
				$q = $this->db->getQueryBuilder();
				$q->delete(SchAccount::TABLE)
					->where($q->expr()->eq(SchAccount::ID, $q->createNamedParameter($id)))
					->executeStatement();
			}
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
