<?php

namespace OCA\Stalwart\Services;

enum ServerStatus: int {
	case Success = 0;
	case NoAdmin = 1;
	case Unauthorized = 2;
	case ErrorServer = 3;
	case ErrorNetwork = 4;
	case InvalidConfig = 5;
}
