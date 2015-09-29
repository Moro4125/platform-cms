<?php
/**
 * Class IndexImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexImagesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\File\ServiceFile getService();
 */
class IndexImagesAction extends AbstractIndexAction
{
	public $serviceCode   = Application::SERVICE_FILE;
	public $template      = '@PlatformCMS/admin/content/image-list.html.twig';
	public $route         = 'admin-content-images';
	public $routeUpdate   = 'admin-content-images-update';
	public $routeDelete   = 'admin-content-images-delete';
	public $routeBindTags = 'admin-content-images-set-tag';

	/**
	 * @var array  Базовые условия фильтрации данных.
	 */
	public $where = ['kind' => '1x1'];

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		return array_merge(parent::_getViewParameters(), [
			'upload' => $this->getService()->createAdminUploadsForm($this->_application)->createView(),
		]);
	}
}