<?php
/**
 * Trait EntityTrait
 */
namespace Moro\Platform\Model;
use \Moro\Platform\Model\Exception\ReadOnlyPropertyException;
use \Moro\Platform\Model\Exception\UnknownPropertyException;
use \ReflectionObject;

/**
 * Trait EntityTrait
 * @package Model
 */
trait EntityTrait
{
	/**
	 * @var array
	 */
	protected static $_default = [];

	/**
	 * @var array
	 */
	protected static $_setters = [];

	/**
	 * @var array
	 */
	protected static $_getters = [];

	/**
	 * @var array
	 */
	protected $_properties = [];

	/**
	 * @var int
	 */
	protected $_flags = 0;

	/**
	 * @var null|array
	 */
	protected $_reflection;

	/**
	 * @param null|array $properties
	 */
	public function __construct(array $properties = null)
	{
		if (empty(self::$_default[static::class]))
		{
			$this->_initEntityReflection();
		}

		$this->_reflection = [
			self::$_default[static::class],
			self::$_getters[static::class],
			self::$_setters[static::class],
		];
		$this->_properties = self::$_default[static::class];

		if ($properties)
		{
			$properties = array_diff_key($properties, array_filter($properties, 'is_null'));
			$properties = array_intersect_key($properties, $this->_properties);
			$this->_flags = EntityInterface::FLAG_DATABASE;

			try
			{
				$this->setProperties($properties);
			}
			finally
			{
				$this->_flags = 0;
			}
		}
	}

	/**
	 * @return void
	 */
	public function __clone()
	{
		$this->_flags |= EntityInterface::FLAG_CLONED;
		$this->_reflection = null;
	}

	/**
	 * @return void
	 */
	public function __wakeup()
	{
		if (is_array($this->_reflection) && count($this->_reflection) === 3)
		{
			self::$_default[static::class] = $this->_reflection[0];
			self::$_getters[static::class] = $this->_reflection[1];
			self::$_setters[static::class] = $this->_reflection[2];
		}

		if (empty(self::$_default[static::class]))
		{
			$this->_initEntityReflection();
		}
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
		return array_key_exists($name, self::$_default[static::class]);
	}

	/**
	 * @param array|\Traversable $properties
	 * @return $this
	 * @throws \Moro\Platform\Model\Exception\UnknownPropertyException
	 */
	public function setProperties($properties)
	{
		foreach ($properties as $name => $value)
		{
			if (isset(self::$_setters[static::class][$name]))
			{
				$this->{self::$_setters[static::class][$name]}($value);
			}
			elseif (array_key_exists($name, $this->_properties))
			{
				$this->_properties[$name] = $value;
			}
			elseif (!($this->_flags & EntityInterface::FLAG_DATABASE))
			{
				$message = sprintf(UnknownPropertyException::ERROR_UNKNOWN_PROPERTY, $name);
				throw new UnknownPropertyException($message, UnknownPropertyException::CODE_SET_UNKNOWN_PROPERTY_NAME);
			}
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 * @throws UnknownPropertyException
	 */
	public function setProperty($name, $value)
	{
		if (isset(self::$_setters[static::class][$name]))
		{
			$this->{self::$_setters[static::class][$name]}($value);
		}
		elseif (array_key_exists($name, $this->_properties))
		{
			$this->_properties[$name] = $value;
		}
		else
		{
			$message = sprintf(UnknownPropertyException::ERROR_UNKNOWN_PROPERTY, $name);
			throw new UnknownPropertyException($message, UnknownPropertyException::CODE_SET_UNKNOWN_PROPERTY_NAME);
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		$properties = [];

		foreach ($this->_properties as $name => $value)
		{
			$properties[$name] = isset(self::$_getters[static::class][$name])
				? $this->{self::$_getters[static::class][$name]}()
				: $value;
		}

		return $properties;
	}

	/**
	 * @param string $name
	 * @return mixed
	 * @throws UnknownPropertyException
	 */
	public function getProperty($name)
	{
		if (isset(self::$_getters[static::class][$name]))
		{
			return $this->{self::$_getters[static::class][$name]}();
		}
		elseif (array_key_exists($name, $this->_properties))
		{
			return $this->_properties[$name];
		}

		$message = sprintf(UnknownPropertyException::ERROR_UNKNOWN_PROPERTY, $name);
		throw new UnknownPropertyException($message, UnknownPropertyException::CODE_GET_UNKNOWN_PROPERTY_NAME);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return (int)$this->_properties[EntityInterface::PROP_ID];
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		if (isset($this->_properties[EntityInterface::PROP_ID]))
		{
			$message = sprintf(ReadOnlyPropertyException::ERROR_READ_ONLY_PROPERTY, EntityInterface::PROP_ID);
			throw new ReadOnlyPropertyException($message, ReadOnlyPropertyException::CODE_ID_PROPERTY_IS_READ_ONLY);
		}

		$this->_properties[EntityInterface::PROP_ID] = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFlags()
	{
		return $this->_flags;
	}

	/**
	 * @param int $flags
	 * @return $this
	 */
	public function setFlags($flags)
	{
		$this->_flags = (int)$flags;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return $this
	 */
	public function addFlag($flag)
	{
		$this->_flags |= $flag;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return $this
	 */
	public function delFlag($flag)
	{
		$this->_flags &= ~$flag;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return bool
	 */
	public function hasFlag($flag)
	{
		return ($this->_flags & $flag) === $flag;
	}

	/**
	 * @return int
	 */
	public function getCreatedAt()
	{
		return isset($this->_properties[EntityInterface::PROP_CREATED_AT])
			? $this->_properties[EntityInterface::PROP_CREATED_AT]
			: 0;
	}

	/**
	 * @return int
	 */
	public function getUpdatedAt()
	{
		return isset($this->_properties[EntityInterface::PROP_UPDATED_AT])
			? $this->_properties[EntityInterface::PROP_UPDATED_AT]
			: 0 ;
	}

	/**
	 * @param int|string $value
	 */
	protected function setCreatedAt($value)
	{
		if ($this->_flags & EntityInterface::FLAG_TIMESTAMP_CONVERTED)
		{
			$value = strtotime($value);
		}

		$this->_properties[EntityInterface::PROP_CREATED_AT] = (int)$value;
	}

	/**
	 * @param int|string $value
	 */
	protected function setUpdatedAt($value)
	{
		if ($this->_flags & EntityInterface::FLAG_TIMESTAMP_CONVERTED)
		{
			$value = strtotime($value);
		}

		$this->_properties[EntityInterface::PROP_UPDATED_AT] = (int)$value;
	}

	/**
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->hasProperty($offset);
	}

	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->getProperty($offset);
	}

	/**
	 * @param string $offset
	 * @param mixed $value
	 * @return $this
	 */
	public function offsetSet($offset, $value)
	{
		$this->setProperty($offset, $value);
		return $this;
	}

	/**
	 * @param string $offset
	 * @return $this
	 */
	public function offsetUnset($offset)
	{
		$this->setProperty($offset, null);
		return $this;
	}

	/**
	 * @return void
	 */
	protected function _initEntityReflection()
	{
		$reflection = new ReflectionObject($this);
		$unusedKeys = [];

		foreach ($reflection->getConstants() as $name => $value)
		{
			if (strncmp($name, 'PROP_', 5) === 0)
			{
				self::$_default[static::class][$value] = $reflection->hasConstant('DEFAULT_'.substr($name, 5))
					? $reflection->getConstant('DEFAULT_'.substr($name, 5))
					: null;
			}
			elseif (strncmp($name, 'FREE_', 5) === 0)
			{
				$unusedKeys[$value] = true;
			}
		}

		self::$_default[static::class] = array_diff_key(self::$_default[static::class], $unusedKeys);

		foreach ($reflection->getMethods() as $method)
		{
			if (preg_match('~^(?P<do>get|set)(?P<name>[A-Z].*)$~', $method->getName(), $match))
			{
				$name = strtolower(preg_replace('~([a-z0-9])([A-Z])~', '$1_$2', $match['name']));

				if ($match['do'] == 'get')
				{
					self::$_getters[static::class][$name] = $match[0];
				}
				else
				{
					self::$_setters[static::class][$name] = $match[0];
				}
			}
		}
	}
}