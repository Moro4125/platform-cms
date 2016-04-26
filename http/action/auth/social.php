<?php
/**
 * Social auth.
 */
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Moro\Platform\Security\User\PlatformUserProvider;
use \Moro\Platform\Provider\Twig\MarkdownExtension;
use \Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Session\Session;
use \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use \Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use \Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::action(
	function(Application $app, Request $request, ArrayObject $context)
	{
		$social = $request->query->get('provider');
		$sessionKeyBack = $social.'_back_url';

		/** @var Session $session */
		$session = $app['session'];

		/** @var Request $back */
		if (!$back = $session->get($sessionKeyBack))
		{
			$prev = Request::create($request->headers->get('Referer', '/'));
			$back = Request::create($prev->query->get('back') ?: $request->getSchemeAndHttpHost().'/login.html');
			$session->set($sessionKeyBack, $back);
			$session->save();
			return $app->redirect($request->getRequestUri());
		}

		if (empty($social))
		{
			$session->set($sessionKeyBack, false);
			return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
		}

		$context['back'] = $back;

		$options = [
			'base_url' => $request->getSchemeAndHttpHost().'/action/auth/hybrid.php',
			'providers' => $app['hybridauth.providers'],
			'debug_mode' => $app->getOption('debug'),
			'debug_file' => $app->getOption('path.temp').'/hybrid-auth.log',
		];

		$hybridAuth = new Hybrid_Auth($options);
		$adapter = $hybridAuth->authenticate($social);

		/** @noinspection PhpUndefinedMethodInspection */
		$userProfile = $adapter->getUserProfile();
		/** @var Hybrid_User_Profile $userProfile */

		try
		{
			$response = $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
			$userService = $app->getServiceUsers();
			$service = $app->getServiceUsersAuth();
			$flags = UsersAuthInterface::FLAG_GET_FOR_UPDATE;

			if (!$app->getOption('social.active'))
			{
				$message = 'Вход на сайт через социальные сети закрыт.';
				throw new AccessDeniedException($message);
			}

			$enter = $service->getEntityByProviderAndIdentifier($social, $userProfile->identifier, true, $flags)
				?: $service->createEntity($social, $userProfile->identifier, sha1($userProfile->identifier), true);

			if (empty($enter->getUserId()))
			{
				$email = $userProfile->email ?: 'temp_'.mt_rand(100000, 999999).'@'.$request->getHost();
				$context['email'] = $email;

				if ($user = $userService->getEntityByCode($email, true))
				{
					$success = $app->url('api-users-apply-rights', [
						'user' => $user->getEmail(),
						'roles' => 'ROLE_WANT_CONFIRM_SOCIAL',
						'counter' => 1,
						'back' => $request->getSchemeAndHttpHost().$back->getRequestUri(),
					]);

					$failure = $app->url('api-users-disable-social', [
						'user' => $user->getEmail(),
						'provider' => $social,
						'roles' => 'ROLE_WANT_DISABLE_SOCIAL',
						'counter' => 1,
						'back' => $request->getSchemeAndHttpHost().$back->getRequestUri(),
					]);

					$response = [
						'host'     => $request->getHost(),
						'provider' => $social,
						'success'  => $success,
						'failure'  => $failure,
					];
				}
				else
				{
					$name = $userProfile->displayName;
					preg_match('{^id\\d+$}', $name) && $name = trim($userProfile->firstName.' '.$userProfile->lastName);

					$user = $userService->createEntity($email, true);
					$user->setEmail($email);
					$user->setName($name);
					$user->setParameters([
						'first_name'  => $userProfile->firstName,
						'second_name' => $userProfile->lastName,
						'roles'       => ['ROLE_USER'],
					]);
					$userService->commit($user);
				}

				$enter->setProperty(UsersAuthInterface::PROP_USER_ID, $user->getId());
				$enter->setProperty(UsersAuthInterface::PROP_ROLES, 'ROLE_USER');
			}

			if ($enter->getBanned())
			{
				$message = 'Вход через %1$s для пользователя «%2$s» закрыт.';
				$message = sprintf($message, $social, $userService->getEntityById($enter->getUserId())->getName());
				throw new AccessDeniedException($message);
			}

			$enter->setOrderAt(time());
			$enter->setProperty(UsersAuthInterface::PROP_SUCCESS, $enter->getSuccess() + 1);
			$enter->setParameters(array_merge($enter->getParameters(), array_filter((array)$userProfile)));
			$enter->setProperty(UsersAuthInterface::PROP_UPDATED_IP, implode(', ', $request->getClientIps()));
			$enter->setProperty(UsersAuthInterface::PROP_RESULT, 1);
			$service->commit($enter);

			$provider = new PlatformUserProvider($app->getServiceUsers(), $app->getServiceUsersAuth(), $social);
			$userAuth = $provider->loadUserByUsername($userProfile->identifier);

			$token = new UsernamePasswordToken($userAuth, null, 'public', $userAuth->getRoles());
			$app['security.token_storage']->setToken($token);

			$event = new InteractiveLoginEvent($app['request'], $token);
			$app['dispatcher']->dispatch("security.interactive_login", $event);

			// Magic auth hack :-)
			$app['session']->set('_security_public', serialize($token));
			$app['session']->set('_security_admin',  serialize($token));

			$session->set($sessionKeyBack, false);
			return $response;
		}
		catch (AccessDeniedException $exception)
		{
			$app->getServiceFlash()->error($exception->getMessage());
		}
		catch (Exception $exception)
		{
			$app->getServiceFlash()->error($exception->getMessage());
			($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		}

		$session->set($sessionKeyBack, false);
		return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
	},
	function (callable $action, ArrayObject $context)
	{
		$app = Application::getInstance();
		/** @var Request $request */
		$request = $app['request'];
		/** @var Response $response */
		$response = $action();

		if ($response->getStatusCode() != 200)
		{
			return $response;
		}

		$content = $response->getContent();
		$text = strtr($content, ['*' => '', '[' => ' ', ']' => ' ']);
		$html = (new MarkdownExtension(new MichelfMarkdownEngine()))->parseMarkdown($content);

		/** @var \Swift_Message $message */
		$message = $app->getServiceMailer()->createMessage();
		$message->addTo($context['email']);
		$message->addFrom($app->getOption('notification.from.email'));
		$message->setSubject('Запрос на подтверждение от сайта '.$request->getHost());
		$message->addPart($text, 'text/plain', 'utf-8');
		$message->addPart($html, 'text/html',  'utf-8');
		$app->getServiceMailer()->send($message);
		$app->getServiceMailer()->getTransport()->stop();

		return $app->redirect($request->getSchemeAndHttpHost().$context['back']->getRequestUri());
	}
);
/** @var string $host */
/** @var string $provider */
/** @var string $success */
/** @var string $failure */
?>
Запрос подтверждения
--------------------

Был совершен вход на сайт *<?= $host ?>* под вашим аккаунтом, в первые из социальной сети *<?= $provider ?>*.

Если это были Вы, то перейдите по [этой ссылке](<?= $success ?>) для подтверждения.

В случае, когда у Вас нет аккаунта в социальной сети *<?= $provider ?>*,
перейдите по [этой ссылке](<?= $failure ?>) для блокировки подозрительной активности.

С уважением,  <?= "\r" ?>
Администрация сайта [<?= $host ?>](http://<?= $host ?>/)