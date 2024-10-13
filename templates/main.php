<?php

declare(strict_types=1);

use OCA\Nextmail\AppInfo\Application;
use OCP\Util;

Util::addScript(Application::APP_ID, Application::APP_ID . '-main');

echo '<div id="main"></div>';
