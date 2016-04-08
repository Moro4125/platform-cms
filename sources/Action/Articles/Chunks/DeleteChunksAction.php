<?php
/**
 * Class DeleteChunksAction
 */
namespace Moro\Platform\Action\Articles\Chunks;
use Moro\Platform\Action\Articles\DeleteArticlesAction;
use Moro\Platform\Application;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application as SilexApplication;

/**
 * Class DeleteChunksAction
 * @package Moro\Platform\Action\Articles\Chunks
 */
class DeleteChunksAction extends DeleteArticlesAction
{
	public $serviceCode  = Application::SERVICE_CONTENT_CHUNKS;
	public $template     = '@PlatformCMS/admin/content/chunk-delete.html.twig';
	public $route        = 'admin-content-chunks-delete';
	public $routeArticle = 'admin-content-articles-update';

	/**
	 * @var int
	 */
	protected $_chunksCount;

	/**
	 * @param SilexApplication|Application $app
	 * @param Request $request
	 * @param int $id
	 * @param null|int $n
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $id, $n = null)
	{
		if (!$app->getOption('content.multi_page'))
		{
			$query = $this->getRequest()->query->all();
			$back = $this->getRequest()->query->get('back') ?: $app->url($this->routeIndex, $query);
			$app->getServiceFlash()->error('Функционал многостраничных материалов отключён.');

			return $app->redirect($app->url($this->routeArticle, [
				'id'   => $id,
				'back' => $back,
			]));
		}

		$this->_chunksCount = $this->getService()->getCount('parent_id', $id);

		if ($n < $this->_chunksCount)
		{
			$query = $this->getRequest()->query->all();
			$back = $this->getRequest()->query->get('back') ?: $app->url($this->routeIndex, $query);

			$app->getServiceFlash()->error('Удалять можно только последнюю часть записи.');

			return $app->redirect($app->url($this->routeArticle, [
				'id'   => $id,
				'back' => $back,
			]));
		}

		$list = $this->getService()->selectEntities($n - 1, 1, 'created_at', 'parent_id', $id);

		if (empty($list) || !$entity = reset($list))
		{
			$app->getServiceFlash()->error("Части номер $n у записи с идентификатором $id не существует.");
			return $app->redirect($request->query->get('back') ?: $app->url($this->routeIndex));
		}

		$app->getServiceContent()->attach($app->getBehaviorHistory());
		return parent::__invoke($app, $request, $entity->getId());
	}
}