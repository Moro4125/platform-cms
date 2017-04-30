<?php
/**
 * Class DeleteSubscribersAction
 */
namespace Moro\Platform\Action\Subscribers;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Model\Implementation\Subscribers\SubscribersInterface;
use \Moro\Platform\Application;

/**
 * Class DeleteSubscribersAction
 * @package Action\Subscribers
 *
 * @method \Moro\Platform\Model\Implementation\Subscribers\ServiceSubscribers getService()
 * @method \Moro\Platform\Model\Implementation\Subscribers\EntitySubscribers getEntity()
 */
class DeleteSubscribersAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_SUBSCRIBERS;
	public $template    = '@PlatformCMS/admin/users/subscribers-delete.html.twig';
	public $route       = 'admin-users-subscribers-delete';
	public $routeIndex  = 'admin-users-subscribers';

	/**
	 * @param $entity SubscribersInterface
	 */
	protected function _prepareForMoveToTrash($entity)
	{
		$entity->addTags(['флаг: удалено']);
		$entity->setActive(0);
	}
}