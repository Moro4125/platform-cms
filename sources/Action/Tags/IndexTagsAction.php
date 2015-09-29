<?php
/**
 * Class IndexTagsAction
 */
namespace Moro\Platform\Action\Tags;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexTagsAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\Tags\ServiceTags getService();
 */
class IndexTagsAction extends AbstractIndexAction
{
	public $serviceCode   = Application::SERVICE_TAGS;
	public $template      = '@PlatformCMS/admin/content/tags-list.html.twig';
	public $route         = 'admin-content-tags';
	public $routeCreate   = 'admin-content-tags-create';
	public $routeUpdate   = 'admin-content-tags-update';
	public $routeDelete   = 'admin-content-tags-delete';
	public $routeBindTags = 'admin-content-tags-set-tag';
}