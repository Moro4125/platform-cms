<?php
/**
 * Class SetTagTagsAction
 */
namespace Moro\Platform\Action\Tags;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagTagsAction
 * @package Action\Articles
 */
class SetTagTagsAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_TAGS;
	public $template    = '@PlatformCMS/admin/content/abstract-set-tag.html.twig';
	public $route       = 'admin-content-tags-set-tag';
	public $routeIndex  = 'admin-content-tags';
}