<?php
/**
 * Class DeleteImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Application;

/**
 * Class DeleteImagesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\File\ServiceFile getService()
 * @method \Moro\Platform\Model\Implementation\File\EntityFile getEntity()
 */
class DeleteImagesAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_FILE;
	public $template    = '@PlatformCMS/admin/content/image-delete.html.twig';
	public $route       = 'admin-content-images-delete';
	public $routeIndex  = 'admin-content-images';
}