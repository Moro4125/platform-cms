<?php
/**
 * Class ServiceRelink
 */
namespace Moro\Platform\Model\Implementation\Relink;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Form\Form;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Form\Index\AbstractIndexForm;
use \Moro\Platform\Form\RelinkForm;
use \Moro\Platform\Application;
use \Exception;

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
	 * @return RelinkInterface
	 */
	public function createEntity()
	{
		$entity = $this->_newEntityFromArray([]);

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
		return $this->selectEntities($offset, $count, $order, $where, $value);
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		return $this->getCount($where, $value);
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
	 * @return \Symfony\Component\Form\Form
	 */
	public function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list = $this->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		$service = $application->getServiceFormFactory();
		$builder = $service->createBuilder(new AbstractIndexForm($list), array_fill_keys(array_keys($list), false));

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param RelinkInterface|EntityInterface $entity
	 * @param Request $request
	 * @return Form
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

		return $application->getServiceFormFactory()->createBuilder(new RelinkForm($entity->getId(), $tags), $data)->getForm();
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
					$href = $entity->getHREF();

					if ($href && strncmp($href, '/', 1) === 0)
					{
						$href = $prefix.$href;
					}

					$link = $href ? '<a href="'.htmlspecialchars($href).'"' : '<span';
					$link.= ( $class = $entity->getClass() ) ? ' class="'.htmlspecialchars($class).'"' : '';
					$link.= empty($parameters['title']) ? '' : ' title="'.htmlspecialchars($parameters['title']).'"';
					$link.= (!empty($parameters['open_tab']) && $href) ? ' target="_blank"' : '';
					$link.= (!empty($parameters['nofollow']) && $href) ? ' rel="nofollow"' : '';
					$link.= '>%text%'.($href ? '</a>' : '</span>');

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
}