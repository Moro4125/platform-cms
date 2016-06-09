<?php
/**
 * Class ToggleStarImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarImagesAction
 * @package Moro\Platform\Action\Images
 */
class ToggleStarImagesAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_FILE;
}