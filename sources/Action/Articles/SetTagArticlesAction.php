<?php
/**
 * Class SetTagArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagArticlesAction
 * @package Action\Articles
 */
class SetTagArticlesAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $template    = '@PlatformCMS/admin/content/abstract-set-tag.html.twig';
	public $route       = 'admin-content-articles-set-tag';
	public $routeIndex  = 'admin-content-articles';
}