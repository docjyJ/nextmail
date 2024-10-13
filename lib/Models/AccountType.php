<?php

namespace OCA\Nextmail\Models;

enum AccountType: string {
	case Individual = 'individual';
	case Group = 'group';
}
