<?php
/**
 * Class SetTagRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagRelinkAction
 * @package Action\Relink
 */
class SetTagRelinkAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_RELINK;
	public $template    = '@PlatformCMS/admin/content/abstract-set-tag.html.twig';
	public $route       = 'admin-content-relink-set-tag';
	public $routeIndex  = 'admin-content-relink';
}