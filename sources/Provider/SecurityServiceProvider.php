<?php
/**
 * Class SecurityProvider
 */
namespace Moro\Platform\Provider;
use \Silex\Provider\SecurityServiceProvider as CProvider;
use \Silex\Application as CApplication;
use \Symfony\Component\Security\Core\Encoder\EncoderFactory;
use \Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;
use \Symfony\Component\Security\Http\Firewall\LogoutListener;
use \Moro\Platform\Security\Voter\EntityEraseVoter;
use \Moro\Platform\Security\User\PlatformUserProvider;
use \Moro\Platform\Security\Authentication\UserAuthenticationSuccessHandler;
use \Moro\Platform\Security\Authentication\UserAuthenticationFailureHandler;
use \Moro\Platform\Security\Authentication\UserLogoutHandler;

/**
 * Class SecurityProvider
 * @package Provider
 */
class SecurityServiceProvider extends CProvider
{
	/**
	 * Registers services on the given app.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param \Moro\Platform\Application|CApplication $app
	 */
	public function register(CApplication $app)
	{
		parent::register($app);

		$app['security.user_provider.admin'] = $app->share(function() use ($app) {
			return new PlatformUserProvider($app->getServiceUsers(), $app->getServiceUsersAuth());
		});

		$app['security.user_provider.public'] = $app->share(function() use ($app) {
			return $app['security.user_provider.admin'];
		});

		$app['security.encoder_factory'] = $app->share(function() use ($app) {
			return new EncoderFactory(array(
				'Symfony\Component\Security\Core\User\UserInterface' => new Pbkdf2PasswordEncoder(),
			));
		});

		$app['security.authentication.success_handler.admin'] =
			$app->share(function() use ($app) {
				return new UserAuthenticationSuccessHandler($app['security.http_utils'], [], $app);
			}
		);

		$app['security.authentication.failure_handler.admin'] =
			$app->share(function() use ($app) {
				return new UserAuthenticationFailureHandler($app['kernel'], $app['security.http_utils'], [
						'failure_path' => '/login.html',
				], $app);
			}
		);

		/** @var callable $logoutProto */
		$logoutProto = $app['security.authentication_listener.logout._proto'];
		$app['security.authentication_listener.logout._proto'] = $app->protect(function($name, $options) use ($app, $logoutProto) {
			return $app->share(function() use ($app, $name, $options, $logoutProto) {
				$prototype = $logoutProto($name, $options);
				/** @var LogoutListener $listener */
				$listener = $prototype($app);
				$listener->addHandler(new UserLogoutHandler());
				return $listener;
			});
		});

		// Security voters.
		$app['security.voters.entity_erase'] = $app->share(function($app) {
			return new EntityEraseVoter($app);
		});

		$app['security.voters'] = $app->extend('security.voters', function($voters) use ($app) {
			array_unshift($voters, $app['security.voters.entity_erase']);

			return $voters;
		});
	}
}