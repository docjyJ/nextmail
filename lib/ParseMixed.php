<?php

namespace OCA\Stalwart;

use DateTimeImmutable;
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

	public static function dateTime(mixed $value): ?DateTimeImmutable {
		try {
			return is_string($value) ? new DateTimeImmutable($value) : null;
		} catch (Exception) {
			return null;
		}
	}

	public static function serverStatus(mixed $value): ?ServerStatus {
		return is_string($value) ? match ($value) {
			'success' => ServerStatus::Success,
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
			return new ConfigEntity(
				$value['cid'],
				self::string($value['endpoint']) ?? '',
				self::string($value['username']) ?? '',
				self::string($value['password']) ?? '',
				self::serverStatus($value['health']) ?? ServerStatus::Invalid,
				self::dateTime($value['expires']) ?? new DateTimeImmutable(),
			);
		}
		return null;
	}

	public static function accountEntity(ConfigEntity $conf, mixed $value): ?AccountEntity {
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
