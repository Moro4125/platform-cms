<?php
/**
 * Class IndexSubscribersAction
 */
namespace Moro\Platform\Action\Subscribers;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexSubscribersAction
 * @package Action\Subscribers
 *
 * @method \Moro\Platform\Model\Implementation\Subscribers\ServiceSubscribers getService();
 */
class IndexSubscribersAction extends AbstractIndexAction
{
	public $title         = 'Подписчики';
	public $serviceCode   = Application::SERVICE_SUBSCRIBERS;
	public $template      = '@PlatformCMS/admin/users/subscribers-list.html.twig';
	public $route         = 'admin-users-subscribers';
	public $routeCreate   = 'admin-users-subscribers-create';
	public $routeUpdate   = 'admin-users-subscribers-update';
	public $routeDelete   = 'admin-users-subscribers-delete';
	public $routeBindTags = 'admin-users-subscribers-set-tag';

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
		$application = $this->getApplication();
		$parameters = parent::_getViewParameters();

		$parameters['finishLine'] = $application->getServiceMessages()->getFinishLine();

		return $parameters;
	}
}