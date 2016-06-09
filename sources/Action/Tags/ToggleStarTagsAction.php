<?php
/**
 * Class ToggleStarTagsAction
 */
namespace Moro\Platform\Action\Tags;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarTagsAction
 * @package Moro\Platform\Action\Tags
 */
class ToggleStarTagsAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_TAGS;
}