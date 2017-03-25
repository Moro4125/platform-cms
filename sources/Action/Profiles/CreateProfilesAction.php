<?php
/**
 * Class CreateProfilesAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Action\AbstractCreateAction;
use \Moro\Platform\Application;

/**
 * Class CreateProfilesAction
 * @package Action\Profiles
 *
 * @method \Moro\Platform\Model\Implementation\Users\ServiceUsers getService()
 */
class CreateProfilesAction extends AbstractCreateAction
{
	public $serviceCode = Application::SERVICE_USERS;
	public $route       = 'admin-users-profiles-create';
	public $routeIndex  = 'admin-users-profiles';
	public $routeUpdate = 'admin-users-profiles-update';

	/**
	 * @return \Moro\Platform\Model\Implementation\Users\UsersInterface
	 */
	protected function _createNewEntity()
	{
		$email = 'temp_'.mt_rand(100000, 999999).'@'.$this->getRequest()->getHost();
		return $this->getService()->createNewEntityWithId($email);
	}
}