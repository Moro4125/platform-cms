<?php
/**
 * HybridAuth enter point.
 *
 * How to use this file in the application:
 *    require_once __DIR__.'/../../../bootstrap.php'; // Connect this file only once.
 *    require __DIR__.'/../../../vendor/moro/platform-cms/http/action/auth/hybrid.php';
 */
use \Moro\Platform\Application;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::getInstance(function() {
	$endPoint = new Hybrid_Endpoint();
	$endPoint->process();
});