<?php
/**
 * Class UpdateArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Implementation\Content\Decorator\HeadingDecorator;
use \Moro\Platform\Application;

/**
 * Class UpdateArticlesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent getEntity()
 */
class UpdateArticlesAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $template    = '@PlatformCMS/admin/content/article-update.html.twig';
	public $route       = 'admin-content-articles-update';
	public $routeIndex  = 'admin-content-articles';
	public $routeDelete = 'admin-content-articles-delete';
	public $routeGetChunk = 'admin-content-chunks-update';
	public $routeAddChunk = 'admin-content-chunks-create';

	/**
	 * @var int
	 */
	protected $_nextEntityId;

	/**
	 * @var int
	 */
	protected $_prevEntityId;

	/**
	 * @var array
	 */
	protected $_patchTextKeys = ['parameters.lead', 'parameters.gallery_text'];

	/**
	 * @var array
	 */
	protected $_diffBlackKeys = ['parameters.chain'];

	/**
	 * @var null|int
	 */
	protected $_parentId;

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$this->_checkFields();

		$entity = $this->getEntity();
		$app = $this->getApplication();

		$parameters = parent::_getViewParameters();
		$parameters['upload'] = $this->getService()->createAdminUploadForm($app, $entity)->createView();
		$parameters['title'] = $this->getEntity()->getName().' - Редактирование текста';
		$parameters['parentId'] = $this->_parentId;

		if ($app->getOption('content.multi_page'))
		{
			$parameters['chunksCount'] = $app->getServiceContentChunks()->getCount('parent_id', $this->_parentId ?: $entity->getId());
		}

		return $parameters;
	}

	/**
	 * @return void
	 */
	protected function _applyForm()
	{
		parent::_applyForm();

		$app = $this->getApplication();
		$entity = $this->getEntity();
		$service = $this->getService();
		$original = $this->getOriginalEntity();
		$changes = $this->getService()->calculateDiff($original, $entity);

		// Выставление меток для обновления страниц, на которых был использован данный материал.
		$routes = $app->getServiceRoutes();
		$tags = ['art-'.$entity->getId()];

		$nextEntity = $service->getEntityByChain($entity, false);
		$prevEntity = $service->getEntityByChain($entity, true);

		$nextEntityId = $nextEntity ? $nextEntity->getId() : null;
		$prevEntityId = $prevEntity ? $prevEntity->getId() : null;

		if ($this->_nextEntityId != $nextEntityId)
		{
			$this->_nextEntityId && ($tags[] = 'art-'.$this->_nextEntityId);
			$nextEntityId && ($tags[] = 'art-'.$nextEntityId);
		}

		if ($this->_prevEntityId != $prevEntityId)
		{
			$this->_prevEntityId && ($tags[] = 'art-'.$this->_prevEntityId);
			$prevEntityId && ($tags[] = 'art-'.$prevEntityId);
		}

		if ($app->getOption('content.headings') && $decorator = HeadingDecorator::newInstance($app, $entity))
		{
			$heading1 = $decorator->getHeading() ?: 'draft';
			$heading2 = HeadingDecorator::newInstance($app, $original)->getHeading() ?: 'draft';

			if ($heading1 != $heading2)
			{
				$tags[] = 'heading-'.$heading1;
				$tags[] = 'heading-'.$heading2;
			}
			elseif (!empty($changes['order_at']) || !empty($changes['name']))
			{
				$tags[] = 'heading-'.$heading1;
			}
		}

		$anonsFields = ['name', 'code', 'icon', 'parameters.lead', 'parameters.link', 'parameters.tags'];
		(array_intersect_key($changes, array_fill_keys($anonsFields, 1))) && ($tags[] = 'ann-'.$entity->getId());

		$routes->setCompileFlagForTag(array_unique($tags));
		$this->_checkFields();
	}

	/**
	 * @return void
	 */
	protected function _checkFields()
	{
		$application = $this->getApplication();
		$entity = $this->getEntity();
		$service = $this->getService();

		// Проверка на уникальность названия материала.
		if ($service->getCount('name', $entity->getName()) > 1)
		{
			$message = sprintf('Название "%1$s" уже используется в другом материале.', $entity->getName());
			$application->getServiceFlash()->alert($message);
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @return $this
	 */
	protected function _setEntity(EntityInterface $entity)
	{
		$service = $this->getService();

		$nextEntity = $service->getEntityByChain($entity, false);
		$prevEntity = $service->getEntityByChain($entity, true);

		$nextEntity && $this->_nextEntityId = $nextEntity->getId();
		$prevEntity && $this->_prevEntityId = $prevEntity->getId();

		return parent::_setEntity($entity);
	}

	/**
	 * @param int $id
	 * @return mixed
	 */
	protected function _doActions($id)
	{
		$result = parent::_doActions($id);

		/** @noinspection PhpUndefinedMethodInspection */
		if ($this->getForm()->get('add_chunk')->isClicked())
		{
			$app = $this->getApplication();
			$query = $this->getRequest()->query->all();
			unset($query['id']);

			$back = $this->getRequest()->query->get('back') ?: $app->url($this->routeIndex, $query);

			$result = $app->redirect($app->url($this->routeAddChunk, [
				'id' => $this->_parentId ?: $this->getEntity()->getId(),
				'back' => $back,
			]));
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($this->getForm()->get('get_chunk')->isClicked())
		{
			$app = $this->getApplication();
			$query = $this->getRequest()->query->all();
			unset($query['id']);

			$number = (int)$this->getRequest()->request->get($this->getForm()->getName())['get_chunk'];
			$back = $this->getRequest()->query->get('back') ?: $app->url($this->routeIndex, $query);

			$result = $app->redirect($app->url($this->routeGetChunk, [
				'n'    => $number,
				'id'   => $this->_parentId ?: $this->getEntity()->getId(),
				'back' => $back,
			]));
		}

		return $result;
	}
}