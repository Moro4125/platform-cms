<?php
/**
 * Class SetTopArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractSetTopAction;
use \Moro\Platform\Model\Implementation\Content\Decorator\HeadingDecorator;
use \Moro\Platform\Application;

/**
 * Class SetTopArticlesAction
 * @package Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 * @method \Moro\Platform\Model\Implementation\Content\EntityContent getEntity()
 */
class SetTopArticlesAction extends AbstractSetTopAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $route       = 'admin-content-articles-set-top';
	public $routeIndex  = 'admin-content-articles';

	/**
	 * @throws \Exception
	 */
	public function execute()
	{
		parent::execute();

		$app = $this->getApplication();
		$entity = $this->getEntity();

		// Выставление меток для обновления страниц, на которых был использован данный материал.
		$tags = ['art-'.$entity->getId()];

		if ($app->getOption('content.headings') && $decorator = HeadingDecorator::newInstance($app, $entity))
		{
			$tags[] = 'heading-'.$decorator->getHeading() ?: 'draft';
		}

		$app->getServiceRoutes()->setCompileFlagForTag(array_unique($tags));
	}
}