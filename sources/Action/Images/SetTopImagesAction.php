<?php
/**
 * Class SetTopImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractSetTopAction;
use \Moro\Platform\Application;

/**
 * Class SetTopImagesAction
 * @package Action\Articles
 */
class SetTopImagesAction extends AbstractSetTopAction
{
	public $serviceCode = Application::SERVICE_FILE;
	public $route       = 'admin-content-images-set-top';
	public $routeIndex  = 'admin-content-images';
}