<?php
/**
 * Class AbstractBehavior
 */
namespace Moro\Platform\Model;

use \SplObserver;
use \SplSubject;

/**
 * Class AbstractBehavior
 * @package Model
 */
abstract class AbstractBehavior implements SplObserver
{
	const KEY_HANDLERS = 'handlers';
	const KEY_SUBJECT  = 'subject';

	/**
	 * @var AbstractService
	 */
	protected $_subjects = [];

	/**
	 * @var array
	 */
	protected $_context = null;

	/**
	 * @var bool
	 */
	protected $_enabled = true;

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
	 */
	public function update(SplSubject $subject, array $args = null)
	{
		$args  = (array)$args;
		$code  = $subject->getServiceCode();
		$state = $subject->getState();

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
				$this->_context = null;
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
				$this->_context = null;
				unset($this->_subjects[$code]);
			}
		}

		if (empty($this->_enabled))
		{
			return null;
		}

		if (AbstractService::STATE_BEHAVIOR_METHOD === $state && method_exists($this, $args[0]) && $args[0][0] !== '_')
		{
			try
			{
				$subject->stopNotify();
				$this->_context = $this->_subjects[$code];
				return call_user_func_array([$this, $args[0]], $args[1]);
			}
			finally
			{
				$this->_subjects[$code] = $this->_context;
				$this->_context = null;
			}
		}

		if (isset($this->_subjects[$code][self::KEY_HANDLERS][$state]))
		{
			try
			{
				$this->_context = $this->_subjects[$code];
				$this->_context[self::KEY_SUBJECT] = $subject;
				return call_user_func_array([$this, $this->_subjects[$code][self::KEY_HANDLERS][$state]], $args);
			}
			finally
			{
				unset($this->_context[self::KEY_SUBJECT]);
				$this->_subjects[$code] = $this->_context;
				$this->_context = null;
			}
		}

		return null;
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