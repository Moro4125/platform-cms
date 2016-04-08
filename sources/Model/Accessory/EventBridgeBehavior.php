<?php
/**
 * Class EventBridgeBehavior
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Application;

/**
 * Class EventBridgeBehavior
 * @package Moro\Platform\Model\Accessory
 */
class EventBridgeBehavior extends AbstractBehavior
{
	const STATE_LAZY_INIT = 4001;

	/**
	 * @var int
	 */
	protected $_listen;

	/**
	 * @var int
	 */
	protected $_notify;

	/**
	 * @var AbstractService
	 */
	protected $_receive;

	/**
	 * @var Application
	 */
	protected $_application;

	/**
	 * EventBridgeBehavior constructor.
	 *
	 * @param int|array $listenOrArgs
	 * @param null int $notify
	 * @param null|AbstractService|string $receive
	 * @param null|Application $application
	 */
	public function __construct($listenOrArgs, $notify = null, $receive = null, Application $application = null)
	{
		if (is_array($listenOrArgs))
		{
			$this->_listen      = array_shift($listenOrArgs);
			$this->_notify      = array_shift($listenOrArgs);
			$this->_receive     = array_shift($listenOrArgs);
			$this->_application = array_shift($listenOrArgs);
		}
		else
		{
			$this->_listen      = $listenOrArgs;
			$this->_notify      = $notify;
			$this->_receive     = $receive;
			$this->_application = $application;
		}

		assert(isset($this->_listen));
		assert(isset($this->_notify));
		assert(is_string($this->_receive) ? isset($this->_application) : $this->_receive instanceof AbstractService);

		parent::__construct();
	}

	/**
	 * @param AbstractService $service
	 */
	protected function _initContext($service)
	{
		$this->_context[self::KEY_HANDLERS] = [
			$this->_listen => '_onEvent',
		];
	}

	/**
	 * @return mixed
	 */
	protected function _onEvent()
	{
		$args = func_get_args();
		array_unshift($args, $this->_notify);
		array_push($args, $this->_context[self::KEY_SUBJECT]);

		if (is_string($this->_receive))
		{
			$this->_receive = $this->_application->offsetGet($this->_receive);
			$this->_application = null;
		}

		return call_user_func_array([$this->_receive, 'notify'], $args);
	}
}