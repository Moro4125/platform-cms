<?php
/**
 * Class SetTagProfilesAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagProfilesAction
 * @package Action\Profiles
 */
class SetTagProfilesAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_USERS;
	public $template    = '@PlatformCMS/admin/users/abstract-set-tag.html.twig';
	public $route       = 'admin-users-profiles-set-tag';
	public $routeIndex  = 'admin-users-profiles';
}