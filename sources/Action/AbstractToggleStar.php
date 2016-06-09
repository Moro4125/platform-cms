<?php
/**
 * Class AbstractToggleStar
 */
namespace Moro\Platform\Action;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Application;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\Star\StarInterface;
use \Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AbstractToggleStar
 * @package Moro\Platform\Action
 */
abstract class AbstractToggleStar extends AbstractContentAction
{
	/**
	 * @param Application|SilexApplication $app
	 * @param Request $request
	 * @param integer $id
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $id)
	{
		$this->setApplication($app);
		$this->setRequest($request);
		$service = $this->getService();

		if (!$request->isXmlHttpRequest() || $request->getMethod() !== 'POST')
		{
			throw new BadRequestHttpException('This URL required POST method and AJAX request.');
		}

		if (!$entity = $service->getEntityById($id, true, EntityInterface::FLAG_SYSTEM_CHANGES))
		{
			throw new NotFoundHttpException(sprintf('Запись с идентификатором %1$s отсутствует.', $id));
		}

		try
		{
			if (!$app->isGranted('ROLE_EDITOR') && !$app->isGranted('ROLE_CLIENT'))
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для данного действия.');
			}
			elseif ($entity instanceof StarInterface)
			{
				$user = $app->getServiceSecurityToken()->getUsername();
				$hasStar = $entity->hasStar($user);
				$hasStar ? $entity->delStar($user) : $entity->addStar($user);
				$service->commit($entity);

				return $app->json(['status' => $hasStar ? 0 : 1]);
			}

			throw new \LogicException('Entity must implements StarInterface.');
		}
		catch (Exception $exception)
		{
			$app->getServiceFlash()->error(basename(get_class($exception)).': '.$exception->getMessage());
			($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		}

		return null;
	}
}