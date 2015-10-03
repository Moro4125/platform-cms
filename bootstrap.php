<?php
/**
 * Bootstrap for current project structure.
 */
use Moro\Platform\Application;

if (file_exists(__DIR__.'/vendor/autoload.php'))
{
	error_reporting(E_ALL);
	require_once __DIR__.'/vendor/autoload.php';
}

require __DIR__.'/sources/registers.php';
require __DIR__.'/sources/middlewares.php';
require __DIR__.'/sources/controllers.php';

return php_sapi_name() === 'cli'
	? Application::getInstance()->offsetGet('console')
	: Application::getInstance();
