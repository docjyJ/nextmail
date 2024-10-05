<?php

namespace OCA\Stalwart\Db;

use OCA\Stalwart\Models\EmailEntity;
use OCA\Stalwart\Models\EmailType;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class EmailManager {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	/** @throws Exception */
	public function create(EmailEntity $entity): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->insert(EmailEntity::TABLE)
				->values([
					'cid' => $q->createNamedParameter($entity->cid, IQueryBuilder::PARAM_INT),
					'uid' => $q->createNamedParameter($entity->uid),
					'email' => $q->createNamedParameter($entity->email),
					'type' => $q->createNamedParameter($entity->type->value),
				])
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/** @throws Exception */
	public function updatePrimary(int $cid, string $uid, string $email): void {
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(EmailEntity::TABLE)
				->where($q->expr()->eq('cid', $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT)))
				->andWhere($q->expr()->eq('uid', $q->createNamedParameter($uid)))
				->andWhere($q->expr()->eq('type', $q->createNamedParameter(EmailType::Primary->value)))
				->executeStatement();
			$q = $this->db->getQueryBuilder();
			$q->insert(EmailEntity::TABLE)
				->values([
					'cid' => $q->createNamedParameter($cid, IQueryBuilder::PARAM_INT),
					'uid' => $q->createNamedParameter($uid),
					'email' => $q->createNamedParameter($email),
					'type' => $q->createNamedParameter(EmailType::Primary->value),
				])
				->executeStatement();
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
