<?php
/**
 * Class ToggleStarRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarRelinkAction
 * @package Moro\Platform\Action\Relink
 */
class ToggleStarRelinkAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_RELINK;
}