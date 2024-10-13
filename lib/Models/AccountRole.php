<?php

namespace OCA\Nextmail\Models;

enum AccountRole: string {
	case User = 'user';
	case Admin = 'admin';
	case Group = 'group';
}
