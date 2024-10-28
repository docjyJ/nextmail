<?php

declare(strict_types=1);

namespace OCA\Nextmail\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCA\Nextmail\Db\UsersManager;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchMember;
use OCA\Nextmail\SchemaV1\SchServer;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass */
class Version000100Date20240914153000 extends SimpleMigrationStep {
	public function __construct(
		private readonly UsersManager $um,
	) {
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
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
		$this->um->updateAllIUser();
		$this->um->commit();
	}
}
