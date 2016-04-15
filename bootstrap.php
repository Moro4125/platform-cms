<?php
/**
 * Bootstrap for current project structure.
 */
use \Moro\Platform\Application;
use \Moro\Platform\Command\UploadedCommand;
use \Moro\Platform\Command\ImagesCommand;

date_default_timezone_set('UTC');

if (file_exists(__DIR__.'/vendor/autoload.php'))
{
	error_reporting(E_ALL);
	require_once __DIR__.'/vendor/autoload.php';
}

require __DIR__.'/sources/registers.php';
require __DIR__.'/sources/middlewares.php';
require __DIR__.'/sources/controllers.php';

if (php_sapi_name() === 'cli')
{
	return Application::getInstance(function(Application $application) {
		$console = $application->offsetGet('console');
		$console->add(new UploadedCommand($application));
		$console->add(new ImagesCommand($application));

		return $console;
	});
}

return Application::getInstance();
