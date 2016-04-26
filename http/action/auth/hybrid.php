<?php
/**
 * HybridAuth enter point.
 */
use \Moro\Platform\Application;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::getInstance(function() {
	$endPoint = new Hybrid_Endpoint();
	$endPoint->process();
});