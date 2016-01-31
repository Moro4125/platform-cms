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
	 * @var string
	 */
	public $routeIndex = 'admin-content-images';

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
				$idList = $service->applyAdminUploadForm($app, $form, $request->query->get('tags'));
			}
			else
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для загрузки изображений на сервер.');
			}
		}

		$fragment = ($idList ? '#selected='.implode(',', $idList) : '');

		if ($back = $request->query->get('back'))
		{
			$url = $back;
		}
		else
		{
			$url = $app->url($this->routeIndex);
		}

		return $app->redirect($url.$fragment);
	}
}