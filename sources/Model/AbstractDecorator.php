<?php
/**
 * Class AbstractDecorator
 */
namespace Moro\Platform\Model;


use \Moro\Platform\Application;
use \ArrayAccess;

/**
 * Class AbstractDecorator
 * @package Model
 */
abstract class AbstractDecorator implements ArrayAccess
{
	use EntityTrait {
		EntityTrait::__construct as protected __constructEntityTrait;
	}

	/**
	 * @var Application
	 */
	protected $_application;

	/**
	 * @var EntityInterface
	 */
	protected $_entity;

	/**
	 * @var float
	 */
	protected $_priority;

	/**
	 * @param Application $application
	 * @param null|EntityInterface $entity
	 * @param null|float $priority
	 * @return EntityInterface
	 */
	public static function newInstance(Application $application, EntityInterface $entity = null, $priority = null)
	{
		$decorator = new static($application, null, $priority);
		return $entity ? $decorator->decorate($entity) : $decorator;
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param null|float $priority
	 */
	public function __construct(Application $application, $priority = null)
	{
		$this->_application = $application;
		$this->_priority = (float)$priority;

		$this->__constructEntityTrait();
	}

	public function __clone()
	{
		if ($this->_entity)
		{
			$this->_entity = clone $this->_entity;
		}
	}

	public function __call($method, $args)
	{
		return call_user_func_array([$this->_entity, $method], $args);
	}

	/**
	 * @return float
	 */
	public function getDecoratorPriority()
	{
		return $this->_priority;
	}

	/**
	 * @param float $priority
	 * @return $this
	 */
	public function setDecoratorPriority($priority)
	{
		$this->_priority = (float)$priority;
		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->getProperties();
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasProperty($name)
	{
		assert(is_string($name));
		return array_key_exists($name, self::$_default[static::class]) ?: $this->_entity->hasProperty($name);
	}

	/**
	 * @param array|\Traversable $properties
	 * @return $this
	 */
	public function setProperties($properties)
	{
		foreach ($properties as $name => $value)
		{
			if (isset(self::$_setters[static::class][$name]))
			{
				$this->{self::$_setters[static::class][$name]}($value);
			}
			else
			{
				$this->_entity->setProperty($name, $value);
			}
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setProperty($name, $value)
	{
		if (isset(self::$_setters[static::class][$name]))
		{
			$this->{self::$_setters[static::class][$name]}($value);
		}
		else
		{
			$this->_entity->setProperty($name, $value);
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		$properties = $this->_entity->getProperties();

		foreach (array_keys(self::$_default[static::class]) as $name)
		{
			if (isset(self::$_getters[static::class][$name]))
			{
				$properties[$name] = $this->{self::$_getters[static::class][$name]}();
			}
		}

		return $properties;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name)
	{
		if (isset(self::$_getters[static::class][$name]))
		{
			return $this->{self::$_getters[static::class][$name]}();
		}

		return $this->_entity->getProperty($name);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->_entity->getId();
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->_entity->setId($id);
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFlags()
	{
		return $this->_entity->getFlags();
	}

	/**
	 * @param int $flags
	 * @return $this
	 */
	public function setFlags($flags)
	{
		$this->_entity->setFlags($flags);
		return $this;
	}

	/**
	 * @param int $flag
	 * @return $this
	 */
	public function addFlag($flag)
	{
		$this->_entity->addFlag($flag);
		return $this;
	}

	/**
	 * @param int $flag
	 * @return $this
	 */
	public function delFlag($flag)
	{
		$this->_entity->delFlag($flag);
		return $this;
	}

	/**
	 * @param int $flag
	 * @return bool
	 */
	public function hasFlag($flag)
	{
		return $this->_entity->hasFlag($flag);
	}

	/**
	 * @return int
	 */
	public function getCreatedAt()
	{
		return $this->_entity->getCreatedAt();
	}

	/**
	 * @return int
	 */
	public function getUpdatedAt()
	{
		return $this->_entity->getUpdatedAt();
	}

	/**
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->_entity->hasProperty($offset);
	}

	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->_entity->getProperty($offset);
	}

	/**
	 * @param string $offset
	 * @param mixed $value
	 * @return $this
	 */
	public function offsetSet($offset, $value)
	{
		$this->_entity->setProperty($offset, $value);
		return $this;
	}

	/**
	 * @param string $offset
	 * @return $this
	 */
	public function offsetUnset($offset)
	{
		$this->_entity->setProperty($offset, null);
		return $this;
	}

	/**
	 * Декорирование оригинального объекта.
	 *
	 * Поведение при разных значения передаваемого параметра:
	 * TRUE - возвращает следующий объект-декоратор, если он есть, иначе - NULL;
	 * FALSE - возвращает декорируемый объект, пропуская объекты-декораторы;
	 * NULL - разрывает связь со следующим элементом цепочки, если этим элементом
	 *   был декорируемый объект, то возвращает его, в противном случае - NULL;
	 * Object - добавляет декорируемый объект или объект-декоратор в
	 *   цепочку. Возвращает объект, находящийся на вершине цепочки декораторов.
	 *
	 * @param  EntityInterface|AbstractDecorator|boolean|null $entity
	 * @return AbstractDecorator|null
	 */
	public function decorate($entity)
	{
		$result = null;

		if (is_bool($entity))
		{
			$result = $entity
				?( $this->_entity instanceof AbstractDecorator
					? $this->_entity
					: null
				):( $this->_entity instanceof AbstractDecorator
					? $this->_entity->decorate(false)
					: $this->_entity
				);
		}
		elseif ($entity === null)
		{
			$result = $this->_entity;
			$this->_entity = null;
		}
		elseif ($entity instanceof EntityInterface)
		{
			$result = $this;

			if ($entity instanceof AbstractDecorator)
			{
				if ($entity->getDecoratorPriority() > $this->getDecoratorPriority())
				{
					if ($chain = $entity->decorate(null))
					{
						$result = $entity->decorate($this->decorate($chain));
					}
					else
					{
						$result = $entity->decorate($this);
					}
				}
				elseif ($this->_entity instanceof AbstractDecorator)
				{
					$this->_entity = $this->_entity->decorate($entity);
				}
				else
				{
					$this->_entity = $entity;
				}
			}
			elseif ($this->_entity instanceof AbstractDecorator)
			{
				$this->_entity->decorate($entity);
			}
			else
			{
				$this->_entity = $entity;
			}
		}
		else
		{
			$message = sprintf('Argument for method %1$s must implements %2$s.', __METHOD__, EntityInterface::class);
			assert($entity instanceof EntityInterface, $message);
		}

		return $result;
	}
}