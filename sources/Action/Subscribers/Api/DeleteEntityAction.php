<?php
/**
 * Class DeleteEntityAction
 */
namespace Moro\Platform\Action\Subscribers\Api;
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Exception;

/**
 * Class DeleteEntityAction
 * @package Moro\Platform\Action\Subscribers\Api
 */
class DeleteEntityAction
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
		$userEmail = $app->getServiceSecurityToken()->getUsername();
		$service   = $app->getServiceSubscribers();

		$service->attach($app->getBehaviorHistory());

		try
		{
			$app->getServiceDataBase()->beginTransaction();

			if ($subscriber = $service->getEntityByEMail($userEmail, true, UsersInterface::FLAG_GET_FOR_UPDATE))
			{
				$subscriber->setActive(false);
				$service->commit($subscriber);
			}

			$app->getServiceDataBase()->commit();
			$app->getServiceFlash()->success('Вы успешно отменили подписку на новости сайта.');
		}
		catch (Exception $exception)
		{
			$app->getServiceDataBase()->rollBack();
			$app->getServiceFlash()->error($exception->getMessage());
			($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		}

		return $app->redirect($request->getSchemeAndHttpHost().'/');
	}
}