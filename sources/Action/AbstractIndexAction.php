<?php
/**
 * Class AbstractIndexAction
 */
namespace Moro\Platform\Action;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;


/**
 * Class AbstractIndexAction
 * @package Action
 */
abstract class AbstractIndexAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по созданию новой записи.
	 */
	public $routeCreate;

	/**
	 * @var string  Название "пути" к действию по редактированию.
	 */
	public $routeUpdate;

	/**
	 * @var string  Название "пути" к действию по удалению (опционально).
	 */
	public $routeDelete;

	/**
	 * @var string  Название "пути" к действию по групповому назначению или снятию ярлыков (опционально).
	 */
	public $routeBindTags;

	/**
	 * @var int  Количество записей на странице.
	 */
	public $pageSize = 50;

	/**
	 * @var bool  Флаг использовани поиска по началу названия.
	 */
	public $useName = true;

	/**
	 * @var bool  Флаг использования поиска по значениям ярлыков.
	 */
	public $useTags = true;

	/**
	 * @var bool  Флаг использования поиска по символьному идентификатору.
	 */
	public $useCode = true;

	/**
	 * @var bool  Флаг использования поиска по числовому идентификатору.
	 */
	public $useId = true;

	/**
	 * @var array  Базовые условия фильтрации данных.
	 */
	public $where = [];

	/**
	 * @var \Symfony\Component\Form\Form
	 */
	protected $_form;

	/**
	 * @var array
	 */
	protected $_tags;

	/**
	 * @var null|array
	 */
	protected $_cached;

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->template));
		assert(!empty($this->route));
		assert(!empty($this->routeUpdate));

		$this->setApplication($app);
		$this->setRequest($request);
		$form = $this->getForm();

		if ($form->handleRequest($request)->isValid())
		{
			$list = array_keys(array_filter($form->getData()));
			$list = array_map('substr', $list, count($list) ? array_fill(0, count($list), 2) : []);
			$list = array_filter(array_map('str_to_int', $list));

			return $this->_doAction($list) ?: $app->redirect($request->getUri());
		}

		if ($request->query->has('ids'))
		{
			$idList = $request->query->get('ids');
			$query1 = ['id' => array_shift($idList)];
			$query2 = array_filter(array_merge($request->query->all(), ['ids' => $idList ?: null]));
			$query1['back'] = $app->url($this->route, $query2);

			return $app->redirect($app->url($this->routeUpdate, $query1));
		}

		return $app->render($this->template, $this->_getViewParameters());
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	public function getForm()
	{
		return $this->_form ?: $this->_form = $this->_createForm();
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	protected function _createForm()
	{
		list($offset, $count, $order, $where, $value) = $this->_prepareArgumentsForList();
		return $this->getService()->createAdminListForm($this->getApplication(), $offset, $count, $order, $where, $value);
	}

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$service = $this->getService();
		list($offset, $count, $order, $where, $value, $total) = $this->_prepareArgumentsForList();

		$page = max(1, (int)$this->getRequest()->query->get('page', 1));
		$next = Request::create($this->getRequest()->getRequestUri(), 'GET', ['page' => $page + 1], [], [], $_SERVER);
		$prev = Request::create($this->getRequest()->getRequestUri(), 'GET', ['page' => $page - 1], [], [], $_SERVER);

		$search = trim($this->getRequest()->query->get('search', ''));
		$searchTags = [];
		$searchTagsMeta = [];

		if ($this->_tags)
		{
			$search = '';
			$tagsList = array_map('trim', explode(',', rtrim($this->_tags, '.')));
			$tagsService = $this->_application->getServiceTags();

			foreach ($tagsList as $tag)
			{
				$searchTags[$tag] = Request::create($this->getRequest()->getRequestUri(), 'GET', [
					'page'   => null,
					'search' => ltrim(implode(', ', array_diff($tagsList, [$tag])).'.', '.') ?: null,
				], [], [], $_SERVER)->getUri();

				if ($tagEntity = $tagsService->getEntityByCode(normalizeTag($tag), true))
				{
					$searchTagsMeta[$tag] = $tagEntity;
				}
			}
		}

		return [
			'route' => $this->route,
			'form' => $this->getForm()->createView(),
			'list' => $this->getService()->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value),
			'page' => $page,
			'pages' => ceil(($total ?: 1) / $count),
			'offset' => $offset,
			'count'  => $count,
			'total'  => $total,
			'search'         => $search,
			'searchTags'     => $searchTags,
			'searchTagsMeta' => $searchTagsMeta,
			'tags' => $service instanceof TagsServiceInterface ? $service->selectActiveTags($this->_tags, true) : [],
			'next' => ($offset + $count < $total) ? $next->getRequestUri() : false,
			'prev' => ($page > 1) ? $prev->getRequestUri() : false,
		];
	}

	/**
	 * @return array
	 */
	protected function _prepareArgumentsForList()
	{
		if ($this->_cached)
		{
			return $this->_cached;
		}

		$service = $this->getService();
		$request = $this->getRequest();

		$search = trim((string)$request->query->get('search'));
		$count = max(10, intval($request->query->get('limit')) ?: $this->pageSize);
		$page = max(1, intval($request->query->get('page')));

		$start = ($page - 1) * $count;
		$order = '!updated_at';
		$where = array_keys($this->where);
		$value = array_values($this->where);

		while ($search)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			if (isset($_where) && $_total = $service->getCountForAdminListForm($_where, $_value))
			{
				/** @noinspection PhpUndefinedVariableInspection */
				return $this->_cached = [$start, $count, $_order, $_where, $_value, $_total];
			}

			($dots = strpos($search, '…')) && $search = substr($search, 0, $dots);
			$this->_tags = null;

			if ($this->useName && empty($useName) && ($useName = true) && substr($search, -1) != '.')
			{
				$_where = array_merge($where, ['~name']);
				$_value = array_merge($value, [$search]);
				$_order = 'name';
				continue;
			}

			if ($this->useCode && empty($useCode) && $useCode = true)
			{
				$_where = array_merge($where, ['~code']);
				$_value = array_merge($value, [ltrim(strtr($search, ['.html' => '']), '/')]);
				$_order = 'code';
				continue;
			}

			if ($this->useTags && empty($useTags) && $useTags = true)
			{
				$this->_tags = $search;

				$_where = array_merge($where, ['tag']);
				$_value = array_merge($value, [$search]);
				$_order = 'name';
				continue;
			}

			if ($this->useId && empty($useId) && $useId = true)
			{
				$_where = array_merge($where, ['id']);
				$_value = array_merge($value, [(int)$search]);
				$_order = 'id';
				continue;
			}

			return $this->_cached = [0, 1, 'id', 'id', 0, 0];
		}

		return $this->_cached = [$start, $count, $order, $where, $value, $service->getCount($where, $value)];
	}

	/**
	 * @param array $list
	 * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doAction($list)
	{
		$app = $this->getApplication();
		$form = $this->getForm();
		$request = $this->getRequest();

		/** @noinspection PhpUndefinedMethodInspection */
		if ($this->routeCreate && $form->get('create')->isClicked())
		{
			return $app->redirect($app->url($this->routeCreate));
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->get('delete')->isClicked())
		{
			if (count($list) == 0)
			{
				$app->getServiceFlash()->alert('Не было выбранно ни одной записи для удаления.');
			}
			elseif ($this->routeDelete)
			{
				return $app->redirect($app->url($this->routeDelete, [
					'ids' => implode(',', $list),
					'back' => $request->getRequestUri(),
					'next' => $request->getRequestUri(),
				]));
			}
			else
			{
				$count = $this->getService()->deleteEntitiesById($list);
				$count && $app->getServiceFlash()->success("Было удалено записей: $count.");
			}
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->get('update')->isClicked())
		{
			if (count($list))
			{
				$query = ['id' => array_shift($list)];
				$list && $query['back'] = $app->url($this->route, $request->query->all() + ['ids' => $list]);
				return $app->redirect($app->url($this->routeUpdate, $query));
			}

			$app->getServiceFlash()->alert('Для редактирования нужно выбрать одну или более записей.');
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($this->routeBindTags && $form->get('bind')->isClicked())
		{
			if (count($list))
			{
				return $app->redirect($app->url($this->routeBindTags, ['ids' => implode(',', $list)]));
			}

			$app->getServiceFlash()->alert('Для назначения ярлыков нужно выбрать одну или более записей.');
		}

		return null;
	}
}