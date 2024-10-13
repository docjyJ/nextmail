<?php

namespace OCA\Nextmail\Services;

enum SqlType: string {
	case Mysql = 'mysql';
	case Pgsql = 'pgsql';
}
