<?php
/**
 * Class AbstractFileDetachAction
 */
namespace Moro\Platform\Action;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Model\EntityInterface;

/**
 * Class AbstractFileDetachAction
 * @package Moro\Platform\Action
 */
class AbstractFileDetachAction extends AbstractContentAction
{
	public $route;
	public $serviceCode;
	public $idPrefix;

	public $error0 = 'Record with ID %1$s is not exists.';
	public $error1 = 'Wrong attachment for article with ID %1$s.';

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
			throw new NotFoundHttpException(sprintf($this->error0, $id));
		}

		if ($request->getMethod() != 'POST' || !($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT')))
		{
			throw new AccessDeniedHttpException();
		}

		$fileId = (int)$request->get('key');

		if ($file = $serviceFile->getEntityById($fileId, true, EntityInterface::FLAG_GET_FOR_UPDATE))
		{
			if ($file->getKind() != $this->idPrefix.$id)
			{
				throw new NotFoundHttpException(sprintf($this->error1, $id));
			}

			if ($service->isLocked($entity))
			{
				throw new AccessDeniedHttpException();
			}

			$serviceFile->deleteEntityById($fileId);
			$this->onSuccess($id, $file);
		}

		return $app->json($result);
	}

	/**
	 * @param integer $id
	 * @param \Moro\Platform\Model\Implementation\File\FileInterface $file
	 * @throws \Exception
	 */
	protected function onSuccess($id, $file)
	{
	}
}