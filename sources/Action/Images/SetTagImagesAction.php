<?php
/**
 * Class SetTagImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagArticlesAction
 * @package Action\Articles
 */
class SetTagImagesAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_FILE;
	public $template    = '@PlatformCMS/admin/content/abstract-set-tag.html.twig';
	public $route       = 'admin-content-images-set-tag';
	public $routeIndex  = 'admin-content-images';
}