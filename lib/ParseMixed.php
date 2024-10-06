<?php

namespace OCA\Stalwart;

use DateTime;
use Exception;
use OCA\Stalwart\Models\AccountEntity;
use OCA\Stalwart\Models\AccountType;
use OCA\Stalwart\Models\ConfigEntity;
use OCA\Stalwart\Models\EmailEntity;
use OCA\Stalwart\Models\EmailType;
use OCA\Stalwart\Models\ServerStatus;

class ParseMixed {

	public static function int(mixed $value): ?int {
		return is_int($value) ? $value : null;
	}

	public static function string(mixed $value): ?string {
		return is_string($value) ? $value : null;
	}

	public static function dateTime(mixed $value): ?DateTime {
		try {
			return is_string($value) ? new DateTime($value) : null;
		} catch (Exception) {
			return null;
		}
	}

	public static function serverStatus(mixed $value): ?ServerStatus {
		return is_string($value) ? match ($value) {
			'success' => ServerStatus::Success,
			'unprivileged' => ServerStatus::NoAdmin,
			'unauthorized' => ServerStatus::Unauthorized,
			'bad_server' => ServerStatus::BadServer,
			'bad_network' => ServerStatus::BadNetwork,
			'invalid' => ServerStatus::Invalid,
			default => null
		} : null;
	}

	public static function accountType(mixed $value): ?AccountType {
		return is_string($value) ? match ($value) {
			'individual' => AccountType::Individual,
			'group' => AccountType::Group,
			default => null
		} : null;
	}

	private static function emailType(mixed $value): ?EmailType {
		return is_string($value) ? match ($value) {
			'primary' => EmailType::Primary,
			'alias' => EmailType::Alias,
			'list' => EmailType::List,
			default => null
		} : null;
	}

	public static function configEntity(mixed $value): ?ConfigEntity {
		if (is_array($value) && is_int($value['cid'])) {
			$entity = new ConfigEntity($value['cid']);
			if (is_string($value['endpoint'])) {
				$entity->endpoint = $value['endpoint'];
			}
			if (is_string($value['username'])) {
				$entity->username = $value['username'];
			}
			if (is_string($value['password'])) {
				$entity->password = $value['password'];
			}
			if ($health = self::serverStatus($value['health'])) {
				$entity->health = $health;
			}
			if ($expires = self::dateTime($value['expires'])) {
				$entity->expires = $expires;
			}
			return $entity;
		}
		return null;
	}

	public static function accuntEntity(ConfigEntity $conf, mixed $value): ?AccountEntity {
		if (is_array($value) && $value['cid'] === $conf->cid && is_string($value['uid'])) {
			$entity = new AccountEntity($conf, $value['uid']);
			if ($type = self::accountType($value['type'])) {
				$entity->type = $type;
			}
			if (is_string($value['display_name'])) {
				$entity->displayName = $value['display_name'];
			}
			if (is_string($value['password'])) {
				$entity->password = $value['password'];
			}
			if (is_int($value['quota'])) {
				$entity->quota = $value['quota'];
			}
			return $entity;
		}
		return null;
	}

	public static function emailEntity(AccountEntity $account, mixed $value): ?EmailEntity {
		if (is_array($value)
			&& $value['cid'] === $account->config->cid
			&& $value['uid'] === $account->uid
			&& is_string($value['email'])
			&& $type = self::emailType($value['type'])) {
			return new EmailEntity($account, $value['email'], $type);
		}
		return null;
	}
}
