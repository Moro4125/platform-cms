<?php
/**
 * Class CreateArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractCreateAction;
use \Moro\Platform\Application;

/**
 * Class CreateArticlesAction
 * @package Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 */
class CreateArticlesAction extends AbstractCreateAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $route       = 'admin-content-articles-create';
	public $routeIndex  = 'admin-content-articles';
	public $routeUpdate = 'admin-content-articles-update';
}