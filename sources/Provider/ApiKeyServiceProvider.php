<?php
/**
 * Class ApiKeyServiceProvider
 */
namespace Moro\Platform\Provider;
use \Silex\Application;
use \Silex\ServiceProviderInterface;
use \Moro\Platform\Security\Http\Firewall\ApiKeyAuthenticationListener;
use \Moro\Platform\Security\Provider\ApiKeyAuthenticationProvider;

/**
 * Class ApiKeyServiceProvider
 *
 * API key security Service Provider.
 * This service provider adds support for authenticating a user through an API key
 *
 * @package Moro\Platform\Provider
 */
class ApiKeyServiceProvider implements ServiceProviderInterface
{
	/**
	 * @param Application $app
	 */
	public function register(Application $app)
	{
		$app['security.authentication_listener.factory.api_key'] = $app->protect(function ($name, $options) use ($app)
		{
			unset($options); // not in use
			$app['security.authentication_provider.' . $name . '.api_key'] = $app->share(function () use ($app)
			{
				return new ApiKeyAuthenticationProvider($app['api_key.user_provider'], $app['api_key.encoder']);
			});

			$app['security.authentication_listener.' . $name . '.api_key'] = $app->share(function () use ($app)
			{
				return new ApiKeyAuthenticationListener($app['security'], $app['security.authentication_manager']);
			});

			return array(
				'security.authentication_provider.' . $name . '.api_key',
				'security.authentication_listener.' . $name . '.api_key',
				null, // the entry point id
				'pre_auth' // // the position of the listener in the stack
			);
		});
	}

	/**
	 * @param \Silex\Application $app
	 */
	public function boot(Application $app)
	{
	}
}
