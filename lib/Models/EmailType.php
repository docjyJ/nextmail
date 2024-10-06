<?php

namespace OCA\Stalwart\Models;

enum EmailType: string {
	case Primary = 'primary';
	case Alias = 'alias';
	case List = 'list';
}
