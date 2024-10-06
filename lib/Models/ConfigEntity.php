<?php

namespace OCA\Stalwart\Models;

use DateTime;
use OCA\Stalwart\ResponseDefinitions;

/** @psalm-import-type StalwartServerConfig from ResponseDefinitions */
class ConfigEntity {
	public const TABLE = 'stalwart_configs';

	public function __construct(
		public int          $cid,
		public string       $endpoint = '',
		public string       $username = '',
		public string       $password = '',
		public ServerStatus $health = ServerStatus::Invalid,
		public DateTime     $expires = new DateTime(),
	) {
	}
}
