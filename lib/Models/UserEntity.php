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
		public ?string       $server_id,
		public string       $name,
		public ?string      $hash,
		public bool         $admin,
		public ?int         $quota,
		public ?string      $primaryEmail,
	) {
	}

	public static function fromIUser(IUser $user, ?string $server_id = null, bool $admin = false, ?int $quota = null): self {
		$email = $user->getEMailAddress();
		return new self(
			$user->getUID(),
			$server_id,
			$user->getDisplayName(),
			self::parsePasswordHash($user->getPasswordHash()),
			$admin,
			$quota,
			$email !== null ? strtolower($email) : null
		);
	}

	public static function parse(mixed $value): self {
		if (!is_array($value)) {
			throw new ValueError('value must be an array');
		}
		if (!is_string($value[SchAccount::ID])) {
			throw new ValueError('id must be a string');
		}
		if (!is_string($value[SchAccount::NAME])) {
			throw new ValueError('name must be a string');
		}
		if (!is_string($value[SchAccount::ROLE])) {
			throw new ValueError('hash must be a string');
		}
		$admin = match (AccountRole::from($value[SchAccount::ROLE])) {
			AccountRole::Admin => true,
			AccountRole::User => false,
			default => throw throw new ValueError('role must be user'),
		};
		if (is_string($value[SchEmail::TYPE])) {
			$type = EmailType::from($value[SchEmail::TYPE]);
			if ($type !== EmailType::Primary) {
				throw new ValueError('type must be primary');
			}
			if (!is_string($value[SchEmail::EMAIL])) {
				throw new ValueError('email must be a string');
			}
			$email = strtolower($value[SchEmail::EMAIL]);
		} else {
			$email = null;
		}
		return new self(
			$value[SchAccount::ID],
			is_string($value[SchServer::ID]) ? $value[SchServer::ID] : null,
			$value[SchAccount::NAME],
			is_string($value[SchAccount::HASH]) ? $value[SchAccount::HASH] : null,
			$admin,
			is_int($value[SchAccount::QUOTA]) ? $value[SchAccount::QUOTA] : null,
			$email
		);
	}

	/** @return NextmailUser */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'server_id' => $this->server_id,
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
			$this->server_id,
			$this->name,
			$this->hash,
			$admin,
			$quota,
			$this->primaryEmail
		);
	}


	public static function parsePasswordHash(?string $passwordHash): string {
		return preg_replace('/^[^$]*/', '', $passwordHash ?? '') ?? '';
	}
}
