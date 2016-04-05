<?php
/**
 * Class FileDetach2ArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Action\AbstractContentAction;
use \Moro\Platform\Application;

/**
 * Class FileDetach2ArticlesAction
 * @package Moro\Platform\Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent getEntity()
 */
class FileDetach2ArticlesAction extends AbstractContentAction
{
	public $route = 'admin-content-articles-detach';
	public $serviceCode = Application::SERVICE_CONTENT;

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $id)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->route));

		$this->setApplication($app);
		$this->setRequest($request);

		$service = $this->getService();
		$serviceFile = $app->getServiceFile();
		$result = [];

		if (!$entity = $service->getEntityById($id, true))
		{
			throw new NotFoundHttpException(sprintf('Article with ID %1$s is not exists.', $id));
		}

		if ($request->getMethod() != 'POST' || !($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT')))
		{
			throw new AccessDeniedHttpException();
		}

		$fileId = (int)$request->get('key');

		if ($file = $serviceFile->getEntityById($fileId, true, EntityInterface::FLAG_GET_FOR_UPDATE))
		{
			if ($file->getKind() != "a$id")
			{
				throw new NotFoundHttpException(sprintf('Wrong attachment for article with ID %1$s.', $id));
			}

			if ($service->isLocked($entity))
			{
				throw new AccessDeniedHttpException();
			}

			$serviceFile->deleteEntityById($fileId);
			$app->getServiceRoutes()->setCompileFlagForTag(['art-'.$id, 'file-'.$file->getSmallHash()]);
		}

		return $app->json($result);
	}
}