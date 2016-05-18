<?php
/**
 * Class IndexMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexMessagesAction
 * @package Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService();
 */
class IndexMessagesAction extends AbstractIndexAction
{
	public $title         = 'Оповещения';
	public $serviceCode   = Application::SERVICE_MESSAGES;
	public $template      = '@PlatformCMS/admin/content/messages-list.html.twig';
	public $route         = 'admin-content-messages';
	public $routeCreate   = 'admin-content-messages-create';
	public $routeUpdate   = 'admin-content-messages-update';
	public $routeDelete   = 'admin-content-messages-delete';
	public $routeBindTags = 'admin-content-messages-set-tag';
	public $routeSend     = 'admin-content-messages-send';

	/**
	 * @var bool  Флаг использования поиска по символьному идентификатору.
	 */
	public $useCode = false;

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$application = $this->getApplication();
		$parameters = parent::_getViewParameters();

		$parameters['startingLine'] = $application->getServiceSubscribers()->getStartingLine();

		return $parameters;
	}

	/**
	 * @param array $list
	 * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doAction($list)
	{
		$app = $this->getApplication();
		$form = $this->getForm();

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->has('send') && $form->get('send')->isClicked())
		{
			if (count($list))
			{
				return $app->redirect($app->url($this->routeSend, ['ids' => implode(',', $list), 'flag' => 1]));
			}

			$app->getServiceFlash()->alert('Нужно выбрать одно или более оповещение для отправки.');
		}

		return parent::_doAction($list);
	}
}