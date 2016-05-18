<?php
/**
 * Class CreateSubscribersAction
 */
namespace Moro\Platform\Action\Subscribers;
use \Moro\Platform\Action\AbstractCreateAction;
use \Moro\Platform\Application;

/**
 * Class CreateSubscribersAction
 * @package Action\Subscribers
 *
 * @method \Moro\Platform\Model\Implementation\Subscribers\ServiceSubscribers getService()
 */
class CreateSubscribersAction extends AbstractCreateAction
{
	public $serviceCode = Application::SERVICE_SUBSCRIBERS;
	public $route       = 'admin-users-subscribers-create';
	public $routeIndex  = 'admin-users-subscribers';
	public $routeUpdate = 'admin-users-subscribers-update';

	/**
	 * @return \Moro\Platform\Model\Implementation\Subscribers\EntitySubscribers
	 */
	protected function _createNewEntity()
	{
		$email = 'temp_'.mt_rand(100000, 999999).'@'.$this->getRequest()->getHost();
		return $this->getService()->createNewEntityWithId($email);
	}
}