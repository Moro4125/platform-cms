<?php
/**
 * Class SetTopArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractSetTopAction;
use \Moro\Platform\Application;

/**
 * Class SetTopArticlesAction
 * @package Action\Articles
 */
class SetTopArticlesAction extends AbstractSetTopAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $route       = 'admin-content-articles-set-top';
	public $routeIndex  = 'admin-content-articles';
}