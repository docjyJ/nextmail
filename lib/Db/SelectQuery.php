<?php

namespace OCA\Stalwart\Db;

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class SelectQuery {
	private array $cond = [];
	private IQueryBuilder $q;

	public function __construct(
		IDBConnection $db,
		string $table,
	) {
		$this->q = $db->getQueryBuilder()->select('*')->from($table);
	}

	public function where(string $name, ?string $value): self {
		if ($value !== null) {
			$this->cond[] = $this->q->expr()->eq($name, $this->q->createNamedParameter($value));
		}
		return $this;
	}

	/** @throws Exception */
	public function fetchAll(): array {
		if (count($this->cond) > 0) {
			$this->q->where(...$this->cond);
		}
		return $this->q->executeQuery()->fetchAll();
	}

}
