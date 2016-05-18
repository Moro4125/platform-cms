<?php
/**
 * Class UpdateEntityAction
 */
namespace Moro\Platform\Action\Subscribers\Api;
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Exception;

/**
 * Class UpdateEntityAction
 * @package Moro\Platform\Action\Subscribers\Api
 */
class UpdateEntityAction
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

			$name = $request->query->get('name');
			$tags = array_filter(array_map('trim', explode(',', $request->query->get('tags'))));

			if (!$subscriber = $service->getEntityByEMail($userEmail, true, UsersInterface::FLAG_GET_FOR_UPDATE))
			{
				$subscriber = $service->createNewEntityWithId($userEmail, true);
			}

			$name && $subscriber->setName($name);
			$tags && $subscriber->setTags($tags);
			$subscriber->setActive(true);

			$parameters['comment'] = '**API**: Были внесены изменения в запись подписчика.';
			$subscriber->setParameters($parameters);
			$service->commit($subscriber);

			$app->getServiceDataBase()->commit();
			$app->getServiceFlash()->success('Изменения записи вашей подписки были подтверждены.');
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