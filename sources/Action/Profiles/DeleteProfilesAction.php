<?php
/**
 * Class DeleteProfilesAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Application;

/**
 * Class DeleteProfilesAction
 * @package Action\Profiles
 *
 * @method \Moro\Platform\Model\Implementation\Users\ServiceUsers getService()
 * @method \Moro\Platform\Model\Implementation\Users\EntityUsers getEntity()
 */
class DeleteProfilesAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_USERS;
	public $template    = '@PlatformCMS/admin/users/profiles-delete.html.twig';
	public $route       = 'admin-users-profiles-delete';
	public $routeIndex  = 'admin-users-profiles';
}