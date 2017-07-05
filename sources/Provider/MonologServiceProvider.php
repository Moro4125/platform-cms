<?php
/**
 * Class MonologServiceProvider
 */
namespace Moro\Platform\Provider;
use \Pimple\Container;
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
	 * @param Container $app
	 */
	public function register(Container $app)
	{
		CMonologServiceProvider::register($app);

		$app->extend('monolog', function(Logger $logger, Container $app) {
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