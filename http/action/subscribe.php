<?php
/**
 * Add user to notification list.
 *
 * How to use this file in the application:
 *    require_once __DIR__.'/../../bootstrap.php'; // Connect this file only once.
 *    require __DIR__.'/../../vendor/moro/platform-cms/http/action/subscribe.php';
 */
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Moro\Platform\Model\Implementation\Subscribers\SubscribersInterface;
use \Symfony\Component\Validator\Validator\RecursiveValidator;
use \Symfony\Component\Validator\ConstraintViolationList;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Email;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

Application::action(
	function(Application $app, Request $request, ArrayObject $context)
	{
		$prev = Request::create($request->headers->get('Referer', '/'));
		$back = Request::create($prev->query->get('back') ?: $request->getSchemeAndHttpHost().'/subscribe.html');
		$context['back'] = $back;

		if ($request->getMethod() != 'POST')
		{
			return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
		}

		// Form validation.
		/** @var RecursiveValidator $validator */
		$validator = $app['validator'];
		$errors = new ConstraintViolationList();

		$errors->addAll($validator->validate(
			$request->request->get('email'),
			[
				new NotBlank(['message' => 'E-mail не может быть пустым.']),
				new Email(['message' => 'Введён некорректный e-mail адрес.']),
			]
		));

		if (count($errors))
		{
			foreach ($errors as $error)
			{
				$error = strtr($error, [
					'(code '.Email::INVALID_FORMAT_ERROR.')' => '',
					'(code '.Email::MX_CHECK_FAILED_ERROR.')' => '',
					'(code '.Email::HOST_CHECK_FAILED_ERROR.')' => '',
				]);
				$app->getServiceFlash()->error($error);
			}

			return $app->redirect($request->getSchemeAndHttpHost().$prev->getRequestUri());
		}

		$email = $request->request->get('email');
		$name  = $request->request->get('name');
		$tags  = $request->request->get('tags') ?: ['Подписка: на всё'];

		$tags = array_filter(array_map('trim', is_array($tags) ? $tags : explode(',', (string)$tags)));
		$service = $app->getServiceSubscribers();

		// Add new subscriber to DB.
		try
		{
			$success = $app->url('api-subscribers-update', [
				'user' => $email,
				'roles' => 'ROLE_WANT_UPDATE_SUBSCRIBER',
				'counter' => 1,
				'back' => $request->getSchemeAndHttpHost().$back->getRequestUri(),
				'name' => $name,
				'tags' => implode(',', $tags),
			]);

			if ($entity = $service->getEntityByEMail($email, true, SubscribersInterface::FLAG_GET_FOR_UPDATE))
			{
				if ($entity->getActive())
				{
					$app->getServiceFlash()->success('Вам на почту отправлено письмо с инструкцией для подтверждения изменений.');

					return [
						'host'    => $request->getHost(),
						'success' => $success,
					];
				}
				else
				{
					$app->getServiceFlash()->error('Ваш e-mail у нас отключён. Обратитесь к администрации сайта.');
				}
			}
			elseif ($app->getOption('notification.confirm'))
			{
				$app->getServiceFlash()->success('Вам на почту отправлено письмо с инструкцией для подтверждения подписки.');

				return [
					'host'    => $request->getHost(),
					'success' => $success,
				];
			}
			else
			{
				$entity = $service->createNewEntityWithId($email);
				$entity->setName($name);
				$entity->setTags($tags);
				$entity->setActive(true);
				$service->commit($entity);
				$app->getServiceFlash()->success('Вы успешно подписались на оповещение о новостях сайта.');
			}
		}
		catch (Exception $exception)
		{
			$app->getServiceFlash()->error($exception->getMessage());
			($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		}

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
		$text = $app->getTwigExtensionMarkdown()->cleanMarkdown($content);
		$html = $app->getTwigExtensionMarkdown()->parseMarkdown($content);

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
/** @var string $success */
?>
Запрос подтверждения
--------------------

Был совершена попытка создания или изменения подписки на новости сайта *<?= $host ?>* для данного почтового адреса.

Если это были Вы, то, пожалуйста, перейдите по [этой ссылке](<?= $success ?>) для подтверждения.

Если вы не запрашивали изменения подписки на новости сайта *<?= $host ?>*, то просто проигнорируйте данное письмо.
Отвечать на данное письмо, в любом случае, не требуется.

С уважением,  <?= "\r" ?>
Администрация сайта [<?= $host ?>](http://<?= $host ?>/)