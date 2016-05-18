<?php
/**
 * Class FileAttach2ArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractFileAttachAction;
use \Moro\Platform\Application;

/**
 * Class FileAttach2ArticlesAction
 * @package Moro\Platform\Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent getEntity()
 */
class FileAttach2ArticlesAction extends AbstractFileAttachAction
{
	public $route = 'admin-content-articles-attach';
	public $routeDetach = 'admin-content-articles-detach';
	public $serviceCode = Application::SERVICE_CONTENT;
	public $idPrefix = 'a';

	/**
	 * @param integer $id
	 * @throws \Exception
	 */
	protected function onSuccess($id)
	{
		$this->getApplication()->getServiceRoutes()->setCompileFlagForTag(['art-'.$id]);
	}
}