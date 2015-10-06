<?php
/**
 * Class AbstractUpdateAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Application;

/**
 * Class AbstractUpdateAction
 * @package Action
 */
abstract class AbstractUpdateAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

	/**
	 * @var string  Название "пути" к действию по удалению (опционально).
	 */
	public $routeDelete;

	/**
	 * @var array  Базовые условия фильтрации данных.
	 */
	public $where = [];

	/**
	 * @var bool  Флаг использования ярлыков, а значит и проверки выборки по ним.
	 */
	public $useTags = true;

	/**
	 * @var \Symfony\Component\Form\Form
	 */
	protected $_form;

	/**
	 * @var \Moro\Platform\Model\EntityInterface
	 */
	protected $_entity;

	/**
	 * @param Application|SilexApplication $app
	 * @param Request $request
	 * @param integer $id
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $id)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->template));
		assert(!empty($this->route));
		assert(!empty($this->routeIndex));

		$this->setApplication($app);
		$this->setRequest($request);
		$service = $this->getService();

		if (!$request->query->has('back'))
		{
			return $app->redirect($app->url($this->route, [
				'id'   => $id,
				'back' => ($request->headers->has('Referer') && $request->headers->get('Referer'))
					? $request->headers->get('Referer')
					: 0,
			]));
		}

		if (!$entity = $this->getEntity() ?: $service->getEntityById($id, true))
		{
			$app->getServiceFlash()->error("Записи с идентификатором $id не существует.");
			return $app->redirect($request->query->get('back') ?: $app->url($this->routeIndex));
		}

		$this->_setEntity($entity);

		if ($request->isMethod('POST') && $form = $this->getForm())
		{
			$form->handleRequest($request);

			if ($this->_ignoreValidation() || $form->isValid())
			{
				if ( ($result = $this->_doActions($id)) && $result instanceof Response)
				{
					return $result;
				}

				/** @noinspection PhpUndefinedMethodInspection */
				return $app->redirect($form->get('apply')->isClicked()
					? $request->getUri()
					: ($request->query->get('back') ?: $app->url($this->routeIndex)));
			}
			else
			{
				$app->getServiceFlash()->error('Не все поля формы заполнены корректно.');
			}
		}

		return $app->render($this->template, $this->_getViewParameters());
	}

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$service = $this->getService();
		$entity = $this->getEntity();

		if ($this->useTags && $service instanceof TagsServiceInterface && $entity instanceof ParametersInterface)
		{
			$temp = $entity->getParameters();
			$tags = isset($temp['tags']) ? (array)$temp['tags'] : [];
			$where = array_merge($this->where, ['tag' => $tags]);

			/** @var $service \Moro\Platform\Model\AbstractService */
			/** @var $entity \Moro\Platform\Model\EntityInterface */
			if (empty($tags) && $entity->getProperty('name'))
			{
				$text = 'У записи отсутствуют ярлыки. Следовательно, в дальнейшем её сложно будет найти.';
				$this->getApplication()->getServiceFlash()->alert($text);
			}
			elseif ($service->getCount(array_keys($where), array_values($where)) > 10)
			{
				$text = 'Было найденно слишком много записей с аналогичным набором ярлыков. '.
						'Рекомендуется добавить ещё один ярлык, описывающий какую-либо особенность.';
				$this->getApplication()->getServiceFlash()->alert($text);
			}
		}

		return [
			'form' => $this->getForm()->createView(),
			'item' => $entity,
		];
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

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	public function getForm()
	{
		return $this->_form ?: $this->_form = $this->_createForm();
	}

	/**
	 * @return bool
	 */
	protected function _ignoreValidation()
	{
		$form = $this->getForm();

		/** @noinspection PhpUndefinedMethodInspection */
		return $form->get('cancel')->isClicked() || $form->get('delete')->isClicked();
	}

	/**
	 * @param int $id
	 * @return mixed
	 */
	protected function _doActions($id)
	{
		$service = $this->getService();
		$form = $this->getForm();
		$app = $this->getApplication();

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->get('delete')->isClicked())
		{
			if (!$app->isGranted('ROLE_EDITOR'))
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для удаления записи.');
			}
			elseif ($this->routeDelete)
			{
				$application = $this->getApplication();
				$query = $this->getRequest()->query->all();
				unset($query['id']);

				$next = $this->getRequest()->query->get('back') ?: $application->url($this->routeIndex, $query);
				$back = $this->getRequest()->getRequestUri();
				$query = ['ids' => $id, 'next' => $next, 'back' => $back];

				return $application->redirect($application->url($this->routeDelete, $query));
			}
			else
			{
				$service->deleteEntityById($id);
				$this->getApplication()->getServiceFlash()->success('Запись была успешно удалена.');
			}
		}
		/** @noinspection PhpUndefinedMethodInspection */
		elseif (!$form->get('cancel')->isClicked())
		{
			if (!$app->isGranted('ROLE_EDITOR'))
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для изменения записи.');
			}
			else
			{
				$this->_applyForm();
			}
		}

		return null;
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	protected function _createForm()
	{
		$application = $this->getApplication();
		$request = $this->getRequest();
		$entity = $this->getEntity();

		return $this->getService()->createAdminUpdateForm($application, $entity, $request);
	}

	/**
	 * @return void
	 */
	protected function _applyForm()
	{
		$application = $this->getApplication();
		$entity = $this->getEntity();
		$form = $this->getForm();

		$this->getService()->applyAdminUpdateForm($application, $entity, $form);
	}
}