<?php
/**
 * Class FileDetach2ArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractFileDetachAction;
use \Moro\Platform\Application;

/**
 * Class FileDetach2ArticlesAction
 * @package Moro\Platform\Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent getEntity()
 */
class FileDetach2ArticlesAction extends AbstractFileDetachAction
{
	public $route = 'admin-content-articles-detach';
	public $serviceCode = Application::SERVICE_CONTENT;
	public $idPrefix = 'a';

	/**
	 * @param integer $id
	 * @param \Moro\Platform\Model\Implementation\File\FileInterface $file
	 * @throws \Exception
	 */
	protected function onSuccess($id, $file)
	{
		$this->getApplication()->getServiceRoutes()->setCompileFlagForTag(['art-'.$id, 'file-'.$file->getSmallHash()]);
	}
}