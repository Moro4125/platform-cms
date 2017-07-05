<?php
/**
 * Class ServiceRelink
 */
namespace Moro\Platform\Model\Implementation\Relink;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Form\Form;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Implementation\History\HistoryInterface;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Form\Index\AbstractIndexForm;
use \Moro\Platform\Form\RelinkForm;
use \Moro\Platform\Application;
use \ArrayObject;
use \Exception;
use \PDO;

/**
 * Class ServiceRelink
 * @package Model\Implementation\Relink
 */
class ServiceRelink extends AbstractService implements ContentActionsInterface, TagsServiceInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
	use \Moro\Platform\Model\Accessory\LockTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Star\StarServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'content_relink';

	/**
	 * @var array  Список пар "искомый текст" => "HTML ссылка"
	 */
	protected $_links;

	/**
	 * @var array  Список пар "искомый текст" => числовой идентификатор записи.
	 */
	protected $_idMap;

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
		$this->_traits[static::class][HistoryInterface::STATE_TRY_MERGE_HISTORY] = '_mergeHistory';
	}

	/**
	 * @param array $tags
	 * @param RelinkInterface $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		$name = $entity->getName();

		if (preg_match_all('{(?<=^|[^А-Яа-я])[А-Яа-я]}u', $name, $matches, PREG_SET_ORDER))
		{
			foreach (array_column($matches, 0) as $char)
			{
				$tags[] = normalizeTag('а-я:'.$char);
			}
		}

		if (preg_match_all('{(?<=^|[^A-Za-z])[A-Za-z]}u', $name, $matches, PREG_SET_ORDER))
		{
			foreach (array_column($matches, 0) as $char)
			{
				$tags[] = normalizeTag('a-z:'.$char);
			}
		}

		if (!$href = $entity->getHREF())
		{
			$tags[] = normalizeTag('Ссылка: запрещённая');
		}
		elseif (preg_match('{^(?:/|(?:f|ht)tps?://'.preg_quote($_SERVER['HTTP_HOST'], '}').')}', $href))
		{
			$tags[] = normalizeTag('Ссылка: внутренняя');
		}
		else
		{
			$tags[] = normalizeTag('Ссылка: внешняя');
		}

		$parameters = $entity->getParameters();

		if (empty($parameters['open_tab']))
		{
			$tags[] = normalizeTag('Переход: обычный');
		}
		else
		{
			$tags[] = normalizeTag('Переход: новая вкладка');
		}

		if (empty($parameters['nofollow']))
		{
			$tags[] = normalizeTag('Переход: индексируется');
		}
		else
		{
			$tags[] = normalizeTag('Переход: не для роботов');
		}

		if (!empty($parameters['is_abbr']))
		{
			$tags[] = normalizeTag('Флаг: аббревиатура');
		}

		if (!empty($parameters['use_name']))
		{
			$tags[] = normalizeTag('Флаг: замена фразы');
		}

		if ($classes = $entity->getClass())
		{
			foreach (array_filter(explode(' ', $classes)) as $cssClass)
			{
				$tags[] = normalizeTag('CSS: '.$cssClass);
			}
		}

		return $tags;
	}

	/**
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 */
	protected function _mergeHistory(ArrayObject $next, ArrayObject $prev)
	{
		$list = [
			'parameters.tags',
			'parameters.nominativus',
			'parameters.genitivus',
			'parameters.dativus',
			'parameters.accusativus',
			'parameters.instrumentalis',
			'parameters.praepositionalis',
		];

		foreach ($next->getArrayCopy() as $key => $value)
		{
			if (in_array($key, $list))
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeList($key, $next, $prev);
			}
			elseif (in_array($key, ['name', 'href', 'parameters.title', 'class', 'parameters.open_tab', 'parameters.nofollow', 'parameters.is_abbr', 'parameters.use_name']))
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeSimple($key, $next, $prev);
			}
		}
	}

	/**
	 * @return RelinkInterface
	 */
	public function createEntity()
	{
		$entity = $this->_newEntityFromArray([], EntityInterface::FLAG_GET_FOR_UPDATE);

		$this->commit($entity);
		return $entity;
	}

	/**
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|integer $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Moro\Platform\Model\EntityInterface[]
	 */
	public function selectEntitiesForAdminListForm($offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$where === '~email' && $where = '~href';
		$where === '~|email' && $where = '~|href';
		$order === 'email'  && $order = 'name';

		while (is_array($where) && (false !== $index = array_search('~email', $where, true)))
		{
			$where[$index] = '~href';
		}

		while (is_array($where) && (false !== $index = array_search('~|email', $where, true)))
		{
			$where[$index] = '~|href';
		}


		$list  = $this->selectEntities($offset, $count, $order, $where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);
		$user  = '+star:'.$this->_userToken->getUsername();
		$stars = $this->selectEntities(0, ceil($count / 3), '!updated_at', 'tag', $user, EntityInterface::FLAG_GET_FOR_UPDATE);

		return array_merge($stars, $list);
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		$where === '~email' && $where = '~href';
		$where === '~|email' && $where = '~|href';

		while (is_array($where) && (false !== $index = array_search('~email', $where, true)))
		{
			$where[$index] = '~href';
		}

		while (is_array($where) && (false !== $index = array_search('~|email', $where, true)))
		{
			$where[$index] = '~|href';
		}

		return $this->getCount($where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);
	}

	/**
	 * @return RelinkInterface
	 */
	public function createNewEntityWithId()
	{
		return $this->createEntity();
	}

	/**
	 * @param Application $application
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list = $this->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		$application->extend(AbstractIndexForm::class, function(AbstractIndexForm $form, $app) use ($list) {
			$form->setApplication($app);
			$form->setList($list);
			return $form;
		});

		$service = $application->getServiceFormFactory();
		$dataArr = array_fill_keys(array_keys($list), false);
		$builder = $service->createNamedBuilder('admin_list', AbstractIndexForm::class, $dataArr);

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param RelinkInterface|EntityInterface $entity
	 * @param Request $request
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request)
	{
		$args = $entity->getParameters();
		$tags = @$request->get('admin_update')['tags'] ?: [];
		$tags = array_unique(array_merge($tags, isset($args['tags']) ? $args['tags'] : []));

		$data = [
			'name'  => $entity->getName(),
			'href'  => $entity->getHREF(),
			'class' => $entity->getClass(),
			'tags'  => isset($args['tags']) ? $args['tags'] : [],
		];

		$list = [
			'nominativus'      => 'Кто/Что',
			'genitivus'        => 'Кого/Чего',
			'dativus'          => 'Кому/Чему',
			'accusativus'      => 'Кого/Что',
			'instrumentalis'   => 'Кем/Чем',
			'praepositionalis' => 'О ком/чём',
			'title' => 'Подсказка',
		];

		foreach (array_keys($list) as $key)
		{
			$data[$key] = isset($args[$key]) ?( is_array($args[$key]) ? implode(', ', $args[$key]) : $args[$key] ): '';
		}

		$data['open_tab'] = !empty($args['open_tab']);
		$data['nofollow'] = !empty($args['nofollow']);
		$data['is_abbr']  = !empty($args['is_abbr']);
		$data['use_name'] = !empty($args['use_name']);

		$application->extend(RelinkForm::class, function(RelinkForm $form) use ($entity, $tags) {
			$form->setId($entity->getId());
			$form->setTags($tags);
			return $form;
		});

		$service = $application->getServiceFormFactory();
		$builder = $service->createNamedBuilder('admin_update', RelinkForm::class, $data);

		return $builder->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param RelinkInterface|EntityInterface $entity
	 * @param Form $form
	 */
	public function applyAdminUpdateForm(Application $application, EntityInterface $entity, Form $form)
	{
		$data = $form->getData();
		$parameters = $entity->getParameters();

		$this->_connection->beginTransaction();

		try
		{
			$parameters['tags'] = array_values($data['tags']);
			$parameters['comment'] = $data['comment'];

			$list = [
				'nominativus'      => 'Кто/Что',
				'genitivus'        => 'Кого/Чего',
				'dativus'          => 'Кому/Чему',
				'accusativus'      => 'Кого/Что',
				'instrumentalis'   => 'Кем/Чем',
				'praepositionalis' => 'О ком/чём',
			];

			foreach (array_keys($list) as $key)
			{
				$parameters[$key] = isset($data[$key]) ? array_map('trim', explode(',', $data[$key])) : null;
			}

			$list = [
				'title'    => 'Подсказка',
				'open_tab' => 'Открывать ссылку в новой вкладке или окне',
				'nofollow' => 'Запретить роботам переход по ссылке',
				'is_abbr'  => 'Является аббревиатурой',
				'use_name' => 'Заменить фразу на название',
			];

			foreach (array_keys($list) as $key)
			{
				$parameters[$key] = isset($data[$key]) ? $data[$key] : null;
			}

			$entity->setName($data['name']);
			$entity->setHREF($data['href']);
			$entity->setClass($data['class']);
			$entity->setParameters($parameters);

			$this->commit($entity);
			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи ярлыка были успешно сохранены.');
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
		}

		unset($application);
	}

	/**
	 * @return array
	 */
	public function getLinks()
	{
		if ($this->_links === null)
		{
			$this->_links = [];
			$prefix = explode('index.php', $_SERVER['REQUEST_URI'])[0].'index.php';

			for ($count = $this->getCount(), $offset = 0, $limit = 256; $offset < $count; $offset += $limit)
			{
				/** @var RelinkInterface $entity */
				foreach ($this->selectEntities($offset, $limit) as $entity)
				{
					$id = $entity->getId();
					$parameters = $entity->getParameters();
					$abbr = !empty($parameters['is_abbr']);
					$href = $entity->getHREF();
					$text = empty($parameters['use_name']) ? '%text%' : $entity->getName();

					if ($href && strncmp($href, '/', 1) === 0)
					{
						$href = $prefix.$href;
					}

					$link = $href ? '<a href="'.htmlspecialchars($href).'"' :( $abbr ? '<abbr' : '<span');
					$link.= ( $class = $entity->getClass() ) ? ' class="'.htmlspecialchars($class).'"' : '';
					$link.= empty($parameters['title']) ? '' : ' title="'.htmlspecialchars($parameters['title']).'"';
					$link.= (!empty($parameters['open_tab']) && $href) ? ' target="_blank"' : '';
					$link.= (!empty($parameters['nofollow']) && $href) ? ' rel="nofollow"' : '';
					$link.= ($href && $abbr) ? '><abbr title="'.htmlspecialchars(@$parameters['title']).'"' : '';
					$link.= '>'.$text.($href ?( $abbr ? '</abbr></a>' : '</a>' ):( $abbr ? '</abbr>' : '</span>'));

					foreach (['nominativus','genitivus','dativus','accusativus','instrumentalis','praepositionalis'] as $key)
					{
						if (!empty($parameters[$key]))
						{
							foreach ((array)$parameters[$key] as $words)
							{
								$this->_links[$words] = $link;
								$this->_idMap[$words] = $id;
							}
						}
					}
				}
			}
		}

		return $this->_links;
	}

	/**
	 * @return array
	 */
	public function getIdMap()
	{
		if ($this->_idMap === null)
		{
			$this->getLinks();
		}

		return $this->_idMap;
	}

	/**
	 * @param string $href
	 * @param null|int $flags
	 * @return RelinkInterface[]
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectByHref($href, $flags = null)
	{
		assert(is_string($href));

		$builder = $this->_connection->createQueryBuilder();

		if (substr($href, -1) == '%')
		{
			$sqlQuery = $builder->select('*')->from($this->_table)->where(RelinkInterface::PROP_HREF.' LIKE ?')->getSQL();
		}
		else
		{
			$sqlQuery = $builder->select('*')->from($this->_table)->where(RelinkInterface::PROP_HREF.'=?')->getSQL();
		}

		$statement = $this->_connection->prepare($sqlQuery);
		$result = [];

		if ($statement->execute([ (string)$href ]))
		{
			while ($record = $statement->fetch(PDO::FETCH_ASSOC))
			{
				$result[] = $this->_newEntityFromArray($record, (int)$flags);
			}
		}

		return $result;
	}
}