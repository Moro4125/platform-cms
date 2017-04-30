<?php
/**
 * Class DeleteMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Application;

/**
 * Class DeleteMessagesAction
 * @package Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService()
 * @method \Moro\Platform\Model\Implementation\Messages\EntityMessages getEntity()
 */
class DeleteMessagesAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $template    = '@PlatformCMS/admin/content/messages-delete.html.twig';
	public $route       = 'admin-content-messages-delete';
	public $routeIndex  = 'admin-content-messages';

	/**
	 * @param $entity TagsEntityInterface
	 */
	protected function _prepareForMoveToTrash($entity)
	{
		$entity->addTags(['флаг: удалено']);
	}
}