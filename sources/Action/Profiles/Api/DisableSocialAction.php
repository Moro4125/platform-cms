<?php
/**
 * Class DisableSocialAction
 */
namespace Moro\Platform\Action\Profiles\Api;
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Exception;

/**
 * Class DisableSocialAction
 * @package Moro\Platform\Action\Profiles\Api
 */
class DisableSocialAction
{
	/**
	 * @param Application $app
	 * @param Request $request
	 * @return Response
	 *
	 * @throws Exception
	 */
	public function __invoke(Application $app, Request $request)
	{
		$identifier  = $app->getServiceSecurityToken()->getUsername();
		$serviceUser = $app->getServiceUsers();
		$serviceAuth = $app->getServiceUsersAuth();

		$serviceUser->attach($app->getBehaviorHistory());

		try
		{
			$app->getServiceDataBase()->beginTransaction();

			if (!$provider = $request->query->get('provider'))
			{
				throw new Exception('Неправильный URL. Отсутствует параметр "provider".');
			}

			$user = $serviceUser->getEntityByCode($identifier, null, UsersInterface::FLAG_GET_FOR_UPDATE);
			$parameters = $user->getParameters();

			foreach ($serviceAuth->selectEntitiesByUser($user, UsersAuthInterface::FLAG_GET_FOR_UPDATE) as $auth)
			{
				if ($auth->getProvider() == $provider)
				{
					$auth->setProperty(UsersAuthInterface::PROP_BANNED, 1);
					$serviceAuth->commit($auth);
				}
			}

			$parameters['comment'] = sprintf('**API**: Запись аутентификации для "%1$s" заблокирована.', $provider);
			$user->setParameters($parameters);
			$serviceUser->commit($user);

			$app->getServiceDataBase()->commit();
			$message = sprintf('Вход в вашу учётную запись через "%1$s" заблокирован.', $provider);
			$app->getServiceFlash()->success($message);
		}
		catch (Exception $exception)
		{
			$app->getServiceDataBase()->rollBack();
			$app->getServiceFlash()->error($exception->getMessage());
			($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		}

		$back = Request::create($request->query->get('back', '/'));
		return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
	}
}