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
		private readonly string $table,
	) {
		$this->q = $db->getQueryBuilder()->select('*')->from($table, $table);
	}

	public function leftJoin(string $table, ?string $on): self {
		if ($on !== null) {
			$this->q->leftJoin($this->table, $table, $table, $this->q->expr()->eq($this->table . '.' . $on, $table . '.' . $on));
		}
		return $this;
	}

	/** @param string[]|string|null $values */
	public function where(string $name, array|string|null $values): self {
		if (is_array($values)) {
			if (count($values) > 1) {
				$this->cond[] = $this->q->expr()->orX(...array_map(fn ($value) => $this->q->expr()->eq($name, $this->q->createNamedParameter($value)), $values));
			} elseif (count($values) > 0) {
				$this->cond[] = $this->q->expr()->eq($name, $this->q->createNamedParameter($values[0]));
			}
		} elseif ($values !== null) {
			$this->cond[] = $this->q->expr()->eq($name, $this->q->createNamedParameter($values));
		}
		return $this;
	}

	/** @param string[] $values */
	public function notWhere(string $name, array $values): self {
		if (count($values) > 1) {
			$this->cond[] = $this->q->expr()->andX(...array_map(fn ($value) => $this->q->expr()->neq($name, $this->q->createNamedParameter($value)), $values));
		} elseif (count($values) > 0) {
			$this->cond[] = $this->q->expr()->neq($name, $this->q->createNamedParameter($values[0]));
		}
		return $this;
	}

	/**
	 * @throws Exception
	 * @return list<mixed>
	 */
	public function fetchAll(): array {
		if (count($this->cond) > 0) {
			$this->q->where(...$this->cond);
		}
		return array_values($this->q->executeQuery()->fetchAll());
	}

}
