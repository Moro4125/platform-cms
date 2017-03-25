<?php
/**
 * Class FileAttachTrait
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Application;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Implementation\File\ServiceFile;
use \Moro\Platform\Form\AjaxUploadForm;
use \Symfony\Component\Form\Form;
use \Exception;

/**
 * Class FileAttachTrait
 * @package Moro\Platform\Model\Accessory
 *
 * @property \Doctrine\DBAL\Connection $_connection
 * @property string $_attachRoute
 * @property string $_idPrefix
 */
trait FileAttachTrait
{
	/**
	 * @var \Moro\Platform\Model\Implementation\File\ServiceFile
	 */
	protected $_serviceFile;

	/**
	 * @return array
	 */
	protected function ___initTraitFileAttach()
	{
		return [
			AbstractService::STATE_DELETE_FINISHED => '_afterDelete',
		];
	}

	/**
	 * @param ServiceFile $service
	 * @return $this
	 */
	public function setServiceFile(ServiceFile $service)
	{
		$this->_serviceFile = $service;
		return $this;
	}

	/**
	 * @param Application $app
	 * @param null|EntityInterface $entity
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminUploadForm(Application $app, EntityInterface $entity = null)
	{
		$action = $app->url($this->_attachRoute , ['id' => $entity ? $entity->getId() : 0]);
		return $app->getServiceFormFactory()->createBuilder(new AjaxUploadForm($action), [])->getForm();
	}

	/**
	 * @param Application $app
	 * @param Form $form
	 * @param int $id
	 * @return array
	 */
	public function applyAdminUploadForm(Application $app, Form $form, $id)
	{
		$idList = [];
		$this->_connection->beginTransaction();
		$service = $app->getServiceFile();

		try
		{
			foreach ($form->getData() as $key => $value)
			{
				if ($key == 'uploads')
				{
					/** @var \Symfony\Component\HttpFoundation\File\UploadedFile $object */
					foreach ((array)$value as $object)
					{
						if (!$path = $object->getPathname())
						{
							$originalName = $object->getClientOriginalName();
							$message = sprintf('Не удалось загрузить на сервер файл "%1$s".', $originalName);
							throw new \RuntimeException($message);
						}

						$hash = $service->getHashForFile($path);
						$file = $service->getPathForHash($hash);
						$file = file_exists($file) ? $object : $object->move(dirname($file), basename($file));

						$name = $object->getClientOriginalName();
						$name = strtr($name, ['[' => ' ', ']' => ' ']);
						$name = trim(preg_replace('{\\s+}', ' ', $name));

						if ($entity = $service->getByHashAndKind($hash, $this->_idPrefix.$id, true, false))
						{
							$entity->setName($name);
						}
						else
						{
							$entity = $service->createEntity($hash, $this->_idPrefix.$id);
							$entity->setName($name);
							$entity->setParameters([
								'size' => $file->getSize(),
							]);
						}

						$service->commit($entity);
						$idList[] = $entity->getId();
					}
				}
			}

			$this->_connection->commit();
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			return get_class($exception).': '.$exception->getMessage();
		}

		return $idList;
	}

	/**
	 * @param EntityInterface $entity
	 * @return \Moro\Platform\Model\Implementation\File\EntityFile[]
	 */
	public function selectAttachmentByEntity(EntityInterface $entity)
	{
		return $this->_serviceFile->selectByKind($this->_idPrefix.$entity->getId());
	}

	/**
	 * @param EntityInterface $entity
	 */
	protected function _afterDelete(EntityInterface $entity)
	{
		foreach ($this->_serviceFile->selectByKind($this->_idPrefix.$entity->getId()) as $file)
		{
			$this->_serviceFile->deleteEntityById($file->getId());
		}
	}
}