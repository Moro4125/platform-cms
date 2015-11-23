<?php
/**
 * Class UploadImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;


/**
 * Class UploadImagesAction
 * @package Action
 */
class UploadImagesAction
{
	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		$service = $app->getServiceFile();
		$form = $service->createAdminUploadsForm($app);
		$idList = [];

		if ($form->handleRequest($request)->isValid())
		{
			if ($app->isGranted('ROLE_EDITOR'))
			{
				$idList = $service->applyAdminUploadForm($app, $form);
			}
			else
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для загрузки изображений на сервер.');
			}
		}

		return $app->redirect($app->url('admin-content-images').($idList ? '#selected='.implode(',', $idList) : ''));
	}
}