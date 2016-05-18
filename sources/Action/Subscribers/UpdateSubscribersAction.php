<?php
/**
 * Class UpdateSubscribersAction
 */
namespace Moro\Platform\Action\Subscribers;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Application;

/**
 * Class UpdateSubscribersAction
 * @package Action\Subscribers
 *
 * @method \Moro\Platform\Model\Implementation\Subscribers\ServiceSubscribers getService()
 * @method \Moro\Platform\Model\Implementation\Subscribers\EntitySubscribers getEntity()
 */
class UpdateSubscribersAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_SUBSCRIBERS;
	public $template    = '@PlatformCMS/admin/users/subscribers-update.html.twig';
	public $route       = 'admin-users-subscribers-update';
	public $routeIndex  = 'admin-users-subscribers';
	public $routeDelete = 'admin-users-subscribers-delete';
}