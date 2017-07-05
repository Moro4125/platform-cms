<?php
/**
 * Class AbstractSetTagAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Application;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;

/**
 * Class AbstractSetTagAction
 * @package Action
 */
class AbstractSetTagAction extends AbstractContentAction
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

		if (($form = $this->getForm()) && $form->handleRequest($request)->isValid())
		{
			if ( ($result = $this->_doActions()) && $result instanceof Response)
			{
				return $result;
			}

			$fragment = '#selected='.implode(',', $list);
			return $app->redirect(($request->query->get('back') ?: $app->url($this->routeIndex)).$fragment);
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
	 * @param array|EntityInterface[] $entities
	 * @return $this
	 */
	protected function _setEntities(array $entities)
	{
		$this->_entities = $entities;
		return $this;
	}

	/**
	 * @return EntityInterface[]
	 */
	public function getEntities()
	{
		return $this->_entities;
	}

	/**
	 * @return \Symfony\Component\Form\FormInterface
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
		$form = $this->getForm();

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->get('commit')->isClicked())
		{
			$data = $form->getData();
			$add = $data['tags_add'] ?: [];
			$del = $data['tags_del'] ?: [];

			if ($add || $del)
			{
				$this->_doActionCommit($add, $del);
			}
			else
			{
				$this->getApplication()->getServiceFlash()->info('Никаких изменений в записи не было внесено.');
			}
		}

		return null;
	}

	/**
	 * @param array $add
	 * @param array $del
	 * @throws \Exception
	 */
	protected function _doActionCommit($add, $del)
	{
		$service = $this->getService();
		$add = array_diff($add, $del);

		/** @var TagsEntityInterface|EntityInterface $entity */
		foreach ($this->getEntities() as $entity)
		{
			$add && $entity->addTags($add);
			$del && $entity->delTags($del);
			$service->commit($entity);
		}

		$add && $this->getApplication()->getServiceFlash()->success(
			(count($add) == 1)
				? 'Был добавлен ярлык '.reset($add)
				: 'Были назначенны следующие ярлыки: '.implode(', ', $add)
		);
		$del && $this->getApplication()->getServiceFlash()->success(
			(count($del) == 1)
				? 'Был снят ярлык '.reset($del)
				: 'Были убраны следующие ярлыки: '.implode(', ', $del)
		);
	}

	/**
	 * @return \Symfony\Component\Form\FormInterface
	 */
	protected function _createForm()
	{
		$factory = $this->getApplication()->getServiceFormFactory();
		$form = $factory->create();
		$request = $this->getRequest();

		$tags_add = @$request->get('form')['tags_add'] ?: [];
		$tags_add = array_unique(array_merge($tags_add, isset($args['tags_add']) ? $args['tags_add'] : []));

		$tags_del = @$request->get('form')['tags_del'] ?: [];
		$tags_del = array_unique(array_merge($tags_del, isset($args['tags_del']) ? $args['tags_del'] : []));

		$form->add('tags_add', TagsChoiceType::class, [
			'label' => 'Назначить ярлыки',
			'filter' => 'Service: '.$this->serviceCode,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($tags_add, $tags_add),
		]);

		$form->add('tags_del', TagsChoiceType::class, [
			'label' => 'Снять ярлыки',
			'filter' => 'Service: '.$this->serviceCode,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($tags_del, $tags_del),
		]);

		$form->add('commit', SubmitType::class, [
			'label' => 'Применить',
		]);
		$form->add('cancel', SubmitType::class, [
			'label' => 'Отмена',
		]);

		return $form;
	}
}