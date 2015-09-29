<?php
/**
 * Class CreateTagsAction
 */
namespace Moro\Platform\Action\Tags;
use \Moro\Platform\Action\AbstractCreateAction;
use \Moro\Platform\Application;

/**
 * Class CreateTagsAction
 * @package Action\Tags
 *
 * @method \Moro\Platform\Model\Implementation\Tags\ServiceTags getService()
 */
class CreateTagsAction extends AbstractCreateAction
{
	public $serviceCode = Application::SERVICE_TAGS;
	public $route       = 'admin-content-tags-create';
	public $routeIndex  = 'admin-content-tags';
	public $routeUpdate = 'admin-content-tags-update';
}