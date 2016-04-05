<?php
/**
 * Trait TagsServiceTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\AbstractService;
use \PDO;

/**
 * Trait TagsServiceTrait
 * @package Model\Accessory\Parameters\Tags
 *
 * @property string $_table
 * @property \Doctrine\DBAL\Connection $_connection
 */
trait TagsServiceTrait
{
	/**
	 * @var int
	 */
	protected $_tagsAliasNumber = 0;

	/**
	 * @param null|string|array $tags
	 * @param null|bool $useNamespace
	 * @param null|string $createdBy
	 * @return array
	 */
	public function selectActiveTags($tags = null, $useNamespace = null, $createdBy = null)
	{
		assert(!$tags || is_string($tags) || is_array($tags) && count($tags) == count(array_filter($tags, 'is_string')));

		$result = [];
		$table = $this->_table;
		$tags = $tags ?( is_string($tags) ? explode(',', rtrim($tags, '.')) : (array)$tags): [];
		$search = rtrim(implode(', ', array_map('trim', $tags)), '.').($tags ? ', ' : '');
		$recordsCount = 0;
		$hideTop = null;

		if (isset($this->_connection))
		{
			$builder1 = $this->_connection->createQueryBuilder();
			$builder1->select('a.tag, COUNT(a.tag) as cnt')->from($table.'_tags', 'a')->groupBy('tag');
			$builder1->orderBy('cnt', 'desc')->setMaxResults(64);

			$builder1->where('a.tag > "+~"');

			$builder1->leftJoin('a', 'content_tags', 'ct', 'a.tag = ct.code');
			$builder1->addSelect('ct.name, ct.parameters');
			$parameters = null;

			if ($tags || $createdBy)
			{
				$builder2 = $this->_connection->createQueryBuilder();
				$builder2->select('m.id')->from($table, 'm');

				if ($tags)
				{
					$parameters = $this->_tagsSelectEntities($builder2, 'tag', $tags, ':t');
				}

				if ($createdBy)
				{
					$builder2->andWhere(UpdatedByInterface::PROP_CREATED_BY.'= :createdBy');
					$parameters[':createdBy'] = $createdBy;
				}

				$builder1->andWhere('a.target IN ('.$builder2->getSQL().')');
			}

			$statement1 = $this->_connection->prepare($builder1->getSQL());

			foreach ($statement1->execute($parameters) ? $statement1->fetchAll(PDO::FETCH_ASSOC) : [] as $record)
			{
				if ($parameters && in_array($record['tag'], $parameters))
				{
					$hideTop = max((int)$hideTop, (int)$record['cnt']);
				}
				elseif (!empty($record['tag']))
				{
					$temp = isset($record['parameters']) ?( @json_decode($record['parameters'], true) ?: [] ): [];
					$name = empty($record['name']) ? $record['tag'] : $record['name'];
					$recordsCount += (int)$record['cnt'];
					$result[] = [
						'cnt' => (int)$record['cnt'],
						'name' => $name,
						'code' => $record['tag'],
						'lead' => empty($temp['lead']) ? '' : $temp['lead'],
						'href' => '?search='.$search.$name.'.',
					];
				}
			}
		}

		if ($hideTop)
		{
			while (($record = reset($result)) && $record['cnt'] == $hideTop)
			{
				array_shift($result);
			}
		}

		$count = count($result);
		$chunk = $count / 10;
		$lastWeight = 0;
		$lastCount = 0;

		foreach ($result as $index => &$meta)
		{
			if ($lastCount == $meta['cnt'])
			{
				$meta['weight'] = $lastWeight;
			}
			else
			{
				$meta['weight'] = $lastWeight = max($lastWeight - 1, (int)ceil(10 - $index / $chunk));
				$lastCount = $meta['cnt'];
			}
		}

		usort($result, function($a, $b) {
			return strcmp($a['code'], $b['code']);
		});

		if ($useNamespace)
		{
			$nsList = [];

			foreach ($result as $item)
			{
				$ns = ($pos = strpos($item['code'], ':'))
					? substr($item['code'], 0, $pos)
					: '';
				$item['alias'] = trim(substr($item['name'], $ns ? strpos($item['name'], ':') + 1 : 0));

				if (mb_strlen($item['alias']) == 1)
				{
					$item['alias'] = mb_strtoupper($item['alias']);
				}

				$nsList[$ns][] = $item;
			}

			$result = [];

			foreach ($nsList as $ns => $items)
			{
				$ns && $ns = trim(explode(':', reset($items)['name'])[0]);
				$ns && $ns = mb_strtoupper(mb_substr($ns, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($ns, 1, null, 'UTF-8');
				$result[$ns] = $items;
			}

			uksort($result, function($a, $b) {
				return strcmp(mb_strtolower($a), mb_strtolower($b));
			});
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function ___initTraitTags()
	{
		return [
			AbstractService::STATE_SELECT_ENTITIES => '_tagsSelectEntities',
			AbstractService::STATE_COMMIT_FINISHED => '_tagsCommitFinished',
			AbstractService::STATE_DELETE_FINISHED => '_tagsDeleteFinished',
		];
	}

	/**
	 * @param \Doctrine\DBAL\Query\QueryBuilder $builder
	 * @param string $field
	 * @param string $value
	 * @param string $place
	 * @return mixed
	 */
	protected function _tagsSelectEntities($builder, $field, $value, $place)
	{
		if ($field !== 'tag' && $field !== '~tag')
		{
			return null;
		}

		assert(isset($this->_table));
		$table = $this->_table;
		$alias = 'tags'.(++$this->_tagsAliasNumber);

		if (is_string($value) && !strpos($value, ','))
		{
			$suffix = (substr($value = trim($value), -1) != '.' && $field[0] == '~') ? '%' : '';
			$builder->innerJoin('m', $table.'_tags', $alias, "$alias.target = m.id");
			$builder->andWhere($alias.".tag ".($suffix ? "like " : "= ").$place);

			return normalizeTag(rtrim($value, '.')).$suffix;
		}

		$strict = is_array($value) ?: substr($value = trim($value), -1) == '.';

		$value = is_array($value) ? $value : explode(',', rtrim($value, '.'));
		$value = array_map('normalizeTag', $value);
		$value = array_unique(array_filter($value));

		if (!$strict && ($v = end($value)) && !$this->_tagsCount($table, $v) && $this->_tagsNearest($table, $v))
		{
			array_pop($value);
		}

		$places = array_map(function($index) use ($place) { return $place.'i'.$index; }, array_keys($value));

		if ($field[0] === '~')
		{
			$order = $builder->getQueryPart('orderBy') ?: [];
			array_unshift($order, 'count(m.id) DESC');
			$builder->resetQueryPart('orderBy');

			foreach ($order as $temp)
			{
				list($field, $vector) = explode(' ', $temp, 2);
				$builder->addOrderBy($field, $vector);
			}

			$builder->innerJoin('m', $table.'_tags', $alias, "$alias.target = m.id");
			$builder->andWhere("$alias.tag in (".implode(',', $places).")");
			$builder->groupBy('m.id');
		}
		else
		{
			$target = 'm.id';
			$aliasF = 'm';

			foreach ($places as $index => $placeholder)
			{
				$condition = "$alias$index.target = $target AND $alias$index.tag = $placeholder";
				$builder->innerJoin($aliasF, $table.'_tags', $alias.$index, $condition);
				$target = "$alias$index.target";
				$aliasF = "$alias$index";
			}
		}

		return array_combine($places, $value);
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface|string|int $entity
	 * @param string $table
	 */
	protected function _tagsCommitFinished($entity, $table)
	{
		$parameters = $entity->getProperty('parameters');
		$tags = array_map('normalizeTag', empty($parameters['tags']) ? ['флаг: без ярлыков'] : $parameters['tags']);
		$ignore = [''];

		if (isset($this->_connection) && $id = $entity->getId())
		{
			$this->_tagsDeleteFinished($entity, $table);

			if ($this instanceof AbstractService)
			{
				$result = $this->notify(TagsServiceInterface::STATE_TAGS_GENERATE, $tags, clone $entity);
				is_array($result) && $tags = $result;
			}

			foreach ($tags as $tag)
			{
				if ($tag && $tag[0] === '-')
				{
					$ignore[] = $tag;
					$ignore[] = substr($tag, 1);
				}
			}

			/** @var \Doctrine\DBAL\Query\QueryBuilder $builder */
			$builder = $this->_connection->createQueryBuilder();
			$sqlQuery = $builder->insert($table.'_tags')->values(['target' => '?', 'tag' => '?'])->getSQL();
			$statement = $this->_connection->prepare($sqlQuery);

			foreach (array_diff(array_unique($tags), $ignore) as $tag)
			{
				$statement->execute([ $id, $tag ]);
			}
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface|string|int $entity
	 * @param string $table
	 */
	protected function _tagsDeleteFinished($entity, $table)
	{
		if (isset($this->_connection) && $id = $entity->getId())
		{
			/** @var \Doctrine\DBAL\Query\QueryBuilder $builder */
			$builder = $this->_connection->createQueryBuilder();
			$sqlQuery = $builder->delete($table.'_tags')->where('target = ?')->getSQL();
			$statement = $this->_connection->prepare($sqlQuery);
			$statement->execute([ $id ]);
		}
	}

	/**
	 * @param string $table
	 * @param null|string $tag
	 * @return int
	 */
	protected function _tagsCount($table, $tag = null)
	{
		if (isset($this->_connection))
		{
			$builder = $this->_connection->createQueryBuilder();
			$builder->select('count(*)')->from($table.'_tags');
			$tag && $builder->where('tag = ?');
			$statement = $this->_connection->prepare($builder->getSQL());

			return $statement->execute($tag ? [$tag] : null) ? (int)$statement->fetchColumn() : 0;
		}

		return 0;
	}

	/**
	 * @param string $table
	 * @param string $tag
	 * @return integer
	 */
	protected function _tagsNearest($table, $tag)
	{
		if (isset($this->_connection))
		{
			$builder = $this->_connection->createQueryBuilder();
			$sqlQuery = $builder->select('count(*)')->from($table.'_tags')->where('tag like ?')->getSQL();
			$statement = $this->_connection->prepare($sqlQuery);

			return $statement->execute([ $tag.'%' ]) ? (int)$statement->fetchColumn() : 0;
		}

		return 0;
	}
}