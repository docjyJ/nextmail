<?php

namespace OCA\Stalwart\Models;

enum ServerStatus: string {
	case Success = 'success';
	case NoAdmin = 'unprivileged';
	case Unauthorized = 'unauthorized';
	case BadServer = 'bad_server';
	case BadNetwork = 'bad_network';
	case Invalid = 'invalid';
}
