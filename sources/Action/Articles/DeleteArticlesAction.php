<?php
/**
 * Class DeleteArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractDeleteAction;
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
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doDelete()
	{
		// Выставление меток для обновления страниц, на которых были использованы удаляемые материалы.
		$tags = [];
		$headings = [];
		$entities = $this->getEntities();
		$application = $this->getApplication();
		$serviceTags = $application->getServiceTags();

		foreach ($entities as $entity)
		{
			$tags[] = 'art-'.$entity->getId();

			foreach ($entity->getTags() as $tag)
			{
				if (false !== strpos($tag = normalizeTag($tag), 'раздел'))
				{
					$headings[] = $tag;
				}
			}
		}

		foreach (array_unique($headings) as $headingTag)
		{
			if ($tagEntity = $serviceTags->getEntityByCode($headingTag, true))
			{
				$tags = array_merge($tags, $tagEntity->getTags());
			}
		}

		$application->getServiceRoutes()->setCompileFlagForTag(array_unique($tags));

		// Вызов родительского метода для удаления материалов.
		return parent::_doDelete();
	}
}