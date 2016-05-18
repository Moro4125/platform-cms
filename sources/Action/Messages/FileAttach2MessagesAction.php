<?php
/**
 * Class FileAttach2MessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractFileAttachAction;
use \Moro\Platform\Application;

/**
 * Class FileAttach2MessagesAction
 * @package Moro\Platform\Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService()
 * @method \Moro\Platform\Model\Implementation\Messages\EntityMessages getEntity()
 */
class FileAttach2MessagesAction extends AbstractFileAttachAction
{
	public $route = 'admin-content-messages-attach';
	public $routeDetach = 'admin-content-messages-detach';
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $idPrefix = 'm';
}