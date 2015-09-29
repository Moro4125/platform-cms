<?php
/**
 * Class DeleteRelinkAction
 */
namespace Moro\Platform\Action\Relink;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Application;

/**
 * Class DeleteRelinkAction
 * @package Action\Relink
 *
 * @method \Moro\Platform\Model\Implementation\Relink\ServiceRelink getService()
 * @method \Moro\Platform\Model\Implementation\Relink\EntityRelink getEntity()
 */
class DeleteRelinkAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_RELINK;
	public $template    = '@PlatformCMS/admin/content/relink-delete.html.twig';
	public $route       = 'admin-content-relink-delete';
	public $routeIndex  = 'admin-content-relink';

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doDelete()
	{
		// Выставление меток для обновления страниц, на которых были использованы удаляемые ссылки.
		$tags = [];
		$entities = $this->getEntities();
		$application = $this->getApplication();

		foreach ($entities as $entity)
		{
			$tags[] = 'link-'.$entity->getId();
		}

		$application->getServiceRoutes()->setCompileFlagForTag(array_unique($tags));

		// Вызов родительского метода для удаления записи правила перелинковки.
		return parent::_doDelete();
	}
}