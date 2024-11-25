<?php

namespace OCA\Nextmail\Models;

use JsonSerializable;
use OCA\Nextmail\ResponseDefinitions;
use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchEmail;
use OCA\Nextmail\SchemaV1\SchServer;
use OCP\IGroup;
use ValueError;

/** @psalm-import-type NextmailGroup from ResponseDefinitions */
readonly class GroupEntity implements JsonSerializable {

	public function __construct(
		public string $id,
		public ?string $server_id,
		public string $name,
		public ?string $primaryEmail,
	) {
	}

	public static function fromIGroup(IGroup $group, ?string $server_id = null, ?string $email = null): self {
		return new self(
			$group->getGID(),
			$server_id,
			$group->getDisplayName(),
			$email
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
		if ($value[SchAccount::ROLE] !== AccountRole::Group->value) {
			throw new ValueError('role must be group');
		}
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
			$email
		);
	}

	/** @return NextmailGroup */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'server_id' => $this->server_id,
			'name' => $this->name,
			'email' => $this->primaryEmail,
		];
	}

	public function update(?string $server_id, ?string $email): self {
		return new self(
			$this->id,
			$server_id,
			$this->name,
			$email
		);
	}
}
