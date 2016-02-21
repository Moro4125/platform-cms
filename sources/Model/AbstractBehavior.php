<?php
/**
 * Class AbstractBehavior
 */
namespace Moro\Platform\Model;
use \SplObserver;
use \SplSubject;
use \LogicException;
use \ReflectionObject;

/**
 * Class AbstractBehavior
 * @package Model
 */
abstract class AbstractBehavior implements SplObserver
{
	const KEY_HANDLERS = 'handlers';
	const KEY_SUBJECT  = 'subject';
	const KEY_LOCKED   = 'locked';

	/**
	 * @var array
	 */
	private $_subjects = [];

	/**
	 * @var array
	 */
	protected $_context = null;

	/**
	 * @var bool
	 */
	protected $_enabled = true;

	/**
	 * @var array
	 */
	protected $_reflection = null;

	/**
	 * @var array
	 */
	protected static $_methods = [];

	/**
	 * AbstractBehavior constructor.
	 */
	public function __construct()
	{
		if (empty(self::$_methods[static::class]))
		{
			$this->_initEntityReflection();
		}

		$this->_reflection = self::$_methods[static::class];
	}

	/**
	 * @return void
	 */
	public function __wakeup()
	{
		if (empty(self::$_methods[static::class]))
		{
			if ($this->_reflection)
			{
				self::$_methods[static::class] = $this->_reflection;
			}
			else
			{
				$this->_initEntityReflection();
			}
		}
	}

	/**
	 * @return void
	 */
	protected function _initEntityReflection()
	{
		$reflection = new ReflectionObject($this);

		foreach ($reflection->getMethods() as $method)
		{
			if (($name = $method->getName()) && $name[0] != '_' && $name != 'update')
			{
				self::$_methods[static::class][$name] = true;
			}
		}
	}

	/**
	 * @param AbstractService $service
	 */
	abstract protected function _initContext($service);

	/**
	 * @param AbstractService $service
	 */
	protected function _freeContext($service)
	{
	}

	/**
	 * @param SplSubject|AbstractService $subject
	 * @param null|array $args
	 * @return mixed
	 *
	 * @throws LogicException
	 */
	public function update(SplSubject $subject, array $args = null)
	{
		$args  = (array)$args;
		$code  = $subject->getServiceCode();
		$state = $subject->getState();
		$value = $this->_context;

		if (AbstractService::STATE_ATTACH_BEHAVIOR === $state && isset($args[0]) && $args[0] === $this)
		{
			try
			{
				$this->_context = [];
				$this->_initContext($subject);
				$this->_subjects[$code] = $this->_context;
			}
			finally
			{
				$this->_context = $value;
			}
		}

		if (!isset($this->_subjects[$code]))
		{
			return null;
		}

		if (AbstractService::STATE_DETACH_BEHAVIOR === $state && isset($args[0]) && $args[0] === $this)
		{
			try
			{
				$this->_context = $this->_subjects[$code];
				$this->_freeContext($subject);
			}
			finally
			{
				unset($this->_subjects[$code]);
				$this->_context = $value;
			}
		}

		if (empty($this->_enabled))
		{
			return null;
		}

		if (AbstractService::STATE_BEHAVIOR_METHOD === $state && isset(self::$_methods[static::class][$args[0]]))
		{
			if (!empty($this->_subjects[$code][self::KEY_LOCKED]))
			{
				throw new LogicException('You can not use this behavior with recursion logic.');
			}

			try
			{
				$subject->stopNotify();
				$this->_context = $this->_subjects[$code];

				if (!isset($this->_subjects[$code][self::KEY_LOCKED]))
				{
					$this->_subjects[$code][self::KEY_LOCKED] = true;
				}

				return call_user_func_array([$this, $args[0]], $args[1]);
			}
			finally
			{
				$this->_subjects[$code] = $this->_context;
				$this->_context = $value;
			}
		}

		if (empty($this->_subjects[$code][self::KEY_HANDLERS][$state]))
		{
			return null;
		}

		if (empty($this->_subjects[$code][self::KEY_LOCKED]))
		{
			try
			{
				$this->_context = $this->_subjects[$code];
				$this->_context[self::KEY_SUBJECT] = $subject;

				if (!isset($this->_subjects[$code][self::KEY_LOCKED]))
				{
					$this->_subjects[$code][self::KEY_LOCKED] = true;
				}

				return call_user_func_array([$this, $this->_subjects[$code][self::KEY_HANDLERS][$state]], $args);
			}
			finally
			{
				unset($this->_context[self::KEY_SUBJECT]);
				$this->_subjects[$code] = $this->_context;
				$this->_context = $value;
			}
		}

		throw new LogicException('You can not use this behavior with recursion logic.');
	}

	/**
	 * @return bool
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param $enabled
	 * @return $this
	 */
	public function setEnabled($enabled)
	{
		$this->_enabled = (bool)$enabled;
		return $this;
	}
}