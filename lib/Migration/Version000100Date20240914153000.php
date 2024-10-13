<?php

declare(strict_types=1);

namespace OCA\Nextmail\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCA\Nextmail\Schema\Columns;
use OCA\Nextmail\Schema\Tables;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass */
class Version000100Date20240914153000 extends SimpleMigrationStep {
	private const SCHEMA = [
		Tables::SERVERS => [
			'columns' => [
				Columns::SERVER_ID => ['type' => Types::STRING, 'length' => 32],
				Columns::SERVER_ENDPOINT => ['type' => Types::STRING, 'length' => 128],
				Columns::SERVER_USERNAME => ['type' => Types::STRING, 'length' => 128],
				Columns::SERVER_PASSWORD => ['type' => Types::STRING, 'length' => 256],
				Columns::SERVER_HEALTH => ['type' => Types::STRING, 'length' => 32],
			],
			'primary' => [Columns::SERVER_ID],
			'foreign' => [],
		],
		Tables::ACCOUNTS => [
			'columns' => [
				Columns::ACCOUNT_ID => ['type' => Types::STRING, 'length' => 64],
				Columns::SERVER_ID => ['type' => Types::STRING, 'length' => 32],
				Columns::ACCOUNT_NAME => ['type' => Types::STRING, 'length' => 32],
				Columns::ACCOUNT_ROLE => ['type' => Types::STRING, 'length' => 16],
				Columns::ACCOUNT_HASH => ['type' => Types::STRING, 'length' => 256, 'nullable' => true],
				Columns::ACCOUNT_QUOTA => ['type' => Types::INTEGER, 'nullable' => true],
			],
			'primary' => [Columns::ACCOUNT_ID],
			'foreign' => [
				Tables::SERVERS => [Columns::SERVER_ID],
			],
		],
		Tables::MEMBERS => [
			'columns' => [
				Columns::USER_ID => ['type' => Types::STRING, 'length' => 64],
				Columns::GROUP_ID => ['type' => Types::STRING, 'length' => 64],
			],
			'primary' => [Columns::USER_ID, Columns::GROUP_ID],
			//			'foreign' => [
			//				Tables::ACCOUNTS => [Columns::USER_ID],
			//				Tables::ACCOUNTS => [Columns::GROUP_ID],
			//			],
		],
		Tables::EMAILS => [
			'columns' => [
				Columns::ACCOUNT_ID => ['type' => Types::STRING, 'length' => 64],
				Columns::EMAIL_ID => ['type' => Types::STRING, 'length' => 128],
				Columns::EMAIL_TYPE => ['type' => Types::STRING, 'length' => 32],
			],
			'primary' => [Columns::ACCOUNT_ID, Columns::EMAIL_ID],
			'foreign' => [
				Tables::ACCOUNTS => [Columns::ACCOUNT_ID],
			],
		],
	];

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
		/** @var array<string, array{columns: array<string, array{type: string, length?: int, nullable?: bool}>, primary: array<int, string>, foreign: array<string, array<int, string>>}> $schemaData */
		$schemaData = self::SCHEMA;
		foreach ($schemaData as $tableName => $data) {
			if (!$schema->hasTable($tableName)) {
				$table = $schema->createTable($tableName);
				foreach ($data['columns'] as $colName => $options) {
					$col = $table->addColumn($colName, $options['type']);
					$col->setNotnull(!($options['nullable'] ?? false));
					if (isset($options['length'])) {
						$col->setLength($options['length']);
					}
				}
				$table->setPrimaryKey($data['primary']);
				foreach ($data['foreign'] as $name => $foreign) {
					$foreignTable = $schema->getTable($name);
					$key = $foreignTable->getPrimaryKey()?->getColumns();
					assert($key !== null);
					$name = $foreignTable->getName();
					$table->addForeignKeyConstraint($name, $foreign, $key, ['onDelete' => 'CASCADE']);
				}
			}
		}
		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
