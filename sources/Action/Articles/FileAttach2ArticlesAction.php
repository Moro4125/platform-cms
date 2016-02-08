<?php
/**
 * Class FileAttach2ArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Action\AbstractContentAction;
use \Moro\Platform\Application;

/**
 * Class FileAttach2ArticlesAction
 * @package Moro\Platform\Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent getEntity()
 */
class FileAttach2ArticlesAction extends AbstractContentAction
{
	public $route = 'admin-content-articles-attach';
	public $routeDetach = 'admin-content-articles-detach';
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
		$result = [ "initialPreview" => [], "initialPreviewConfig" => [], "append" => true ];
		$idList = null;

		if (!$entity = $service->getEntityById($id, true))
		{
			throw new NotFoundHttpException(sprintf('Article with ID %1$s is not exists.', $id));
		}

		while ($request->getMethod() == 'POST')
		{
			$form = $service->createAdminUploadForm($app);

			if (!$form->handleRequest($request)->isValid())
			{
				$result['error'] = 'Форма не прошла валидацию. Обратитесь к администратору с описанием ваших действий.';
				break;
			}

			if (!$app->isGranted('ROLE_EDITOR'))
			{
				$result['error'] = 'У вас недостаточно прав для загрузки файлов на сервер.';
				break;
			}

			if ($service->isLocked($entity))
			{
				$result['error'] = 'Нельзя добавлять файлы к заблокированному другим пользователем материалу.';
				break;
			}

			$idList = $service->applyAdminUploadForm($app, $form, $id);

			if (!is_array($idList))
			{
				$result['error'] = (string)$idList;
				break;
			}

			$app->getServiceRoutes()->setCompileFlagForTag(['art-'.$id]);

			break;
		}

		$fileService = $app->getServiceFile();
		$flag = $result['append'] && isset($idList);
		$list = $flag ? $fileService->selectByIds($idList) : $fileService->selectByKind("a$id");

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
}