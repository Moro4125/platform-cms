<?php
/**
 * Class ApplyRightsAction
 */
namespace Moro\Platform\Action\Profiles\Api;
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Exception;

/**
 * Class ApplyRightsAction
 * @package Moro\Platform\Action\Profiles\Api
 */
class ApplyRightsAction
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

			$user = $serviceUser->getEntityByCode($identifier, null, UsersInterface::FLAG_GET_FOR_UPDATE);
			$parameters = $user->getParameters();
			$roles = implode(',', isset($parameters['roles']) ? $parameters['roles'] : []);

			foreach ($serviceAuth->selectEntitiesByUser($user, UsersAuthInterface::FLAG_GET_FOR_UPDATE) as $auth)
			{
				$auth->setProperty(UsersAuthInterface::PROP_ROLES, $roles);
				$serviceAuth->commit($auth);
			}

			$parameters['comment'] = '**API**: Права доступа были применены для всех записей аутентификации.';
			$user->setParameters($parameters);
			$serviceUser->commit($user);

			$app->getServiceDataBase()->commit();
			$app->getServiceFlash()->success('Ваша учётная запись из социальной сети подтверждена.');
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