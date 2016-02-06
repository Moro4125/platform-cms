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

	/**
	 * @var int
	 */
	protected $_nextEntityId;

	/**
	 * @var int
	 */
	protected $_prevEntityId;

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$this->_checkFields();
		$parameters = parent::_getViewParameters();
		$parameters['upload'] = $this->getService()->createAdminUploadForm($this->getApplication(), $this->getEntity())->createView();
		$parameters['title'] = $this->getEntity()->getName().' - Редактирование статьи';

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
}