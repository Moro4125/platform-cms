<?php
/**
 * Class CreateMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractCreateAction;
use \Moro\Platform\Application;

/**
 * Class CreateMessagesAction
 * @package Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService()
 */
class CreateMessagesAction extends AbstractCreateAction
{
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $route       = 'admin-content-messages-create';
	public $routeIndex  = 'admin-content-messages';
	public $routeUpdate = 'admin-content-messages-update';

	protected $_tags = ['Подписка: на всё'];
}