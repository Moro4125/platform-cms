<?php
/**
 * Class UpdateChunksAction
 */
namespace Moro\Platform\Action\Articles\Chunks;
use \Moro\Platform\Application;
use \Moro\Platform\Action\Articles\UpdateArticlesAction;
use \Symfony\Component\HttpFoundation\Request;
use \Silex\Application as SilexApplication;

/**
 * Class UpdateChunksAction
 * @package Moro\Platform\Action\Articles
 */
class UpdateChunksAction extends UpdateArticlesAction
{
	public $serviceCode  = Application::SERVICE_CONTENT_CHUNKS;
	public $template     = '@PlatformCMS/admin/content/chunk-update.html.twig';
	public $route        = 'admin-content-chunks-update';
	public $routeDelete  = 'admin-content-chunks-delete';
	public $routeArticle = 'admin-content-articles-update';
	public $useTags      = false;

	/**
	 * @var int
	 */
	protected $_number;

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

		$this->_parentId = $id;
		$this->_number = $n;

		if (!$request->query->has('back') && !$request->isXmlHttpRequest())
		{
			return $app->redirect($app->url($this->route, [
				'n'    => $n,
				'id'   => $id,
				'back' => ($request->headers->has('Referer') && $request->headers->get('Referer'))
					? $request->headers->get('Referer')
					: 0,
			]));
		}

		if ($n <= 0)
		{
			$query = $this->getRequest()->query->all();
			$back = $this->getRequest()->query->get('back') ?: $app->url($this->routeIndex, $query);

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

		$this->_chunksCount = $this->getService()->getCount('parent_id', $id);

		$app->getServiceContent()->attach($app->getBehaviorHistory());
		return parent::__invoke($app, $request, $entity->getId());
	}

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$parameters = parent::_getViewParameters();
		$parameters['chunkNumber'] = $this->_number;

		return $parameters;
	}

	/**
	 * @param int $id
	 * @return mixed
	 */
	protected function _doActions($id)
	{
		/** @noinspection PhpUndefinedMethodInspection */
		if ($this->getForm()->get('del_chunk')->isClicked())
		{
			$app = $this->getApplication();
			$query = $this->getRequest()->query->all();
			unset($query['id']);

			$number = $this->_chunksCount;
			$back = $this->getRequest()->query->get('back') ?: $app->url($this->routeIndex, $query);

			$next = $app->url($this->route, ['n' => $number - 1, 'id'   => $this->_parentId, 'back' => $back]);
			$back = $app->url($this->route, ['n' => $number, 'id'   => $this->_parentId, 'back' => $back]);

			return $app->redirect($app->url($this->routeDelete, [
				'n'    => $number,
				'id'   => $this->_parentId,
				'next' => $next,
				'back' => $back,
			]));
		}

		return parent::_doActions($id);
	}

	/**
	 * @return void
	 */
	protected function _checkFields()
	{
	}
}