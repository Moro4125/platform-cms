<?php
/**
 * Class CloneRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Application;
use \Moro\Platform\Action\AbstractCloneAction;

/**
 * Class CloneRelinkAction
 * @package Moro\Platform\Action\Relink
 */
class CloneRelinkAction extends AbstractCloneAction
{
	public $serviceCode = Application::SERVICE_RELINK;
	public $route       = 'admin-content-relink-clone';
	public $routeIndex  = 'admin-content-relink';
	public $routeUpdate = 'admin-content-relink-update';

	/**
	 * @param array $properties
	 * @param int $id
	 * @return array
	 */
	protected function _prepareClone($properties, $id)
	{
		$properties['name'] .= ' - '.$id;

		return parent::_prepareClone($properties, $id);
	}
}