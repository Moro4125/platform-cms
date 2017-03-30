<?php
/**
 * Class AbstractCloneAction
 */
namespace Moro\Platform\Action;
use \Silex\Application as SilexApplication;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Moro\Platform\Model\EntityInterface;

/**
 * Class AbstractCloneAction
 * @package Action
 */
abstract class AbstractCloneAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

	/**
	 * @var string  Название "пути" к действию по отображению страницы редактирования.
	 */
	public $routeUpdate;

	/**
	 * @var \Moro\Platform\Model\EntityInterface
	 */
	protected $_entity;

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $id)
	{
		$this->setApplication($app);
		$this->setRequest($request);

		$back = $request->headers->get('Referer', $app->url($this->routeIndex), true);
		$back = preg_replace('{^https?://[^/]+}', '', $back);

		if (!($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT')))
		{
			$app->getServiceFlash()->error('У вас недостаточно прав для клонирования записи.');
		}
		elseif ($entity = $this->getService()->getEntityById($id, true, EntityInterface::FLAG_GET_FOR_UPDATE))
		{
			$this->_setEntity($entity);
			$newId = $this->execute();
			$back = $app->url($this->routeUpdate, ['id' => $newId, 'back' => $back]);
		}
		else
		{
			$message = sprintf('Записи с ID "%1$s" не существует.', $id);
			$app->getServiceFlash()->error($message);
		}

		return $app->redirect($back);
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	public function execute()
	{
		$app = $this->getApplication();
		$entity = $this->getEntity();
		$service = $this->getService();

		$properties = array_diff_key($entity->getProperties(), [
			'id' => null,
			'created_at' => null,
			'created_by' => null,
			'updated_at' => null,
			'updated_by' => null,
		]);

		$newEntity = $service->createNewEntityWithId();
		$newEntity->setProperties($this->_prepareClone($properties, $newEntity->getId()));
		$service->commit($newEntity);

		$app->getServiceFlash()->success('Запись удачно склонирована. Внесите необходимые изменения.');

		return $newEntity->getId();
	}

	/**
	 * @param array $properties
	 * @param integer $id
	 * @return array
	 */
	protected function _prepareClone($properties, $id)
	{
		unset($id);
		return $properties;
	}

	/**
	 * @param EntityInterface $entity
	 * @return $this
	 */
	protected function _setEntity(EntityInterface $entity)
	{
		$this->_entity = $entity;
		return $this;
	}

	/**
	 * @return EntityInterface
	 */
	public function getEntity()
	{
		return $this->_entity;
	}
}