<?php
/**
 * Class DeleteTagsAction
 */
namespace Moro\Platform\Action\Tags;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Application;

/**
 * Class DeleteTagsAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\Tags\ServiceTags getService()
 * @method \Moro\Platform\Model\Implementation\Tags\EntityTags getEntity()
 */
class DeleteTagsAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_TAGS;
	public $template    = '@PlatformCMS/admin/content/tags-delete.html.twig';
	public $route       = 'admin-content-tags-delete';
	public $routeIndex  = 'admin-content-tags';
}