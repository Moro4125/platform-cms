<?php
/**
 * Class FileDetach2MessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractFileDetachAction;
use \Moro\Platform\Application;

/**
 * Class FileDetach2MessagesAction
 * @package Moro\Platform\Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService()
 * @method \Moro\Platform\Model\Implementation\Messages\EntityMessages getEntity()
 */
class FileDetach2MessagesAction extends AbstractFileDetachAction
{
	public $route = 'admin-content-messages-detach';
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $idPrefix = 'm';
}