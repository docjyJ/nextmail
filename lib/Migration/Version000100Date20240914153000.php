<?php


declare(strict_types=1);

namespace OCA\Stalwart\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass */
class Version000100Date20240914153000 extends SimpleMigrationStep {
	private const TABLE_CONFIGS = 'stalwart_configs';
	private const STALWART_ACCOUNTS = 'stalwart_accounts';
	private const TABLE_EMAILS = 'stalwart_emails';

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/** @throws SchemaException */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		/** @type ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(self::TABLE_CONFIGS)) {
			$tableConfigs = $schema->createTable(self::TABLE_CONFIGS);
			$tableConfigs->addColumn('cid', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$tableConfigs->addColumn('endpoint', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$tableConfigs->addColumn('username', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$tableConfigs->addColumn('password', Types::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$tableConfigs->addColumn('health', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$tableConfigs->setPrimaryKey(['cid']);
		} else {
			$tableConfigs = $schema->getTable(self::TABLE_CONFIGS);
		}

		if (!$schema->hasTable(self::STALWART_ACCOUNTS)) {
			$tableAccounts = $schema->createTable(self::STALWART_ACCOUNTS);
			$tableAccounts->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$tableAccounts->addColumn('cid', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$tableAccounts->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$tableAccounts->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$tableAccounts->addColumn('password', Types::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$tableAccounts->addColumn('quota', Types::INTEGER, [
				'notnull' => true,
			]);
			$tableAccounts->setPrimaryKey(['uid']);
			$tableAccounts->addForeignKeyConstraint(
				$tableConfigs->getName(),
				['cid'],
				['cid'],
				['onDelete' => 'CASCADE']
			);
		} else {
			$tableAccounts = $schema->getTable(self::STALWART_ACCOUNTS);
		}

		if (!$schema->hasTable(self::TABLE_EMAILS)) {
			$table = $schema->createTable(self::TABLE_EMAILS);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->setPrimaryKey(['uid', 'email']);
			$table->addForeignKeyConstraint(
				$tableAccounts->getName(),
				['uid'],
				['uid'],
				['onDelete' => 'CASCADE']);
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
