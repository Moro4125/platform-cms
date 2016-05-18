<?php
/**
 * Class AbstractService
 */
namespace Moro\Platform\Model;
use \Doctrine\DBAL\Connection;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Moro\Platform\Model\Exception\CommitFailedException;
use \Moro\Platform\Model\Exception\ReadOnlyEntityException;
use \ArrayObject;
use \ReflectionObject;
use \SplObserver;
use \SplSubject;
use \BadMethodCallException;
use \Exception;
use \PDO;

/**
 * Class AbstractService
 * @package Model
 */
abstract class AbstractService implements SplSubject
{
	const STATE_STOP_NOTIFY     =  0;
	const STATE_ATTACH_BEHAVIOR =  1;
	const STATE_DETACH_BEHAVIOR =  2;
	const STATE_BEFORE_SELECT   =  3;
	const STATE_SELECT_ENTITIES =  4;
	const STATE_PREPARE_ENTITY  =  5;
	const STATE_ENTITY_LOADED   =  6;
	const STATE_COMMIT_STARTED  =  7;
	const STATE_PREPARE_COMMIT  =  8;
	const STATE_COMMIT_FINISHED =  9;
	const STATE_COMMIT_SUCCESS  = 10;
	const STATE_COMMIT_FAILED   = 11;
	const STATE_DELETE_STARTED  = 12;
	const STATE_DELETE_FINISHED = 13;
	const STATE_DELETE_SUCCESS  = 14;
	const STATE_DELETE_FAILED   = 15;
	const STATE_BEHAVIOR_METHOD = 16;

	/**
	 * @var Connection
	 */
	protected $_connection;

	/**
	 * @var string
	 */
	protected $_serviceCode;

	/**
	 * @var string
	 */
	protected $_table;

	/**
	 * @var EntityInterface
	 */
	protected $_entity;

	/**
	 * @var AbstractDecorator
	 */
	protected $_decorator;

	/**
	 * @var array
	 */
	protected $_traits;

	/**
	 * @var int
	 */
	protected $_state;

	/**
	 * @var bool
	 */
	protected $_handled;

	/**
	 * @var SplObserver[]
	 */
	protected $_observers;

	/**
	 * @var EntityInterface[]
	 */
	protected $_cachedItemsList;

	/**
	 * @var string
	 */
	protected $_cacheDependency;

	/**
	 * @var array
	 */
	protected $_specials = [
		EntityInterface::PROP_ID,
		EntityInterface::PROP_CREATED_AT,
		EntityInterface::PROP_UPDATED_AT,
	];

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->_connection = $connection;
		$this->_observers = [];
		$this->_serviceCode = static::class;
		$this->_traits = [];

		$this->_initialization();
	}

	/**
	 * @return void
	 */
	public function __clone()
	{
		$this->_table = null;
		$this->_serviceCode = null;
	}

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		$this->_traits[self::class][self::STATE_PREPARE_COMMIT] = '_prepareSpecialsUpdatedAt';
		$this->_traits[self::class][self::STATE_PREPARE_ENTITY] = '_prepareEntityUpdatedAt';

		foreach ((new ReflectionObject($this))->getMethods() as $method)
		{
			if (strncmp('___initTrait', $name = $method->getName(), 12) === 0)
			{
				$this->_traits[strtolower(substr($name, 12))] = call_user_func([$this, $name]);
			}
		}

		$this->_specials = array_fill_keys($this->_specials, null);
	}

	/**
	 * @return void
	 */
	public function __destruct()
	{
		foreach ($this->_observers as $observer)
		{
			$this->detach($observer);
		}
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$result = $this->notify(self::STATE_BEHAVIOR_METHOD, $method, $args);

		if (!$this->getIsHandled())
		{
			throw new BadMethodCallException(static::class.'::'.$method);
		}

		return $result;
	}

	/**
	 * @param SplObserver $observer
	 */
	public function attach(SplObserver $observer)
	{
		$this->_observers[] = $observer;
		$this->notify(self::STATE_ATTACH_BEHAVIOR, $observer);
	}

	/**
	 * @param SplObserver $observer
	 */
	public function detach(SplObserver $observer)
	{
		if (false !== $index = array_search($observer, $this->_observers, true))
		{
			$this->notify(self::STATE_DETACH_BEHAVIOR, $observer);
			unset($this->_observers[$index]);
		}
	}

	/**
	 * @param null|integer $state
	 * @return mixed
	 */
	public function notify($state = null)
	{
		$return = null;

		try
		{
			$args = func_get_args();
			$value = $this->_state;
			$this->_state = array_shift($args) ?: $value;

			foreach ($this->_traits as $trait)
			{
				if ($this->_state == self::STATE_STOP_NOTIFY)
				{
					break;
				}

				if (isset($trait[$state]) && null !== $result = call_user_func_array([$this, $trait[$state]], $args))
				{
					$return = (is_array($result) && is_array($return)) ? array_merge($return, $result) : $result;
				}
			}

			foreach ($this->_observers as $observer)
			{
				if ($this->_state == self::STATE_STOP_NOTIFY)
				{
					break;
				}

				/** @noinspection PhpVoidFunctionResultUsedInspection */
				if (null !== $result = call_user_func([$observer, 'update'], $this, $args))
				{
					$return = (is_array($result) && is_array($return)) ? array_merge($return, $result) : $result;
				}
			}
		}
		finally
		{
			$this->_handled = ($this->_state == self::STATE_STOP_NOTIFY);
			$this->_state = $value;
		}

		return $return;
	}

	/**
	 * @return $this
	 */
	public function stopNotify()
	{
		$this->_state = self::STATE_STOP_NOTIFY;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getIsHandled()
	{
		return (bool)$this->_handled;
	}

	/**
	 * @return int
	 */
	public function getState()
	{
		return (int)$this->_state;
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setServiceCode($code)
	{
		assert(is_string($code));

		$observers = $this->_observers;

		if ($this->_serviceCode)
		{
			foreach ($observers as $observer)
			{
				$this->detach($observer);
			}
		}

		$this->_serviceCode = $code;
		$this->_observers = [];

		foreach ($observers as $observer)
		{
			$this->attach($observer);
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getServiceCode()
	{
		return $this->_serviceCode;
	}

	/**
	 * @param EntityInterface $entity
	 * @return $this
	 */
	public function setEntity(EntityInterface $entity)
	{
		$this->_entity = $entity;
		return $this;
	}

	/**
	 * @return EntityInterface
	 */
	protected function _getEntity()
	{
		if (empty($this->_entity))
		{
			$class = array_slice(explode('\\', static::class), 0, -1);
			$class = implode('\\', array_merge($class, ['Entity'.$class[count($class) - 1]]));
			$this->setEntity(new $class());
		}

		return $this->_entity;
	}

	/**
	 * @param AbstractDecorator $decorator
	 * @return $this
	 */
	public function appendDecorator(AbstractDecorator $decorator)
	{
		$this->_decorator = $this->_decorator
			? $decorator->decorate($this->_decorator)
			: $decorator;

		return $this;
	}

	/**
	 * @return AbstractDecorator|null
	 */
	public function getDecorator()
	{
		return $this->_decorator;
	}

	/**
	 * @return $this
	 */
	public function unsetDecorator()
	{
		$this->_decorator = null;
		return $this;
	}

	/**
	 * @param AbstractDecorator $decorator
	 * @param callable $callback
	 * @return mixed
	 */
	public function with(AbstractDecorator $decorator, callable $callback)
	{
		$value = $this->getDecorator();

		try
		{
			$value && $this->unsetDecorator();
			$this->appendDecorator($decorator);
			$result = $callback($this, $value ? clone $value : null);
		}
		finally
		{
			$this->unsetDecorator();
			$value && $this->appendDecorator($value);
		}

		return $result;
	}

	/**
	 * @param array $record
	 */
	protected function _prepareEntityUpdatedAt($record)
	{
		if (isset($record[EntityInterface::PROP_CREATED_AT]))
		{
			if (!is_numeric($record[EntityInterface::PROP_CREATED_AT]))
			{
				$record['_flags'] |= EntityInterface::FLAG_TIMESTAMP_CONVERTED;
			}
		}
		elseif (isset($record[EntityInterface::PROP_CREATED_AT]))
		{
			if (!is_numeric($record[EntityInterface::PROP_UPDATED_AT]))
			{
				$record['_flags'] |= EntityInterface::FLAG_TIMESTAMP_CONVERTED;
			}
		}
	}

	/**
	 * @param array $record
	 * @param int $flags
	 * @return mixed
	 */
	protected function _newEntityFromArray(array $record, $flags)
	{
		$record = new ArrayObject($record);
		$record['_flags'] = EntityInterface::FLAG_DATABASE | $flags;

		$this->notify(self::STATE_PREPARE_ENTITY, $record);

		$flags = $record['_flags'];
		unset($record['_flags']);

		$entity = clone $this->_getEntity();
		$entity->setFlags($flags | $entity->getFlags());
		$entity->setProperties($record);
		$entity->setFlags($entity->getFlags() & ~EntityInterface::FLAG_DATABASE);

		$this->notify(self::STATE_ENTITY_LOADED, $entity);

		$entity = $this->_decorator ? $this->_applyDecorator($entity) : $entity;

		return $entity;
	}

	/**
	 * @param EntityInterface $entity
	 * @return AbstractDecorator|EntityTrait
	 */
	protected function _applyDecorator(EntityInterface $entity)
	{
		$decorator = $this->_decorator ? clone $this->_decorator : null;
		return $decorator ? $decorator->decorate($entity) : $entity;
	}

	/**
	 * @param EntityInterface $entity
	 * @param bool $insert
	 * @return array
	 */
	protected function _prepareSpecialsUpdatedAt(EntityInterface $entity, $insert)
	{
		$fields = [];

		if ($insert || !$entity->hasFlag(EntityInterface::FLAG_SYSTEM_CHANGES))
		{
			if ($insert && $entity->hasProperty(EntityInterface::PROP_CREATED_AT))
			{
				$nowExpression = $this->_connection->getDriver()->getDatabasePlatform()->getNowExpression();
				$fields[EntityInterface::PROP_CREATED_AT] = $nowExpression;
			}

			if ($entity->hasProperty(EntityInterface::PROP_UPDATED_AT))
			{
				$nowExpression = empty($nowExpression)
					? $this->_connection->getDriver()->getDatabasePlatform()->getNowExpression()
					: $nowExpression;
				$fields[EntityInterface::PROP_UPDATED_AT] = $nowExpression;
			}
		}

		return $fields;
	}

	/**
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $orderBy
	 * @param null|string $filter
	 * @param null|mixed $value
	 * @param null|int $flags
	 * @return EntityInterface[]
	 */
	public function selectEntities($offset = null, $count = null, $orderBy = null, $filter = null, $value = null, $flags = null)
	{
		if (null === $this->_cachedItemsList || $this->_cacheDependency !== $key = serialize(func_get_args()))
		{
			if (is_array($filter) && $value === null)
			{
				$value = array_values($filter);
				$filter = array_keys($filter);
			}

			$this->_cacheDependency = isset($key) ? $key : serialize(func_get_args());
			$builder = $this->_connection->createQueryBuilder()->select('m.*')->from($this->_table, 'm');
			$values = [];
			$args = new ArrayObject([
				'offset'  => $offset,
				'count'   => $count,
				'orderBy' => $orderBy,
				'filter'  => $filter,
				'value'   => $value,
				'flags'   => $flags,
			]);

			$this->notify(self::STATE_BEFORE_SELECT, $args);
			list($offset, $count, $orderBy, $filter, $value, $flags) = [
				$args['offset'],
				$args['count'],
				$args['orderBy'],
				$args['filter'],
				$args['value'],
				$args['flags'],
			];

			$offset !== null && $builder->setFirstResult((int)$offset);
			(intval($count) || $offset !== null) && $builder->setMaxResults(intval($count) ?: 1024);

			$isArray = is_array($filter) && is_array($value);

			foreach ((array)$orderBy as $field)
			{
				$builder->addOrderBy(ltrim($field, '!'), ($field[0] == '!') ? 'DESC' : 'ASC');
			}

			foreach ((array)$filter as $index => $field)
			{
				$place = ':w'.$index;
				$temporary = $isArray ? array_shift($value) : $value;

				if (null === $result = $this->notify(self::STATE_SELECT_ENTITIES, $builder, $field, $temporary, $place))
				{
					if ($field[0] == '|' || $field[1] == '|')
					{
						$builder->orWhere(ltrim($field, '~!|').($field[0] == '~' ? ' like ' :( $field[0] == '!' ? '<>' : ' = ')).$place);
						$values[$place] = (is_string($temporary) && $field[0] == '~') ? $temporary . '%' : $temporary;
					}
					else
					{
						$builder->andWhere(ltrim($field, '~!|').($field[0] == '~' ? ' like ' :( $field[0] == '!' ? '<>' : ' = ')).$place);
						$values[$place] = (is_string($temporary) && $field[0] == '~') ? $temporary . '%' : $temporary;
					}
				}
				elseif (is_array($result))
				{
					$values = array_merge($values, $result);
				}
				elseif ($result !== false)
				{
					$values[$place] = $result;
				}
			}

			$statement = $this->_connection->prepare($builder->getSQL());

			$this->_cachedItemsList = array_map(function($record) use ($flags) {
				return $this->_newEntityFromArray($record, (int)$flags);
			}, $statement->execute($values ?: null) ? $statement->fetchAll(PDO::FETCH_ASSOC) : []);

			$this->_cachedItemsList = array_combine(array_map(function(EntityInterface $entity) {
				return 'id'.$entity->getId();
			}, $this->_cachedItemsList), $this->_cachedItemsList);
		}

		return $this->_cachedItemsList;
	}

	/**
	 * @param integer $id
	 * @param null|bool $withoutException
	 * @param null|integer $flags
	 * @return EntityInterface|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws EntityNotFoundException
	 */
	public function getEntityById($id, $withoutException = null, $flags = null)
	{
		assert(is_int($id) || (string)$id === (string)(int)$id);

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(EntityInterface::PROP_ID.'=?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ (int)$id ]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			return $this->_newEntityFromArray($record, (int)$flags);
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'ID', $id);
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_ID);
		}

		return null;
	}

	/**
	 * @param array $idList
	 * @param null|int $flags
	 * @return EntityInterface[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getEntitiesById(array $idList, $flags = null)
	{
		$results = [];

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(EntityInterface::PROP_ID.'=?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		foreach ($idList as $id)
		{
			if ($statement->execute([ (int)$id ]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
			{
				$results[(int)$id] = $this->_newEntityFromArray($record, (int)$flags);
			}
		}

		return $results;
	}

	/**
	 * @param EntityInterface $entity
	 * @throws Exception
	 */
	public function commit(EntityInterface $entity)
	{
		assert(!empty($this->_table));

		if (!($entity->getFlags() & (EntityInterface::FLAG_GET_FOR_UPDATE | EntityInterface::FLAG_SYSTEM_CHANGES)))
		{
			throw new ReadOnlyEntityException('Entity with ID '.$entity->getId().' is in "read only" state.');
		}

		$this->notify(self::STATE_COMMIT_STARTED, $entity, $this->_table);

		if (!$this->getIsHandled())
		try
		{
			$this->_connection->beginTransaction();
			$transaction = true;

			if ($entity instanceof AbstractDecorator)
			{
				$entity = $entity->decorate(false);
			}

			$params = new ArrayObject();
			$query = $this->_connection->createQueryBuilder();
			$entity->setFlags(EntityInterface::FLAG_DATABASE | $entity->getFlags());

			if ($id = (int)$entity->getId())
			{
				$insert = false;

				$params[':id'] = $id;
				$query->update($this->_table);
				$query->where(EntityInterface::PROP_ID.' = :id');

				foreach ((array)$this->notify(self::STATE_PREPARE_COMMIT, $entity, false, $params) as $name => $value)
				{
					$query->set($name, $value);
				}
			}
			else
			{
				$insert = true;

				$query->insert($this->_table);
				$values = $this->notify(self::STATE_PREPARE_COMMIT, $entity, true, $params);
				empty($values) || $query->values($values);
			}

			foreach (array_diff_key($entity->getProperties(), $this->_specials) as $name => $value)
			{
				$id ? $query->set($name, ":$name") : $query->setValue($name, ":$name");
				$params[":$name"] = $value;
			}

			if (!$this->_connection->prepare($query->getSQL())->execute($params->getArrayCopy()))
			{
				$message = sprintf(CommitFailedException::M_NOT_EXECUTE, basename(get_class($entity)));
				throw new CommitFailedException($message, CommitFailedException::C_DB_ERROR);
			}

			empty($id) && $entity->setId($id = $this->_connection->lastInsertId());

			$entity->setFlags($entity->getFlags() & ~EntityInterface::FLAG_DATABASE);
			$this->notify(self::STATE_COMMIT_FINISHED, $entity, $this->_table, $insert);

			if ($this->_connection->isRollbackOnly())
			{
				$message = sprintf(CommitFailedException::M_ROLLBACK_F, basename(get_class($entity)));
				throw new CommitFailedException($message, CommitFailedException::C_UNKNOWN_ERROR);
			}

			unset($transaction);
			$this->_connection->commit();
		}
		catch (Exception $exception)
		{
			isset($transaction) && $this->_connection->rollBack();

			$entity->setFlags($entity->getFlags() & ~EntityInterface::FLAG_DATABASE);
			$this->notify(self::STATE_COMMIT_FAILED, $entity, $this->_table, $exception);

			throw $exception;
		}

		$this->notify(self::STATE_COMMIT_SUCCESS, $entity, $this->_table);
	}

	/**
	 * @param integer $id
	 * @param null|bool $withoutException
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws EntityNotFoundException
	 */
	public function deleteEntityById($id, $withoutException = null)
	{
		$exception = null;

		while (empty($entity) && $entity = $this->getEntityById($id))
		{
			try
			{
				$this->_connection->beginTransaction();
				$transaction = true;

				if (false === $this->notify(self::STATE_DELETE_STARTED, $entity, $this->_table))
				{
					$withoutException = true;
					$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'ID', $id);
					throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_ID);
				}

				$builder = $this->_connection->createQueryBuilder();
				$sqlQuery = $builder->delete($this->_table)->where(EntityInterface::PROP_ID.'=?')->getSQL();
				$statement = $this->_connection->prepare($sqlQuery);

				if (!$statement->execute([ (int)$id ]) ||!$statement->rowCount())
				{
					$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'ID', $id);
					throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_ID);
				}

				$this->notify(self::STATE_DELETE_FINISHED, $entity, $this->_table, 1);

				if ($this->_connection->isRollbackOnly())
				{
					$message = sprintf(CommitFailedException::M_ROLLBACK_F, basename(get_class($entity)));
					throw new CommitFailedException($message, CommitFailedException::C_UNKNOWN_ERROR);
				}

				unset($transaction);
				$this->_connection->commit();
			}
			catch (Exception $exception)
			{
				isset($transaction) && $this->_connection->rollBack();
				$this->notify(self::STATE_DELETE_FAILED, $entity, $this->_table);
				break;
			}

			$this->notify(self::STATE_DELETE_SUCCESS, $entity, $this->_table);
			return true;
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'ID', $id);
			throw ($exception ?: new EntityNotFoundException($message, EntityNotFoundException::C_BY_ID));
		}

		return false;
	}

	/**
	 * @param array $idList
	 * @return int
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Exception
	 */
	public function deleteEntitiesById(array $idList)
	{
		$result = 0;
		$list = [];

		try
		{
			$this->_connection->beginTransaction();
			$transaction = true;

			$builder = $this->_connection->createQueryBuilder();
			$sqlQuery = $builder->delete($this->_table)->where(EntityInterface::PROP_ID.'=?')->getSQL();
			$statement = $this->_connection->prepare($sqlQuery);

			foreach ($idList as $id)
			{
				unset($entity);

				if (!$entity = $this->getEntityById($id))
				{
					continue;
				}

				if (false !== $this->notify(self::STATE_DELETE_STARTED, $entity, $this->_table))
				{
					$list[] = $entity;

					$count = $statement->execute([ (int)$id ]) ? $statement->rowCount() : 0;
					$this->notify(self::STATE_DELETE_FINISHED, $entity, $this->_table, $count);
					$result += $count;
				}
			}

			if ($this->_connection->isRollbackOnly())
			{
				$message = sprintf(CommitFailedException::M_ROLLBACK_F, implode(', ', $idList));
				throw new CommitFailedException($message, CommitFailedException::C_UNKNOWN_ERROR);
			}

			unset($transaction);
			$this->_connection->commit();
		}
		catch (Exception $exception)
		{
			isset($transaction) && $this->_connection->rollBack();

			foreach ($list as $entity)
			{
				$this->notify(self::STATE_DELETE_FAILED, $entity, $this->_table);
			}

			throw $exception;
		}

		foreach ($list as $entity)
		{
			$this->notify(self::STATE_DELETE_SUCCESS, $entity, $this->_table);
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setTableName($name)
	{
		assert($this->_table === null);
		$this->_table = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return $this->_table;
	}

	/**
	 * @param string $filter
	 * @param string $value
	 * @param null|int $flags
	 * @return int
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getCount($filter = null, $value = null, $flags  = null)
	{
		$builder = $this->_connection->createQueryBuilder()->select('COUNT(m.id)')->from($this->_table, 'm');
		$values = [];
		$args = new ArrayObject([
			'filter'  => $filter,
			'value'   => $value,
			'flags'   => $flags,
		]);

		$this->notify(self::STATE_BEFORE_SELECT, $args);
		list($filter, $value) = [
			$args['filter'],
			$args['value'],
		];

		$isArray = is_array($filter) && is_array($value);

		foreach ((array)$filter as $index => $field)
		{
			$place = ':w'.$index;
			$temporary = $isArray ? array_shift($value) : $value;

			if (null === $result = $this->notify(self::STATE_SELECT_ENTITIES, $builder, $field, $temporary, $place))
			{
				if ($field[0] == '|' || $field[1] == '|')
				{
					$builder->orWhere(ltrim($field, '~!|').($field[0] == '~' ? ' like ' :( $field[0] == '!' ? '<>' : ' = ')).$place);
					$values[$place] = (is_string($temporary) && $field[0] == '~') ? $temporary . '%' : $temporary;
				}
				else
				{
					$builder->andWhere(ltrim($field, '~!|').($field[0] == '~' ? ' like ' :( $field[0] == '!' ? '<>' : ' = ')).$place);
					$values[$place] = (is_string($temporary) && $field[0] == '~') ? $temporary . '%' : $temporary;
				}
			}
			elseif (is_array($result))
			{
				$values = array_merge($values, $result);
			}
			elseif ($result !== false)
			{
				$values[$place] = $result;
			}
		}

		$sql = $builder->getSQL();
		$statement = $this->_connection->prepare($sql);

		if (!$statement->execute($values ?: null))
		{
			return 0;
		}

		return (strpos($sql, 'GROUP BY') || strpos($sql, 'group by'))
			? count($statement->fetchAll(PDO::FETCH_ASSOC))
			: (int)$statement->fetchColumn();
	}

	/**
	 * @param EntityInterface|array $a
	 * @param EntityInterface|array $b
	 * @return array
	 */
	public function calculateDiff($a, $b)
	{
		$result = [];

		$a instanceof AbstractDecorator && $a = $a->decorate(false);
		$b instanceof AbstractDecorator && $b = $b->decorate(false);

		is_array($a) || $a = $a->getProperties();
		is_array($b) || $b = $b->getProperties();

		$handler = function($a, $b, $prefix = '') use (&$result, &$handler) {
			$keys = array_unique(array_merge(array_keys($a), array_keys($b)));

			if (is_numeric(reset($keys)))
			{
				$av = array_diff($a, $b);
				$bv = array_diff($b, $a);

				if (count($av) || count($bv))
				{
					$result[substr($prefix, 0, -1)] = [array_values($av), array_values($bv)];
				}

				return;
			}

			foreach ($keys as $property)
			{
				$av = isset($a[$property]) ? $a[$property] : null;
				$bv = isset($b[$property]) ? $b[$property] : null;

				if ((is_array($av) || $av === null) && (is_array($bv) || $bv === null))
				{
					$handler((array)$av, (array)$bv, $prefix.$property.'.');
				}
				elseif (empty($av) != empty($bv) && gettype($av) != gettype($bv) || $av != $bv)
				{
					$result[$prefix.$property] = [$av, $bv];
				}
			}
		};

		$handler($a, $b);
		return $result;
	}
}