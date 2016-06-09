<?php
/**
 * Class ToggleStarMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarMessagesAction
 * @package Moro\Platform\Action\Messages
 */
class ToggleStarMessagesAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_MESSAGES;
}