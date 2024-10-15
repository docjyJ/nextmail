<?php

namespace OCA\Nextmail\Models;

use JsonSerializable;
use OCA\Nextmail\ResponseDefinitions;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchServer;
use OCP\IUser;
use ValueError;

/** @psalm-import-type NextmailUser from ResponseDefinitions */
readonly class UserEntity implements JsonSerializable {

	public function __construct(
		public string       $id,
		public ServerEntity $server,
		public string       $name,
		public ?string      $hash,
		public bool         $admin,
		public ?int         $quota,
		public ?string      $primaryEmail,
	) {
	}

	public static function fromIUser(ServerEntity $server, IUser $user, bool $admin = false, ?int $quota = null): self {
		$email = $user->getEMailAddress();
		return new self(
			$user->getUID(),
			$server,
			$user->getDisplayName(),
			self::getHashFromUser($user),
			$admin,
			$quota,
			$email !== null ? strtolower($email) : null
		);
	}

	public static function parse(ServerEntity $server, array $values): self {
		if ($values[SchServer::ID] !== $server->id) {
			throw new ValueError('server mismatch');
		}
		if (!is_string($values[SchAccount::ID])) {
			throw new ValueError('id must be a string');
		}
		if (!is_string($values[SchAccount::NAME])) {
			throw new ValueError('name must be a string');
		}
		if (!is_string($values[SchAccount::ROLE])) {
			throw new ValueError('hash must be a string');
		}
		$role = AccountRole::from($values[SchAccount::ROLE]);
		if (!$role->isUser()) {
			throw new ValueError('role must be user');
		}
		if (is_string($values[SchEmail::TYPE])) {
			$type = EmailType::from($values[SchEmail::TYPE]);
			if ($type !== EmailType::Primary) {
				throw new ValueError('type must be primary');
			}
			if (!is_string($values[SchEmail::EMAIL])) {
				throw new ValueError('email must be a string');
			}
			$email = strtolower($values[SchEmail::EMAIL]);
		} else {
			$email = null;
		}
		return new self(
			$values[SchAccount::ID],
			$server,
			$values[SchAccount::NAME],
			is_string($values[SchAccount::HASH]) ? $values[SchAccount::HASH] : null,
			$role == AccountRole::Admin,
			is_int($values[SchAccount::QUOTA]) ? $values[SchAccount::QUOTA] : null,
			$email
		);
	}

	/** @return NextmailUser */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'email' => $this->primaryEmail,
			'admin' => $this->admin,
			'quota' => $this->quota,
		];
	}

	public function getRoleEnum(): AccountRole {
		return $this->admin ? AccountRole::Admin : AccountRole::User;
	}

	public function updateAdminQuota(bool $admin, ?int $quota): self {
		return new self(
			$this->id,
			$this->server,
			$this->name,
			$this->hash,
			$admin,
			$quota,
			$this->primaryEmail
		);
	}


	public static function getHashFromUser(IUser $user): string {
		return preg_replace('/^[^$]*/', '', $user->getPasswordHash() ?? '') ?? '';
	}
}
