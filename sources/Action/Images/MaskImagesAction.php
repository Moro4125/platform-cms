<?php
/**
 * Class MaskImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractSilentAction;
use \Silex\Application as SilexApplication;
use \Symfony\Component\HttpFoundation\Response;
use \Moro\Platform\Model\Implementation\File\FileInterface;
use \Moro\Platform\Application;

/**
 * Class MaskImagesAction
 * @package Moro\Platform\Action\Images
 */
class MaskImagesAction extends AbstractSilentAction
{
	public $serviceCode = Application::SERVICE_FILE;
	public $route       = 'admin-content-images-mask';
	public $routeIndex  = 'admin-content-images';

	/**
	 * @param array|FileInterface[] $entities
	 * @return $this
	 */
	protected function _setEntities(array $entities)
	{
		/** @var \Moro\Platform\Model\Implementation\File\ServiceFile $service */
		$service = $this->getService();
		$this->_entities = [];

		foreach ($entities as $item)
		{
			foreach ($service->selectByHash($item->getHash()) as $entity)
			{
				$this->_entities[] = $entity;
			}
		}

		return $this;
	}

	/**
	 * @return null|Response
	 */
	protected function _execute()
	{
		/** @var \Moro\Platform\Model\Implementation\File\ServiceFile $service */
		$service = $this->getService();
		$application = $this->getApplication();
		$applied = [];
		$files = 0;

		/** @var FileInterface $entity */
		foreach ($this->getEntities() as $entity)
		{
			$parameters = $entity->getParameters();

			if (empty($this->_flag) && empty($parameters['hide_mask']))
			{
				$parameters['hide_mask'] = true;
				$entity->setParameters($parameters);
				$service->commit($entity);

				$applied[$entity->getHash()] = true;
			}
			elseif ($this->_flag && (!empty($parameters['hide_mask']) || !isset($parameters['hide_mask'])))
			{
				$parameters['hide_mask'] = false;
				$entity->setParameters($parameters);
				$service->commit($entity);

				$applied[$entity->getHash()] = true;
			}
		}

		foreach (array_keys($applied) as $hash)
		{
			$files += $service->recreateImageFiles($application, $hash);
		}

		$message = sprintf('Изменения коснулись %1$s изображений и %2$s файлов', count($applied), $files);
		$this->getApplication()->getServiceFlash()->info($message);
	}
}