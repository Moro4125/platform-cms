<?php
/**
 * Class SetTagMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagMessagesAction
 * @package Action\Messages
 */
class SetTagMessagesAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $template    = '@PlatformCMS/admin/content/abstract-set-tag.html.twig';
	public $route       = 'admin-content-messages-set-tag';
	public $routeIndex  = 'admin-content-messages';
}