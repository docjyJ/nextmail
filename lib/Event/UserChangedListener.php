<?php

namespace OCA\Nextmail\Event;

use OCA\Nextmail\Db\Transaction;
use OCA\Nextmail\Models\AccountRole;
use OCA\Nextmail\Models\EmailType;
use OCA\Nextmail\Models\UserEntity;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * @implements IEventListener<UserChangedEvent>
 */
readonly class UserChangedListener implements IEventListener {
	public function __construct(
		private Transaction $tr,
	) {
	}

	/**
	 * @param UserChangedEvent $event
	 * @throws Exception
	 */
	public function handle(Event $event): void {
		$i_user = $event->getUser();
		$users = $this->tr->selectAccount($i_user->getUID(), [AccountRole::User, AccountRole::Admin]);
		$array = count($users) === 1 && is_array($users[0]) ? $users[0] : [];
		$user = UserEntity::fromIUser(
			is_string($array[SchAccount::ID]) ? $array[SchAccount::ID] : '',
			$i_user,
			$array[SchAccount::ROLE] === AccountRole::Admin,
			is_int($array[SchAccount::QUOTA]) ? $array[SchAccount::QUOTA] : null
		);
		if (count($users) === 0) {
			$this->tr->insertAccount(
				$user->id,
				$user->server_id,
				$user->name,
				$user->hash,
				$user->getRoleEnum(),
				$user->quota
			);
		} else {
			$this->tr->updateAccount(
				$user->id,
				$user->name,
				$user->getRoleEnum(),
				$user->hash,
				$user->quota
			);
		}
		$this->tr->deleteEmail($user->id, EmailType::Primary);
		if ($user->primaryEmail !== null) {
			$this->tr->insertEmail($user->id, $user->primaryEmail, EmailType::Primary);
		}
		$this->tr->commit();
	}
}
