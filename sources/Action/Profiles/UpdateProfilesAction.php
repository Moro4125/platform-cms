<?php
/**
 * Class UpdateProfilesAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Moro\Platform\Application;

/**
 * Class UpdateProfilesAction
 * @package Action\Profiles
 *
 * @method \Moro\Platform\Model\Implementation\Users\ServiceUsers getService()
 * @method \Moro\Platform\Model\Implementation\Users\EntityUsers getEntity()
 */
class UpdateProfilesAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_USERS;
	public $template    = '@PlatformCMS/admin/users/profiles-update.html.twig';
	public $route       = 'admin-users-profiles-update';
	public $routeIndex  = 'admin-users-profiles';
	public $routeDelete = 'admin-users-profiles-delete';

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$application = $this->getApplication();
		$authService = $application->getServiceUsersAuth();
		$parameters  = parent::_getViewParameters();

		$parameters['auth_list'] = $authService->selectEntities(null, null, '!order_at', [
			UsersAuthInterface::PROP_USER_ID => $this->getEntity()->getId(),
		]);

		$parameters['editRights'] = true;
		$args = $this->getEntity()->getParameters();

		foreach (isset($args['roles']) ? $args['roles'] : [] as $role)
		{
			if (!$application->isGranted($role))
			{
				$parameters['editRights'] = false;
			}
		}

		$choices = array_merge($application['security.role_hierarchy'], ['ROLE_USER' => 0]);
		$service = $this->_application->getServiceTags();

		foreach ($choices as $role => &$name)
		{
			$name = ($list = $service->selectEntities(0, 1, null, 'tag', strtr($role, ['ROLE_' => 'Role: '])))
					? reset($list)->getName()
					: $role;
			$name = explode(':', $name, 2);
			$name = trim(end($name));
		}

		$parameters['roles'] = $choices;

		return $parameters;
	}
}