<?php
/**
 * Login by login (e-mail) and password.
 *
 * How to use this file in the application:
 *    require_once __DIR__.'/../../../bootstrap.php'; // Connect this file only once.
 *    require __DIR__.'/../../../vendor/moro/platform-cms/http/action/auth/login.php';
 */
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Moro\Platform\Security\User\PlatformUserProvider;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;
use \Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use \Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::getInstance()->get('/', function() {
	throw new NotFoundHttpException();
});

Application::action(function(Application $app, Request $request) {
	$prev = Request::create($request->headers->get('Referer', '/'));
	$back = Request::create($prev->query->get('back') ?: $request->getSchemeAndHttpHost().'/login.html');

	$username = $request->request->get('_username');
	$password = $request->request->get('_password');
	$remember = $request->request->get('_remember_me');

	try
	{
		if (empty($username) || empty($password))
		{
			throw new UsernameNotFoundException('', 1);
		}

		$service = $app->getServiceUsersAuth();
		$list = $service->selectEntities(0, 1, null, [
			UsersAuthInterface::PROP_PROVIDER => 'platform-cms',
			UsersAuthInterface::PROP_IDENTIFIER => $username,
		]);

		/** @var UsersAuthInterface $auth */
		if (empty($list) || !$auth = reset($list))
		{
			throw new UsernameNotFoundException('', 2);
		}

		$provider = new PlatformUserProvider($app->getServiceUsers(), $app->getServiceUsersAuth());
		$user = $provider->loadUserByUsername($username);

		if ((new Pbkdf2PasswordEncoder())->isPasswordValid($user->getPassword(), $password, $user->getSalt()))
		{
			$enter = $user->getAuthEnter();
			$count = $enter->getProperty(UsersAuthInterface::PROP_SUCCESS);
			$enter->setProperty(UsersAuthInterface::PROP_UPDATED_IP, implode(', ', $request->getClientIps()));
			$enter->setProperty(UsersAuthInterface::PROP_ORDER_AT, time());
			$enter->setProperty(UsersAuthInterface::PROP_SUCCESS, $count + 1);
			$enter->setProperty(UsersAuthInterface::PROP_RESULT, 1);
			$app->getServiceUsersAuth()->commit($enter);
		}
		else
		{
			$enter = $user->getAuthEnter();
			$count = $enter->getProperty(UsersAuthInterface::PROP_FAILURE);
			$enter->setProperty(UsersAuthInterface::PROP_UPDATED_IP, implode(', ', $request->getClientIps()));
			$enter->setProperty(UsersAuthInterface::PROP_FAILURE, $count + 1);
			$enter->setProperty(UsersAuthInterface::PROP_RESULT, 0);
			$app->getServiceUsersAuth()->commit($enter);

			throw new UsernameNotFoundException('', 3);
		}

		$token = new UsernamePasswordToken($user, null, 'public', $user->getRoles());
		$app['security.token_storage']->setToken($token);

		$event = new InteractiveLoginEvent($app['request'], $token);
		$app['dispatcher']->dispatch("security.interactive_login", $event);

		// Magic auth hack :-)
		$app['session']->set('_security_public', serialize($token));
		$app['session']->set('_security_admin',  serialize($token));

		// Last actions.
		$response = $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());

		if ($remember)
		{
			$app['security.remember_me.service.admin']->loginSuccess(
				$request,
				$response,
				$app->getServiceSecurityToken()
			);
		}

		return $response;
	}
	catch (UsernameNotFoundException $exception)
	{
		$app->getServiceFlash()->error('Ошибка. Неверное сочетания логина и пароля.');
	}
	catch (Exception $exception)
	{
		$app->getServiceFlash()->error($exception->getMessage());
		($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
	}

	return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
});