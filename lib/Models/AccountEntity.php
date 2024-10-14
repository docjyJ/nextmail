<?php

namespace OCA\Nextmail\Models;

use OCA\Nextmail\SchemaV1\SchAccount;
use OCA\Nextmail\SchemaV1\SchServer;
use ValueError;

readonly class AccountEntity {
	public function __construct(
		public string       $id,
		public ServerEntity $server,
		public string       $name,
		public ?string      $hash,
		public AccountRole  $role,
		public ?int         $quota,
	) {
	}

	public static function parse(ServerEntity $conf, mixed $value): self {
		if (!is_array($value)) {
			throw new ValueError('value must be an array');
		}
		if (!is_string($value[SchAccount::ID])) {
			throw new ValueError('uid must be a string');
		}
		if ($conf->id !== $value[SchServer::ID]) {
			throw new ValueError('server id mismatch');
		}
		if (!is_string($value[SchAccount::NAME])) {
			throw new ValueError('display_name must be a string');
		}
		if (!is_string($value[SchAccount::ROLE])) {
			throw new ValueError('type must be a string');
		}
		return new self(
			$value[SchAccount::ID],
			$conf,
			$value[SchAccount::NAME],
			is_string($value[SchAccount::HASH]) ? $value[SchAccount::HASH] : null,
			AccountRole::from($value[SchAccount::ROLE]),
			is_int($value[SchAccount::QUOTA]) ? $value[SchAccount::QUOTA] : null,
		);
	}
}
