<?php
/**
 * Class AbstractIndexAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Model\EntityInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \DateTime;


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
	public $pageSize = 30;

	/**
	 * @var bool  Флаг использования поиска по числовому идентификатору.
	 */
	public $useId = true;

	/**
	 * @var bool  Флаг использования поиска по началу названия.
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
	 * @var bool  Флаг использования поиска по адресу эл.почты.
	 */
	public $useEmail = false;

	/**
	 * @var string  Заголовок окна.
	 */
	public $title = '';

	/**
	 * @var array  Базовые условия фильтрации данных.
	 */
	public $where = [];

	/**
	 * @var \Symfony\Component\Form\Form
	 */
	protected $_form;

	/**
	 * @var string
	 */
	protected $_tags;

	/**
	 * @var null|array
	 */
	protected $_cached;

	/**
	 * @var array
	 */
	protected $_headers = [
		'Content-Type' => 'text/html; charset=utf-8',
	];

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		$this->setApplication($app);
		$this->setRequest($request);

		assert(!empty($this->serviceCode));
		assert(!empty($this->template));
		assert(!empty($this->route));
		assert(!empty($this->routeUpdate));
		assert($this->getService() instanceof ContentActionsInterface);

		if ($request->query->has('search') && is_array($search = $request->query->get('search')))
		{
			return $app->redirect(Request::create($request->getUri(), 'GET', [
				'search' => implode(', ', $search),
			])->getUri());
		}

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

		return $app->render($this->template, $this->_getViewParameters(), new Response('', 200, $this->_headers));
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

		$createdBy = $this->getApplication()->getServiceSecurityAcl()->isGranted('ROLE_RS_ALIEN_RECORDS')
			? null
			: $this->getApplication()->getServiceSecurityToken()->getUsername();

		$title = $search ?( $search.' / ' ):( $searchTags ? implode(', ', array_keys($searchTags)).' / ' : '' );
		$user = $this->getApplication()->getServiceSecurityToken()->getUsername();
		$list = $this->getService()->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		return [
			'route' => $this->route,
			'user' => $user,
			'form' => $this->getForm()->createView(),
			'list' => $list,
			'page' => $page,
			'pages' => ceil(($total ?: 1) / $count),
			'offset' => $offset,
			'count'  => $count,
			'total'  => $total,
			'title'  => $title.$this->title.' :: '.$this->getRequest()->getHost(),
			'published' => time(),
			'search'         => $search,
			'searchTags'     => $searchTags,
			'searchTagsMeta' => $searchTagsMeta,
			'tags' => $service instanceof TagsServiceInterface
				? $service->selectActiveTags($this->_tags, true, $createdBy)
				: [],
			'next' => ($offset + $count < $total) ? $next->getRequestUri() : false,
			'prev' => ($page > 1) ? $prev->getRequestUri() : false,
			'rfc822' => DateTime::RFC822,
			'routeUpdate' => $this->routeUpdate,
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

			$this->_tags = null;

			if ($this->useName && empty($useName) && ($useName = true) && substr($search, -1) != '.')
			{
				$_where = array_merge($where, ['~name']);
				$_value = array_merge($value, [$search]);
				$_order = 'name';
				continue;
			}

			if ($this->useCode && empty($useCode) && ($useCode = true) && !strpos($search, ','))
			{
				$localSearch = ($dots = strpos($search, '…')) ? substr($search, 0, $dots) : $search;

				$_where = array_merge($where, ['~code']);
				$_value = array_merge($value, [ltrim(strtr($localSearch, ['.html' => '']), '/')]);
				$_order = 'code';
				continue;
			}

			if ($this->useEmail && empty($useEmail) && $useEmail = true)
			{
				$_where = array_merge($where, ['~email']);
				$_value = array_merge($value, [explode('@', $search)[0]]);
				$_order = 'email';
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

			if ($this->useName && empty($useNames) && ($useNames = true))
			{
				$_where = $where;
				$_value = $value;

				foreach (array_map('trim', explode(',', trim($search, '.'))) as $chunk)
				{
					$chunk = mb_strtolower(mb_substr($chunk, 0, 1, 'UTF-8')).mb_substr($chunk, 1, null, 'UTF-8');
					$_where = array_merge($_where, ['~|name']);
					$_value = array_merge($_value, [$chunk]);

					$chunk = mb_strtoupper(mb_substr($chunk, 0, 1, 'UTF-8')).mb_substr($chunk, 1, null, 'UTF-8');
					$_where = array_merge($_where, ['~|name']);
					$_value = array_merge($_value, [$chunk]);
				}

				$_order = 'name';
				continue;
			}

			if ($this->useCode && empty($useCodes) && $useCodes = true)
			{
				$_where = $where;
				$_value = $value;

				foreach (array_map('trim', explode(',', trim($search, '.'))) as $chunk)
				{
					($dots = strpos($chunk, '…')) && $chunk = substr($chunk, 0, $dots);

					$chunk = ltrim(strtr($chunk, ['.html' => '']), '/');
					$_where = array_merge($_where, ['~|code']);
					$_value = array_merge($_value, [$chunk]);
				}

				$_order = 'code';
				continue;
			}

			if ($this->useEmail && empty($useEmails) && $useEmails = true)
			{
				$_where = $where;
				$_value = $value;

				foreach (array_map('trim', explode(',', trim($search, '.'))) as $chunk)
				{
					$chunk = explode('@', $chunk)[0];
					$_where = array_merge($_where, ['~|email']);
					$_value = array_merge($_value, [$chunk]);
				}

				$_order = 'email';
				continue;
			}

			return $this->_cached = [0, $count, 'id', 'id', 0, 0];
		}

		$totalCount = $service->getCount($where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);
		return $this->_cached = [$start, $count, $order, $where, $value, $totalCount];
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
			return $app->redirect($app->url($this->routeCreate, ['tags' => $this->_tags]));
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