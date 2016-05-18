<?php
/**
 * Class SetTagSubscribersAction
 */
namespace Moro\Platform\Action\Subscribers;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagSubscribersAction
 * @package Action\Subscribers
 */
class SetTagSubscribersAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_SUBSCRIBERS;
	public $template    = '@PlatformCMS/admin/users/abstract-set-tag.html.twig';
	public $route       = 'admin-users-subscribers-set-tag';
	public $routeIndex  = 'admin-users-subscribers';
}