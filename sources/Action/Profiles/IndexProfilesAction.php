<?php
/**
 * Class IndexProfilesAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexProfilesAction
 * @package Action\Profiles
 *
 * @method \Moro\Platform\Model\Implementation\Users\ServiceUsers getService();
 */
class IndexProfilesAction extends AbstractIndexAction
{
	public $title         = 'Карточки пользователей';
	public $serviceCode   = Application::SERVICE_USERS;
	public $template      = '@PlatformCMS/admin/users/profiles-list.html.twig';
	public $route         = 'admin-users-profiles';
	public $routeCreate   = 'admin-users-profiles-create';
	public $routeUpdate   = 'admin-users-profiles-update';
	public $routeDelete   = 'admin-users-profiles-delete';
	public $routeBindTags = 'admin-users-profiles-set-tag';

	/**
	 * @var bool  Флаг использования поиска по символьному идентификатору.
	 */
	public $useCode = false;

	/**
	 * @var bool  Флаг использования поиска по адресу эл.почты.
	 */
	public $useEmail = true;

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$view = parent::_getViewParameters();

		$choices = array_merge($this->_application['security.role_hierarchy'], ['ROLE_USER' => 0]);
		$service = $this->_application->getServiceTags();

		foreach ($choices as $role => &$name)
		{
			$name = ($list = $service->selectEntities(0, 1, null, 'tag', strtr($role, ['ROLE_' => 'Role: '])))
				? reset($list)->getName()
				: $role;
			$name = explode(':', $name, 2);
			$name = trim(end($name));
		}

		$view['roles'] = $choices;

		return $view;
	}
}