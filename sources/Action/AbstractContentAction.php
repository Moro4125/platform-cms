<?php
/**
 * Class AbstractContentAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Moro\Platform\Application;


/**
 * Class AbstractContentAction
 * @package Action
 */
abstract class AbstractContentAction
{
	/**
	 * @var string  Символьный код сервиса.
	 */
	public $serviceCode;

	/**
	 * @var string  Название "пути" к данному действию.
	 */
	public $route;

	/**
	 * @var string  Название файла шаблона.
	 */
	public $template;

	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_application;

	/**
	 * @var Request
	 */
	protected $_request;

	/**
	 * @var \Moro\Platform\Model\AbstractService
	 */
	protected $_service;

	/**
	 * @param Application $application
	 * @return $this
	 */
	public function setApplication(Application $application)
	{
		$this->_application = $application;
		return $this;
	}

	/**
	 * @return Application
	 */
	public function getApplication()
	{
		return $this->_application ?: $this->_application = Application::getInstance();
	}

	/**
	 * @param Request $request
	 * @return $this
	 */
	public function setRequest(Request $request)
	{
		$this->_request = $request;
		return $this;
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		if (empty($this->_request))
		{
			$this->setRequest($this->getApplication()->offsetGet('request'));
		}

		return $this->_request;
	}

	/**
	 * @return \Moro\Platform\Model\AbstractService|ContentActionsInterface
	 */
	public function getService()
	{
		$this->_service || $this->_service = $this->_createService();
		assert($this->_service instanceof ContentActionsInterface);
		return $this->_service;
	}

	/**
	 * @return \Moro\Platform\Model\AbstractService|ContentActionsInterface
	 */
	protected function _createService()
	{
		return $this->getApplication()->offsetGet($this->serviceCode);
	}
}