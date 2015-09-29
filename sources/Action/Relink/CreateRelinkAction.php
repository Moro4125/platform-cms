<?php
/**
 * Class CreateRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Action\AbstractCreateAction;
use \Moro\Platform\Application;

/**
 * Class CreateRelinkAction
 * @package Action\Relink
 *
 * @method \Moro\Platform\Model\Implementation\Relink\ServiceRelink getService()
 */
class CreateRelinkAction extends AbstractCreateAction
{
	public $serviceCode = Application::SERVICE_RELINK;
	public $route       = 'admin-content-relink-create';
	public $routeIndex  = 'admin-content-relink';
	public $routeUpdate = 'admin-content-relink-update';
}