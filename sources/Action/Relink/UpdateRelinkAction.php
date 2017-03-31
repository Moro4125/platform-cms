<?php
/**
 * Class UpdateRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UpdateRelinkAction
 * @package Action\Relink
 *
 * @method \Moro\Platform\Model\Implementation\Relink\ServiceRelink getService()
 * @method \Moro\Platform\Model\Implementation\Relink\EntityRelink getEntity()
 */
class UpdateRelinkAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_RELINK;
	public $template    = '@PlatformCMS/admin/content/relink-update.html.twig';
	public $route       = 'admin-content-relink-update';
	public $routeIndex  = 'admin-content-relink';
	public $routeDelete = 'admin-content-relink-delete';

	public $useTags = false;

	/**
	 * @param Request $request
	 * @return AbstractUpdateAction
	 */
	public function setRequest(Request $request)
	{
		if ($request->isMethod('POST') && $request->request->has('admin_update'))
		{
			$form = $request->request->get('admin_update');
			$host = $request->getSchemeAndHttpHost();

			if (isset($form['href']) && strncmp($form['href'], $host, strlen($host)) === 0)
			{
				$form['href'] = substr($form['href'], strlen($host));
				$request->request->set('admin_update', $form);
			}
		}

		return parent::setRequest($request);
	}

	/**
	 * @return void
	 */
	protected function _applyForm()
	{
		parent::_applyForm();

		$application = $this->getApplication();
		$entity = $this->getEntity();

		// Выставление меток для обновления страниц, на которых была использована данная перелинковка.
		$routes = $application->getServiceRoutes();
		$tags = ['link-'.$entity->getId()];

		$routes->setCompileFlagForTag($tags);
	}

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$host = $this->getRequest()->getSchemeAndHttpHost();
		$href = $this->getEntity()->getHref();

		if (strncmp($href, $host, strlen($host)) === 0)
		{
			$msg = 'Пожалуйста, не указывайте имя хоста для ссылок на страницы данного сайта.';
			$this->getApplication()->getServiceFlash()->alert($msg);
		}

		$parameters = parent::_getViewParameters();

		$parameters['title'] = $this->getEntity()->getName().' - Редактирование правила перелинковки';

		return $parameters;
	}
}