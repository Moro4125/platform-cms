<?php
/**
 * Class AbstractDeleteAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Model\Accessory\HistoryBehavior;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\EntityInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Application;

/**
 * Class AbstractDeleteAction
 * @package Action
 */
abstract class AbstractDeleteAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

	/**
	 * @var \Symfony\Component\Form\Form
	 */
	protected $_form;

	/**
	 * @var \Moro\Platform\Model\EntityInterface[]
	 */
	protected $_entities;

	/**
	 * @var array  Список полей, изменения которых должны игнорироваться в истории изменений.
	 */
	protected $_diffBlackKeys;

	/**
	 * @param Application|SilexApplication $app
	 * @param Request $request
	 * @param string $ids
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $ids)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->template));
		assert(!empty($this->route));
		assert(!empty($this->routeIndex));

		$this->setApplication($app);
		$this->setRequest($request);
		$service = $this->getService();

		$list = array_filter(array_map('str_to_int', explode(',', $ids)));
		$this->_setEntities(array_filter(array_map(function($id) use ($service) {
			return $service->getEntityById($id, true, EntityInterface::FLAG_GET_FOR_UPDATE);
		}, $list)));

		if (!$request->query->has('back'))
		{
			return $app->redirect($app->url($this->route, [
				'ids'  => $request->attributes->get('ids'),
				'back' => ($request->headers->has('Referer') && $request->headers->get('Referer'))
					? $request->headers->get('Referer')
					: 0,
			]));
		}

		if (!($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT')))
		{
			$app->getServiceFlash()->error('У вас недостаточно прав для удаления записей.');

			return $app->redirect($request->query->get('back') ?: $app->url($this->routeIndex));
		}

		if (($form = $this->getForm()) && $form->handleRequest($request)->isValid())
		{
			$service->attach($app->getBehaviorHistory());
			/** @var HistoryBehavior $behavior */
			$behavior = $service;
			$behavior->setBlackFields((array)$this->_diffBlackKeys);

			if ( ($result = $this->_doActions()) && $result instanceof Response)
			{
				return $result;
			}

			return $app->redirect($request->query->get('back') ?: $app->url($this->routeIndex));
		}

		return $app->render($this->template, $this->_getViewParameters());
	}

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		return [
			'form' => $this->getForm()->createView(),
			'list' => $this->getEntities(),
		];
	}

	/**
	 * @param array|\Moro\Platform\Model\EntityInterface[] $entities
	 * @return $this
	 */
	protected function _setEntities(array $entities)
	{
		$this->_entities = $entities;
		return $this;
	}

	/**
	 * @return \Moro\Platform\Model\EntityInterface[]
	 */
	public function getEntities()
	{
		return $this->_entities;
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	public function getForm()
	{
		return $this->_form ?: $this->_form = $this->_createForm();
	}

	/**
	 * @return mixed
	 */
	protected function _doActions()
	{
		/** @noinspection PhpUndefinedMethodInspection */
		if ($this->getForm()->get('delete')->isClicked())
		{
			return $this->_doDelete();
		}

		return null;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doDelete()
	{
		$app = $this->getApplication();
		$acl = $app->getServiceSecurityAcl();
		$listDrop = [];
		$listMove = [];

		foreach ($this->getEntities() as $entity)
		{
			if ($acl->isGranted('ACTION_ERASE_ENTITY', $entity))
			{
				$listDrop[] = $entity;
			}
			else
			{
				$listMove[] = $entity;
			}
		}

		$this->_setEntities($listMove);
		$result = $this->_doMoveToTrash();

		$this->_setEntities($listDrop);
		$result = $this->_doDeleteFromDB() ?: $result;

		return $result;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doDeleteFromDB()
	{
		$application = $this->getApplication();
		$service = $this->getService();
		$list = $this->getEntities();

		if (count($list) == 1)
		{
			if ($service->deleteEntityById($list[0]->getId(), null, $application))
			{
				$application->getServiceFlash()->success('Запись была успешно удалена.');
				return $application->redirect(
					$this->getRequest()->query->get('next') ?: $this->getRequest()->query->get('back') ?: $application->url($this->routeIndex)
				);
			}
			else
			{
				$application->getServiceFlash()->error('Не удалось удалить запись.');
				return $application->redirect(
					$this->getRequest()->query->get('back') ?: $application->url($this->routeIndex)
				);
			}
		}
		elseif (count($list))
		{
			$count = 0;

			foreach ($list as $entity)
			{
				$count += (int)$service->deleteEntityById($entity->getId(), null, $application);
			}

			$application->getServiceFlash()->success('Количество удаленных записей: '.$count);
			return $application->redirect(
				$this->getRequest()->query->get('next') ?: $this->getRequest()->query->get('back') ?: $application->url($this->routeIndex)
			);
		}

		return null;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doMoveToTrash()
	{
		$application = $this->getApplication();
		$service = $this->getService();
		$list = $this->getEntities();

		if (count($list) == 1 && ($entity = reset($list)) && $entity instanceof TagsEntityInterface)
		{
			$entity->addTags(['флаг: удалено']);
			$entity instanceof EntityInterface && $service->commit($entity);
			$application->getServiceFlash()->success('Запись была успешно отправлена в корзину.');
		}
		else
		{
			$count = 0;

			foreach ($list as $entity)
			{
				if ($entity instanceof TagsEntityInterface)
				{
					$entity->addTags(['флаг: удалено']);
					$entity instanceof EntityInterface && $service->commit($entity);
					$count++;
				}
			}

			if ($count)
			{
				$application->getServiceFlash()->success('Количество записей, отправленных в корзину: '.$count);
			}
		}

		return $application->redirect(
			$this->getRequest()->query->get('next') ?: $this->getRequest()->query->get('back') ?: $application->url($this->routeIndex)
		);
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	protected function _createForm()
	{
		$factory = $this->getApplication()->getServiceFormFactory();
		$form = $factory->create();

		$form->add('delete', 'submit', [
			'label' => 'Да, удалить',
		]);
		$form->add('cancel', 'submit', [
			'label' => 'Нет, отменить',
		]);

		return $form;
	}
}