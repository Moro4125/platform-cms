<?php
/**
 * Class UpdateMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Application;

/**
 * Class UpdateMessagesAction
 * @package Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService()
 * @method \Moro\Platform\Model\Implementation\Messages\EntityMessages getEntity()
 */
class UpdateMessagesAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $template    = '@PlatformCMS/admin/content/messages-update.html.twig';
	public $route       = 'admin-content-messages-update';
	public $routeIndex  = 'admin-content-messages';
	public $routeDelete = 'admin-content-messages-delete';

	/**
	 * @var array
	 */
	protected $_patchTextKeys = ['parameters.text'];

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$entity = $this->getEntity();
		$app = $this->getApplication();

		$parameters = parent::_getViewParameters();
		$parameters['upload'] = $this->getService()->createAdminUploadForm($app, $entity)->createView();
		$parameters['title'] = $this->getEntity()->getName().' - Редактирование сообщения';

		return $parameters;
	}
}