<?php
/**
 * Class UpdateImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Application;

/**
 * Class UpdateImagesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\File\ServiceFile getService()
 * @method \Moro\Platform\Model\Implementation\File\EntityFile getEntity()
 */
class UpdateImagesAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_FILE;
	public $template    = '@PlatformCMS/admin/content/image-update.html.twig';
	public $route       = 'admin-content-images-update';
	public $routeIndex  = 'admin-content-images';
	public $routeDelete = 'admin-content-images-delete';

	/**
	 * @param int $id
	 * @return mixed
	 */
	protected function _doActions($id)
	{
		$application = $this->getApplication();
		$service     = $this->getService();
		$entity      = $this->getEntity();
		$form        = $this->getForm();

		foreach (array_keys($service->getKinds()) as $kind)
		{
			/** @noinspection PhpUndefinedMethodInspection */
			if ($form->get('copy'.$kind)->isClicked())
			{
				$query = $this->getRequest()->query->all();
				$query['id'] = $service->applyAdminImageCopyForm($application, $form, $entity, $kind) ?: $id;
				return $application->redirect($application->url($this->route, $query));
			}
		}

		return parent::_doActions($id);
	}

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		return array_merge(parent::_getViewParameters(), [
			'kinds' => $this->getService()->getKinds(),
		]);
	}

	/**
	 * @return void
	 */
	protected function _applyForm()
	{
		parent::_applyForm();

		$application = $this->getApplication();
		$service = $this->getService();
		$entity = $this->getEntity();

		// Выставление меток для обновления страниц, на которых был использовано данное изображение.
		$routes = $application->getServiceRoutes();
		$tags = ['img-'.substr($entity->getHash(), 0, 4)];

		foreach ($entity->getTags() as $tag)
		{
			if (false !== strpos($tag = normalizeTag($tag), 'раздел'))
			{
				foreach ($application->getServiceTags()->selectEntities(null, null, null, 'code', $tag) as $tagEntity)
				{
					$tags = array_merge($tags, $tagEntity->getTags());
				}
			}
		}

		$routes->setCompileFlagForTag(array_unique($tags));

		// Проверка на уникальность названия материала.
		foreach ($service->selectEntities(null, 2, null, ['name', 'kind'], [$entity->getName(), '1x1']) as $item)
		{
			if ($item->getId() != $entity->getId())
			{
				$message = sprintf('Название "%1$s" уже используется для другого изображения.', $entity->getName());
				$application->getServiceFlash()->alert($message);
				break;
			}
		}
	}
}