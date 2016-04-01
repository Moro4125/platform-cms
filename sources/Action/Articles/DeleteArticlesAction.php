<?php
/**
 * Class DeleteArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractDeleteAction;
use \Moro\Platform\Model\Implementation\Content\Decorator\HeadingDecorator;
use \Moro\Platform\Application;

/**
 * Class DeleteArticlesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent[] getEntities()
 */
class DeleteArticlesAction extends AbstractDeleteAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $template    = '@PlatformCMS/admin/content/article-delete.html.twig';
	public $route       = 'admin-content-articles-delete';
	public $routeIndex  = 'admin-content-articles';

	/**
	 * @var array
	 */
	protected $_diffBlackKeys = ['parameters.chain'];

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doDelete()
	{
		// Выставление меток для обновления страниц, на которых были использованы удаляемые материалы.
		$tags = [];
		$entities = $this->getEntities();
		$application = $this->getApplication();

		foreach ($entities as $entity)
		{
			$tags[] = 'art-'.$entity->getId();
		}

		if ($application->getOption('content.headings'))
		{
			foreach ($entities as $entity)
			{
				$entity = HeadingDecorator::newInstance($application, $entity);

				if ($heading = $entity->getHeading())
				{
					$tags[] = 'heading-'.$heading;
				}
			}
		}

		$application->getServiceRoutes()->setCompileFlagForTag(array_unique($tags));

		// Вызов родительского метода для удаления материалов.
		return parent::_doDelete();
	}
}