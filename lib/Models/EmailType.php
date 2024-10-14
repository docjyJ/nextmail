<?php

namespace OCA\Nextmail\Models;

enum EmailType: string {
	case Primary = 'primary';
	case Alias = 'alias';
	case List = 'list';
}
