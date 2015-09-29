<?php
/**
 * Bootstrap for current project structure.
 */
use Moro\Platform\Application;

error_reporting(E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED);
require_once __DIR__.'/vendor/autoload.php';

require_once __DIR__.'/sources/application.php';
require_once __DIR__.'/sources/registers.php';
require_once __DIR__.'/sources/middlewares.php';
require_once __DIR__.'/sources/controllers.php';

return php_sapi_name() === 'cli'
	? Application::getInstance()->offsetGet('console')
	: Application::getInstance();
