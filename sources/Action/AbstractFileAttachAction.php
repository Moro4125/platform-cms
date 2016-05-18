<?php
/**
 * Class AbstractFileAttachAction
 */
namespace Moro\Platform\Action;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Silex\Application as SilexApplication;

/**
 * Class AbstractFileAttachAction
 * @package Moro\Platform\Action
 */
class AbstractFileAttachAction extends AbstractContentAction
{
	public $route;
	public $routeDetach;
	public $serviceCode;
	public $idPrefix;

	public $error0 = 'Record with ID %1$s is not exists.';
	public $error1 = 'Форма не прошла валидацию. Обратитесь к администратору с описанием ваших действий.';
	public $error2 = 'У вас недостаточно прав для загрузки файлов на сервер.';
	public $error3 = 'Нельзя добавлять файлы к заблокированной другим пользователем записи.';

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
		assert(!empty($this->routeDetach));

		$this->setApplication($app);
		$this->setRequest($request);

		$service = $this->getService();
		$result = [ "initialPreview" => [], "initialPreviewConfig" => [], "append" => true ];
		$idList = null;

		if (!$entity = $service->getEntityById($id, true))
		{
			throw new NotFoundHttpException(sprintf($this->error0, $id));
		}

		while ($request->getMethod() == 'POST')
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$form = $service->createAdminUploadForm($app);

			/** @var \Symfony\Component\Form\Form $form */
			if (!$form->handleRequest($request)->isValid())
			{
				$result['error'] = $this->error1;
				break;
			}

			if (!($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT')))
			{
				$result['error'] = $this->error2;
				break;
			}

			if ($service->isLocked($entity))
			{
				$result['error'] = $this->error3;
				break;
			}

			/** @noinspection PhpUndefinedMethodInspection */
			$idList = $service->applyAdminUploadForm($app, $form, $id);

			if (!is_array($idList))
			{
				$result['error'] = (string)$idList;
				$idList = null;
				break;
			}

			$this->onSuccess($id);

			break;
		}

		$fileService = $app->getServiceFile();
		$flag = $result['append'] && isset($idList);
		$list = $flag ? $fileService->selectByIds($idList, 0) : $fileService->selectByKind($this->idPrefix.$id);

		foreach ($list as $file)
		{
			$url = htmlspecialchars($app->url('download', ['file' => $file]));
			$result["initialPreview"][] = "<"."a href='$url' title='Download file' target='_blank' class='b-file-upload__link glyphicon glyphicon-download'></a>";
			$result["initialPreviewConfig"][] = [
				"caption" => $file->getName(),
				"url" => $app->url($this->routeDetach, ['id' => $id]),
				"key" => $file->getId(),
			];
		}

		return $app->json($result);
	}

	/**
	 * @param integer $id
	 * @throws \Exception
	 */
	protected function onSuccess($id)
	{
	}
}