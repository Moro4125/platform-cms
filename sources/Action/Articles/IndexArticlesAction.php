<?php
/**
 * Class IndexArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexArticlesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService();
 */
class IndexArticlesAction extends AbstractIndexAction
{
	public $title         = 'Материалы сайта';
	public $serviceCode   = Application::SERVICE_CONTENT;
	public $template      = '@PlatformCMS/admin/content/article-list.html.twig';
	public $route         = 'admin-content-articles';
	public $routeCreate   = 'admin-content-articles-create';
	public $routeUpdate   = 'admin-content-articles-update';
	public $routeDelete   = 'admin-content-articles-delete';
	public $routeBindTags = 'admin-content-articles-set-tag';
}