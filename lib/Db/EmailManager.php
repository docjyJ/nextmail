<?php

namespace OCA\Stalwart\Db;

use OCA\Stalwart\Models\AccountEntity;
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
	public function findPrimary(AccountEntity $account): ?EmailEntity {
		$q = $this->db->getQueryBuilder();
		$q->select('*')
			->from(EmailEntity::TABLE)
			->where($q->expr()->eq('cid', $q->createNamedParameter($account->config->cid, IQueryBuilder::PARAM_INT)))
			->andWhere($q->expr()->eq('uid', $q->createNamedParameter($account->uid)))
			->andWhere($q->expr()->eq('type', $q->createNamedParameter(EmailType::Primary->value)));
		$result = $q->executeQuery();
		$row = EmailEntity::fromMixed($account, $result->fetch());
		$result->closeCursor();
		return $row;
	}

	/** @throws Exception */
	public function setPrimary(AccountEntity $account, string $email): EmailEntity {
		$email = new EmailEntity($account, strtolower($email), EmailType::Primary);
		$this->db->beginTransaction();
		try {
			$q = $this->db->getQueryBuilder();
			$q->delete(EmailEntity::TABLE)
				->where($q->expr()->eq('cid', $q->createNamedParameter($email->account->config->cid, IQueryBuilder::PARAM_INT)))
				->andWhere($q->expr()->eq('uid', $q->createNamedParameter($email->account->uid)))
				->andWhere($q->expr()->eq('type', $q->createNamedParameter(EmailType::Primary->value)))
				->executeStatement();
			$q = $this->db->getQueryBuilder();
			$q->insert(EmailEntity::TABLE)
				->values([
					'cid' => $q->createNamedParameter($email->account->config->cid, IQueryBuilder::PARAM_INT),
					'uid' => $q->createNamedParameter($email->account->uid),
					'email' => $q->createNamedParameter($email->email),
					'type' => $q->createNamedParameter($email->type->value),
				])
				->executeStatement();
			$this->db->commit();
			return $email;
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
