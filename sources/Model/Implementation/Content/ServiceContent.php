<?php
/**
 * Class ServiceContent
 */
namespace Moro\Platform\Model\Implementation\Content;
use \Moro\Platform\Application;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Accessory\Parameters\Chain\ChainServiceInterface;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Moro\Platform\Model\Implementation\File\ServiceFile;
use \Moro\Platform\Form\Index\AbstractIndexForm;
use \Moro\Platform\Form\ContentForm;
use \Moro\Platform\Form\AjaxUploadForm;
use \Symfony\Component\Form\Form;
use \Symfony\Component\HttpFoundation\Request;
use \PDO;
use \Exception;

/**
 * Class ServiceContent
 * @package Model\Content
 *
 * @method EntityContent[] getEntitiesById(array $idList, $flags)
 */
class ServiceContent extends AbstractService implements ContentActionsInterface, TagsServiceInterface, ChainServiceInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Chain\ChainServiceTrait;
	use \Moro\Platform\Model\Accessory\LockTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	const MD_LINK_MASK = '{\\[([^\\]^]+)\\]}';

	/**
	 * @var string
	 */
	protected $_table = 'content_article';

	/**
	 * @var \Moro\Platform\Model\Implementation\File\ServiceFile
	 */
	protected $_serviceFile;

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
		$this->_traits[static::class][self::STATE_DELETE_FINISHED] = '_afterDelete';
	}

	/**
	 * @param array $tags
	 * @param EntityContent $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		$parameters = $entity->getParameters();

		if (empty($parameters['lead']) && empty($entity->getIcon()))
		{
			$tags[] = normalizeTag('флаг: без анонса');
		}

		if (empty($parameters['link']))
		{
			if (empty($parameters['gallery']) || !is_array($parameters['gallery']) || count($parameters['gallery']) < 1)
			{
				$tags[] = normalizeTag('флаг: без картинок');
			}

			if (empty($parameters['gallery_text']) || !is_string($parameters['gallery_text']) || !trim($parameters['gallery_text']))
			{
				$tags[] = normalizeTag('флаг: без описания');
			}
		}

		if ($hash = $entity->getIcon())
		{
			$tags[] = '+img:'.$hash;
		}

		if (!empty($parameters['gallery']))
		{
			foreach ($parameters['gallery'] as $hash)
			{
				$tags[] = '+img:'.$hash;
			}
		}

		return $tags;
	}

	/**
	 * @param ContentInterface $entity
	 */
	protected function _afterDelete(ContentInterface $entity)
	{
		foreach ($this->_serviceFile->selectByKind('a'.$entity->getId()) as $file)
		{
			$this->_serviceFile->deleteEntityById($file->getId());
		}
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
	 * @return ContentInterface
	 */
	public function createEntity()
	{
		$entity = $this->_newEntityFromArray([], EntityInterface::FLAG_GET_FOR_UPDATE);

		$this->commit($entity);
		return $entity;
	}

	/**
	 * @param string $code
	 * @param null|int $flags
	 * @param null|bool $withoutException
	 * @return ContentInterface|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getEntityByCode($code, $withoutException = null, $flags = null)
	{
		assert(is_string($code));

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(ContentInterface::PROP_CODE.'=?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ (string)$code ]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			return $this->_newEntityFromArray($record, $flags);
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'CODE', $code);
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_CODE);
		}

		return null;
	}

	/**
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|integer $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Moro\Platform\Model\EntityInterface[]
	 */
	public function selectEntitiesForAdminListForm($offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		return $this->selectEntities($offset, $count, $order, $where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		return $this->getCount($where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);
	}

	/**
	 * @return ContentInterface
	 */
	public function createNewEntityWithId()
	{
		return $this->createEntity();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Symfony\Component\Form\Form
	 */
	public function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list = $this->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		$service = $application->getServiceFormFactory();
		$builder = $service->createBuilder(new AbstractIndexForm($list), array_fill_keys(array_keys($list), false));

		return $builder->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param ContentInterface|\Moro\Platform\Model\EntityInterface $entity
	 * @param Request $request
	 * @return Form
	 */
	public function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request)
	{
		$method = $request->getMethod();

		$args = $entity->getParameters();
		$tags = @$request->get('admin_update')['tags'] ?: [];
		$tags = array_unique(array_merge($tags, isset($args['tags']) ? $args['tags'] : []));

		$data = [
			'name' => $entity->getName(),
			'code' => $entity->getCode(),
			'icon' => $entity->getIcon(),
			'lead' => isset($args['lead']) ? $args['lead'] : '',
			'external' => isset($args['link']) ? $args['link'] : '',
			'tags' => isset($args['tags']) ? $args['tags'] : [],
			'gallery' => isset($args['gallery']) ? $args['gallery'] : [],
			'gallery_text' => isset($args['gallery_text']) ? $args['gallery_text'] : '',
			'articles' => isset($args['articles']) ? $args['articles'] : [],
		];

		$serviceFile = $application->getServiceFile();
		$hasIcon = !empty($data['icon']);
		$data['icon'] = ($hasIcon && $serviceFile->existsByHashAndKind($data['icon'], '1x1')) ? $data['icon'] : null;
		$count = count($data['gallery']);
		$data['gallery'] = $serviceFile->filterHashList($data['gallery']);

		if ($method == Request::METHOD_GET && ($count > count($data['gallery']) || $hasIcon && empty($data['icon'])))
		{
			$application->getServiceFlash()->alert('В материале были скрыты ссылки на удаленные изображения.');
		}

		$count = count($data['articles']);
		$data['articles'] = $this->filterIdList($data['articles']);

		if ($method == Request::METHOD_GET && $count > count($data['articles']))
		{
			$application->getServiceFlash()->alert('В материале были скрыты ссылки на другие удаленные материалы.');
		}

		$id = $entity->getId();

		$data['gallery_text'] = preg_replace_callback(self::MD_LINK_MASK, function($match) use ($id, $data, $serviceFile) {
			if (in_array($match[1], $data['gallery']) && $image = $serviceFile->getByHashAndKind($match[1], '1x1', true))
			{
				return '['.$image->getName().']';
			}
			elseif (!strncmp($match[1], 'id:', 3) && $code = array_search((int)substr($match[1],3), $data['articles']))
			{
				return '['.$code.']';
			}
			elseif ($attachment = $serviceFile->getByHashAndKind($match[1], "a$id", true))
			{
				return '['.$attachment->getName().']';
			}

			return $match[0];
		}, $data['gallery_text']);

		$form = new ContentForm($entity->getId(), $tags);

		return $application->getServiceFormFactory()->createBuilder($form, $data)->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param ContentInterface|\Moro\Platform\Model\EntityInterface $entity
	 * @param Form $form
	 */
	public function applyAdminUpdateForm(Application $application, EntityInterface $entity, Form $form)
	{
		$data = $form->getData();
		$parameters = $entity->getParameters();
		$serviceFile = $application->getServiceFile();

		$this->_connection->beginTransaction();

		try
		{
			$id = $entity->getId();
			$data['articles'] = $this->filterIdList($data['articles']);

			$data['gallery_text'] = preg_replace_callback(self::MD_LINK_MASK, function($match) use ($id, $data, $serviceFile) {
				if (!empty($data['articles'][$match[1]]))
				{
					return '[id:'.$data['articles'][$match[1]].']';
				}
				elseif ($list = $serviceFile->selectEntities(null, null, null, 'name', $match[1]))
				{
					/** @var \Moro\Platform\Model\Implementation\File\EntityFile $file */
					foreach ($list as $file)
					{
						$kind = $file->getKind();

						if ($kind == '1x1' && in_array($file->getHash(), $data['gallery']) || $kind == "a$id")
						{
							return '['.$file->getHash().']';
						}
					}
				}

				return $match[0];
			}, $data['gallery_text']);

			$parameters['lead']         = $data['lead'];
			$parameters['link']         = $data['external'];
			$parameters['tags']         = array_values($data['tags']);
			$parameters['gallery']      = $data['gallery'];
			$parameters['gallery_text'] = $data['gallery_text'];
			$parameters['articles']     = array_values($data['articles']);
			$parameters['comment']      = $data['comment'];

			$parameters['attachments']  = [];
			foreach ($this->selectAttachmentByEntity($entity) as $attachment)
			{
				$parameters['attachments'][] = $attachment->getName();
			}

			$entity->setName($data['name']);
			$entity->setCode($data['code']);
			$entity->setIcon($data['icon']);
			$entity->setParameters(array_filter($parameters));

			$this->commit($entity);
			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи материала были успешно сохранены.');
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
		}


		unset($application);
	}

	/**
	 * @param Application $app
	 * @param null|EntityInterface $entity
	 * @return Form
	 */
	public function createAdminUploadForm(Application $app, EntityInterface $entity = null)
	{
		$action = $app->url('admin-content-articles-attach', ['id' => $entity ? $entity->getId() : 0]);
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

						if ($entity = $service->getByHashAndKind($hash, "a$id", true, false))
						{
							$entity->setName($name);
						}
						else
						{
							$entity = $service->createEntity($hash, "a$id");
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
	 * @param array $list
	 * @return array
	 */
	public function filterIdList(array $list)
	{
		$query = $this->_connection->createQueryBuilder()->select('code')->from($this->_table)->where('id = ?')->getSQL();
		$statement = $this->_connection->prepare($query);
		$result = [];

		foreach ($list as $id)
		{
			if ($statement->execute([(int)$id]) && $code = $statement->fetchColumn(0))
			{
				$result[$code] = (int)$id;
			}
		}

		return $result;
	}

	/**
	 * @param ContentInterface $entity
	 * @return \Moro\Platform\Model\Implementation\File\EntityFile[]
	 */
	public function selectAttachmentByEntity(ContentInterface $entity)
	{
		return $this->_serviceFile->selectByKind('a'.$entity->getId());
	}

	/**
	 * @param array|EntityInterface $a
	 * @param array|EntityInterface $b
	 * @return array
	 */
	public function calculateDiff($a, $b)
	{
		$diff = parent::calculateDiff($a, $b);

		if (!empty($diff['code']) && strncmp($diff['code'][0], 'temp_', 5) === 0)
		{
			$diff['code'][0] = null;
		}

		return $diff;
	}
}