<?php
/**
 * Class MonologServiceProvider
 */
namespace Moro\Platform\Provider;
use \Silex\Application;
use \Silex\Provider\MonologServiceProvider as CMonologServiceProvider;
use \Monolog\Logger;

/**
 * Class MonologServiceProvider
 * @package Provider
 */
class MonologServiceProvider extends CMonologServiceProvider
{
	/**
	 * Registers services on the given app.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param Application $app
	 */
	public function register(Application $app)
	{
		CMonologServiceProvider::register($app);

		$app->extend('monolog', function(Logger $logger, Application $app) {
			if (isset($app['monolog.processors']))
			{
				foreach ($app['monolog.processors'] as $processor)
				{
					$logger->pushProcessor($processor);
				}
			}

			return $logger;
		});
	}
}