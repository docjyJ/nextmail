<?php

namespace OCA\Stalwart\Models;

enum AccountType: string {
	case Individual = 'individual';
	case Group = 'group';
}