<?php
/**
 * Class SetTagArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractSetTagAction;
use \Moro\Platform\Application;

/**
 * Class SetTagArticlesAction
 * @package Action\Articles
 *
 * @method \Moro\Platform\Model\Implementation\Content\ServiceContent getService()
 */
class SetTagArticlesAction extends AbstractSetTagAction
{
	public $serviceCode = Application::SERVICE_CONTENT;
	public $template    = '@PlatformCMS/admin/content/abstract-set-tag.html.twig';
	public $route       = 'admin-content-articles-set-tag';
	public $routeIndex  = 'admin-content-articles';

	/**
	 * @param array $add
	 * @param array $del
	 * @throws \Exception
	 */
	protected function _doActionCommit($add, $del)
	{
		parent::_doActionCommit($add, $del);

		$app = $this->getApplication();

		// Выставление меток для обновления страниц, на которых могут отразится изменения.
		$tags = [];

		foreach ($this->getEntities() as $entity)
		{
			$tags[] = 'art-'.$entity->getId();
		}

		if ($app->getOption('content.headings'))
		{
			$behavior = $app->getBehaviorHeadings();

			foreach (array_unique(array_merge($add, $del)) as $tag)
			{
				if ($headingCode = $behavior->getHeadingCodeByTagName($tag))
				{
					$tags[] = 'heading-'.$headingCode;
				}
			}
		}

		$tags && $app->getServiceRoutes()->setCompileFlagForTag(array_unique($tags));
	}
}