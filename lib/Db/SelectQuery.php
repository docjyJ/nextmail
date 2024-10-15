<?php

namespace OCA\Nextmail\Db;

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class SelectQuery {
	private array $cond = [];
	private readonly IQueryBuilder $q;

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

	/** @param string[] $values */
	public function whereSome(string $name, array $values): self {
		if (count($values) === 0) {
			return $this;
		} elseif (count($values) === 1) {
			$this->cond[] = $this->q->expr()->eq($name, $this->q->createNamedParameter($values[0]));
		} else {
			$this->cond[] = $this->q->expr()->orX(...array_map(fn ($value) => $this->q->expr()->eq($name, $this->q->createNamedParameter($value)), $values));
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
