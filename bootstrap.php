<?php
/**
 * Bootstrap for current project structure.
 */
use \Moro\Platform\Application;
use \Moro\Platform\Command\UploadedCommand;
use \Moro\Platform\Command\ImagesCommand;
use \Moro\Platform\Command\MessagesCommand;
use \Symfony\Component\Routing\RequestContext;

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
		$application->extend('request_context', function(RequestContext $context) use ($application) {
			$context->setHost($application->getOption('host'));
			$context->setBaseUrl('/admin/index.php');

			return $context;
		});

		$console = $application->offsetGet('console');
		$console->add(new UploadedCommand($application));
		$console->add(new ImagesCommand($application));
		$console->add(new MessagesCommand($application));

		return $console;
	});
}

return Application::getInstance();
