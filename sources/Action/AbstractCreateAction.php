<?php
/**
 * Class AbstractCreateAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\EntityInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;

/**
 * Class AbstractCreateAction
 * @package Action
 */
abstract class AbstractCreateAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

	/**
	 * @var string  Название "пути" к действию по редактированию записи.
	 */
	public $routeUpdate;

	/**
	 * @var EntityInterface
	 */
	protected $_entity;

	/**
	 * @var array Список ярлыков, присваемых новой записи по-умолчанию.
	 */
	protected $_tags;

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->route));
		assert(!empty($this->routeIndex));
		assert(!empty($this->routeUpdate));

		$this->setApplication($app);
		$this->setRequest($request);

		if (!$request->query->has('back'))
		{
			return $app->redirect($app->url($this->route, [
				'tags' => $request->query->get('tags'),
				'back' => ($request->headers->has('Referer') && $request->headers->get('Referer'))
					? $request->headers->get('Referer')
					: 0,
			]));
		}

		if (!($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT')))
		{
			$app->getServiceFlash()->error('У вас недостаточно прав для создания новой записи.');

			return $app->redirect($request->query->get('back') ?: $app->url($this->routeIndex));
		}

		$this->getService()->attach($app->getBehaviorHistory());
		$this->_entity = $this->_createNewEntity();

		$tags = array_filter(array_map('trim', explode(',', (string)rtrim($request->query->get('tags'), '.'))));

		if ($tags || $this->_tags)
		{
			if ($this->_entity instanceof TagsEntityInterface && $this->_entity instanceof EntityInterface)
			{
				$tags = array_unique(array_merge($tags, (array)$this->_tags));
				$this->_entity->addTags($tags);

				$this->getService()->commit($this->_entity);
			}
		}

		return $app->redirect($app->url($this->routeUpdate, $this->_getRedirectParameters()));
	}

	/**
	 * @return \Moro\Platform\Model\EntityInterface
	 */
	protected function _createNewEntity()
	{
		return $this->getService()->createNewEntityWithId();
	}

	/**
	 * @return array
	 */
	protected function _getRedirectParameters()
	{
		return [
			'id'   => $this->_entity->getId(),
			'back' => $this->getRequest()->query->get('back') ?: $this->getApplication()->url($this->routeIndex)
		];
	}
}