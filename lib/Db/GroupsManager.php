<?php

namespace OCA\Nextmail\Db;

use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\GroupEntity;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchServer;
use OCP\DB\Exception;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;

readonly class GroupsManager {
	public function __construct(
		private IGroupManager $gm,
		private Transaction $tr,
	) {
	}

	/** @throws Exception */
	private function loadEntity(IGroup $group): ?GroupEntity {
		$data = $this->tr->selectAccount('#' . $group->getGID(), [AccountRole::Group]);
		$data_email = $this->tr->selectEmail('#' . $group->getGID(), EmailType::Primary);
		$i = array_key_first($data);
		$i_email = array_key_first($data_email);
		return $i !== null && is_array($data[$i]) ? GroupEntity::fromIGroup(
			$group,
			is_string($data[$i][SchServer::ID]) ? $data[$i][SchServer::ID] : null,
			$i_email !== null && is_array($data_email[$i_email]) && is_string($data_email[$i_email][SchEmail::EMAIL])
				? $data_email[$i_email][SchEmail::EMAIL]
				: null
		) : null;
	}

	/**
	 * @return list<GroupEntity>
	 * @throws Exception
	 */
	public function listAll(): array {
		return array_map(fn ($x) => $this->loadEntity($x) ?? GroupEntity::fromIGroup($x), array_values($this->gm->search('')));
	}

	/** @throws Exception */
	private function processQuery(GroupEntity $data, bool $create): GroupEntity {
		if ($create) {
			$this->tr->insertAccount(
				'#' . $data->id,
				$data->name,
				AccountRole::Group,
				$data->server_id,
				null,
				null
			);
		} else {
			$this->tr->updateAccount(
				'#' . $data->id,
				$data->name,
				AccountRole::Group,
				$data->server_id,
				null,
				null
			);
		}
		$this->tr->deleteEmail('#' . $data->id, EmailType::Primary);
		if ($data->primaryEmail !== null) {
			$this->tr->insertEmail('#' . $data->id, $data->primaryEmail, EmailType::Primary);
		}
		return $data;
	}

	/** @throws Exception */
	public function addMember(IGroup $group, IUser $user): void {
		$this->tr->insertMember('#' . $group->getGID(), $user->getUID());
	}

	/** @throws Exception */
	public function removeMember(IGroup $group, IUser $user): void {
		$this->tr->deleteMember('#' . $group->getGID(), $user->getUID());
	}

	/** @throws Exception */
	private function syncMembers(IGroup $group): void {
		$this->tr->deleteMember('#' . $group->getGID(), null);
		foreach ($group->getUsers() as $user) {
			$this->addMember($group, $user);
		}
	}

	/** @throws Exception */
	public function syncOne(IGroup $group): void {
		$data = $this->loadEntity($group);
		if ($data !== null) {
			$this->processQuery($data, false);
		} else {
			$this->processQuery(GroupEntity::fromIGroup($group), true);
		}
		$this->syncMembers($group);
	}

	/** @throws Exception */
	public function delete(IGroup $group): void {
		$this->tr->deleteAccount('#' . $group->getGID());
	}

	/** @throws Exception */
	public function update(string $gid, ?string $srv, ?string $email): ?GroupEntity {
		$group = $this->gm->get($gid);
		if ($group !== null) {
			$data = $this->loadEntity($group);
			$ret = $data === null
				? $this->processQuery(GroupEntity::fromIGroup($group, $srv, $email), true)
				: $this->processQuery($data->update($srv, $email), false);
			$this->syncMembers($group);
			return $ret;
		} else {
			return null;
		}
	}

	/** @throws Exception */
	public function commit(): void {
		$this->tr->commit();
	}

}
