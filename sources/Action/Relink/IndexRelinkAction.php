<?php
/**
 * Class IndexRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexRelinkAction
 * @package Action\Relink
 *
 * @method \Moro\Platform\Model\Implementation\Relink\ServiceRelink getService();
 */
class IndexRelinkAction extends AbstractIndexAction
{
	public $serviceCode   = Application::SERVICE_RELINK;
	public $template      = '@PlatformCMS/admin/content/relink-list.html.twig';
	public $route         = 'admin-content-relink';
	public $routeCreate   = 'admin-content-relink-create';
	public $routeUpdate   = 'admin-content-relink-update';
	public $routeDelete   = 'admin-content-relink-delete';
	public $routeBindTags = 'admin-content-relink-set-tag';

	public $useCode = false;
}