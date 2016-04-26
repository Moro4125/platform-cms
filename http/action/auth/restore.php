<?php
/**
 * Reset user password.
 */
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Moro\Platform\Provider\Twig\MarkdownExtension;
use \Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine;
use \Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::action(
	function(Application $app, Request $request, ArrayObject $context)
	{
		$prev = Request::create($request->headers->get('Referer', '/'));
		$back = Request::create($prev->query->get('back') ?: $request->getSchemeAndHttpHost().'/register.html');
		$context['back'] = $back;

		if ($request->getMethod() != 'POST')
		{
			return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
		}

		$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&()_+-=~';
		$email = $request->request->get('email');
		$provider = UsersAuthInterface::MAIN_PROVIDER;
		$password = '';

		for ($i = 0, $cCount = strlen($chars) - 1; $i < 8; $i++)
		{
			$password .= substr($chars, mt_rand(0, $cCount - 14 * intval($i == 0)), 1);
		}

		if (!$profile = $app->getServiceUsers()->getEntityByCode($email, true))
		{
			$app->getServiceFlash()->error('Вы ввели неизвестный e-mail адрес.');
			return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
		}

		if (!$enter = $app->getServiceUsersAuth()->getEntityByProviderAndUser($provider, $profile, true))
		{
			$credential = (new Pbkdf2PasswordEncoder())->encodePassword($password, $email);

			$enter = $app->getServiceUsersAuth()->createEntity($provider, $email, $credential, true);
			$enter->setProperty(UsersAuthInterface::PROP_USER_ID, $profile->getId());
			$parameters = ['ip' => $request->getClientIps()];
			$parameters = array_merge($parameters, array_intersect_key($request->headers->all(), [
				'host' => true,
				'cookie' => true,
				'referer' => true,
				'user-agent' => true,
			]));
			$enter->setParameters($parameters);

			$app->getServiceUsersAuth()->commit($enter);
		}

		$link = $app->url('api-users-reset-password', [
			'user' => $enter->getIdentifier(),
			'roles' => 'ROLE_WANT_RESET_PASSWORD',
			'counter' => 1,
			'credential' => (new Pbkdf2PasswordEncoder())->encodePassword($password, $enter->getIdentifier()),
			'back' => $request->getSchemeAndHttpHost().$back->getRequestUri(),
		]);

		return [
			'host'     => $request->getHost(),
			'password' => $password,
			'link'     => $link,
		];
	},
	// Middleware.
	function(callable $action, ArrayObject $context)
	{
		/** @var Request $request */
		/** @var Response $response */
		$response = $action();
		$app = Application::getInstance();
		$request = $app['request'];

		if ($response->getStatusCode() != 200)
		{
			return $response;
		}

		$content = $response->getContent();
		$text = strtr($content, ['*' => '', '[' => ' ', ']' => ' ']);
		$html = (new MarkdownExtension(new MichelfMarkdownEngine()))->parseMarkdown($content);

		/** @var \Swift_Message $message */
		$message = $app->getServiceMailer()->createMessage();
		$message->addTo($request->request->get('email'));
		$message->addFrom($app->getOption('notification.from.email'));
		$message->setSubject('Ответ на запрос сброса пароля на сайте '.$request->getHost());
		$message->addPart($text, 'text/plain', 'utf-8');
		$message->addPart($html, 'text/html',  'utf-8');
		$app->getServiceMailer()->send($message);
		$app->getServiceMailer()->getTransport()->stop();

		return $app->redirect($request->getSchemeAndHttpHost().$context['back']->getRequestUri());
	}
);
/** @var string $host */
/** @var string $password */
/** @var string $link */
?>
Сброс пароля
------------

Вы запросили сброс пароля для вашего аккаунта на сайте *<?= $host ?>*

Ваш новый пароль: **<?= $password ?>**

Для замены старого пароля на новый, перейдите по [этой ссылке](<?= $link ?>) на сайт *<?= $host ?>*.

Если вы не запрашивали сброс пароля на сайте *<?= $host ?>*, то просто проигнорируйте данное письмо.
Отвечать на данное письмо, в любом случае, не требуется.

С уважением,  <?= "\r" ?>
Администрация сайта [<?= $host ?>](http://<?= $host ?>/)