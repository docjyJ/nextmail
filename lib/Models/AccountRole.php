<?php

namespace OCA\Nextmail\Models;

enum AccountRole: string {
	case User = 'user';
	case Admin = 'admin';
	case Group = 'group';

	public function isUser(): bool {
		return $this === self::User || $this === self::Admin;
	}
}
