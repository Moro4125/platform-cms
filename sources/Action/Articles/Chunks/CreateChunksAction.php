<?php
/**
 * Class CreateChunksAction
 */
namespace Moro\Platform\Action\Articles\Chunks;
use \Moro\Platform\Action\Articles\CreateArticlesAction;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Silex\Application as SilexApplication;

/**
 * Class CreateChunksAction
 * @package Moro\Platform\Action\Articles\Chunks
 */
class CreateChunksAction extends CreateArticlesAction
{
	public $serviceCode = Application::SERVICE_CONTENT_CHUNKS;
	public $parentsCode = Application::SERVICE_CONTENT;
	public $route       = 'admin-content-chunks-create';
	public $routeUpdate = 'admin-content-chunks-update';
	public $routeArticle = 'admin-content-articles-update';

	/**
	 * @var int
	 */
	protected $_parentId;

	/**
	 * @param SilexApplication|Application $app
	 * @param Request $request
	 * @param int $id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $id = null)
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

		/** @noinspection PhpUndefinedMethodInspection */
		$this->getService()->setCurrentParentId($id);
		$this->_parentId = $id;
		$app->getServiceContent()->attach($app->getBehaviorHistory());
		return parent::__invoke($app, $request);
	}

	/**
	 * @return \Moro\Platform\Model\EntityInterface
	 */
	protected function _createNewEntity()
	{
		/** @var \Moro\Platform\Model\Implementation\Content\EntityContent $entity */
		$entity = parent::_createNewEntity();

		return $entity;
	}

	/**
	 * @return array
	 */
	protected function _getRedirectParameters()
	{
		$parameters = parent::_getRedirectParameters();
		$parameters['id'] = $this->_parentId;
		$parameters['n'] = $this->getService()->getCount('parent_id', $this->_parentId);

		return $parameters;
	}
}