<?php
/**
 * Class SetTopArticlesAction
 */
namespace Moro\Platform\Action;
use \Silex\Application as SilexApplication;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtInterface;


/**
 * Class SetTopArticlesAction
 * @package Action\Articles
 */
class AbstractSetTopAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

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

		if (!$app->isGranted('ROLE_EDITOR'))
		{
			$app->getServiceFlash()->error('У вас недостаточно прав для изменения порядка записей.');
		}
		elseif ($entity = $this->getService()->getEntityById($id, true))
		{
			$this->_setEntity($entity);
			$this->execute();
		}
		else
		{
			$message = sprintf('Записи с ID "%1$s" не существует.', $id);
			$app->getServiceFlash()->error($message);
		}

		return $app->redirect($back);
	}

	/**
	 * @throws \Exception
	 */
	public function execute()
	{
		$app = $this->getApplication();
		$entity = $this->getEntity();
		$id = $entity->getId();

		if ($entity instanceof OrderAtInterface)
		{
			$entity->setOrderAt(time());
			/** @var \Moro\Platform\Model\EntityInterface $entity */
			$this->getService()->commit($entity);

			$message = sprintf('Запись "%1$s" успешно поднята на первое место.', $entity->getProperty('name'));
			$app->getServiceFlash()->success($message);
		}
		else
		{
			$message = sprintf('Сущность с ID "%1$s" не реализует необходимый интерфейс.', $id);
			$app->getServiceFlash()->error($message);
		}
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