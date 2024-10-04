<?php


declare(strict_types=1);

namespace OCA\Stalwart\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000100Date20240914153000 extends SimpleMigrationStep {
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		/** @type ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('stalwart_config')) {
			$table = $schema->createTable('stalwart_config');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('endpoint', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('username', Types::STRING, [
				'notnull' => true,
				'length' => 300,
			]);
			$table->addColumn('password', Types::STRING, [
				'notnull' => true,
				'length' => 300,
			]);
			$table->addColumn('health', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->addColumn('health_expires', Types::DATETIME, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('stalwart_users')) {
			$table = $schema->createTable('stalwart_users');
			$table->addColumn('config_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'length' => 128,
			]);
			$table->addColumn('password', Types::STRING, [
				'length' => 300,
			]);
			$table->addColumn('quota', Types::BIGINT, [
				'length' => 4,
			]);
			$table->setPrimaryKey(['config_id', 'uid']);
			$table->addForeignKeyConstraint('stalwart_config', ['config_id'], ['id'], ['onDelete' => 'CASCADE']);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
