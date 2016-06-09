<?php
/**
 * Class ToggleStarSubscribersAction
 */
namespace Moro\Platform\Action\Subscribers;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarSubscribersAction
 * @package Moro\Platform\Action\Subscribers
 */
class ToggleStarSubscribersAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_SUBSCRIBERS;
}