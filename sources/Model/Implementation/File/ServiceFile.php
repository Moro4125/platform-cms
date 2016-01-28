<?php
/**
 * Class ServiceFile
 */
namespace Moro\Platform\Model\Implementation\File;
use \Moro\Platform\Application;
use \Moro\Platform\Form\FilesUploadForm;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Form\Index\ImagesIndexForm;
use \Moro\Platform\Form\ImageUpdateForm;
use \Imagine\Image\Box;
use \Imagine\Image\Point;
use \Symfony\Component\Form\Form;
use \Symfony\Component\HttpFoundation\Request;
use \Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use \DirectoryIterator;
use \Exception;
use \Symfony\Component\Intl\Exception\NotImplementedException;
use \PDO;

/**
 * Class ServiceFile
 * @package Model\File
 *
 * @method FileInterface getEntityById($id, $withoutException = null)
 */
class ServiceFile extends AbstractService implements ContentActionsInterface, TagsServiceInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
	use \Moro\Platform\Model\Accessory\LockTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'content_file';

	/**
	 * @var array
	 */
	protected $_kinds = ['1x1' => 1, '16x10' => 1.6, '10x16' => 0.625, '16x5' => 3.2, '32x5' => 6.4];

	/**
	 * @var EntityFile[]
	 */
	protected $_cachedItemsList;

	/**
	 * @var string
	 */
	protected $_storagePath;

	/**
	 * @var array
	 */
	protected $_specialTags = [];

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
		$this->_specialTags = array_merge($this->_specialTags, array_keys($this->_kinds));
		$this->_specialTags = array_map('normalizeTag', $this->_specialTags);
	}

	/**
	 * @param array $tags
	 * @param EntityFile $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		$tags = array_diff($tags, $this->_specialTags);
		$kind = $entity->getKind();
		$kind != '1x1' && ($tags[] = $kind);

		if (isset($this->_kinds[$kind]) && $kind != '1x1' && $this->getByHashAndKind($entity->getHash(), '1x1', true))
		{
			$tags = [];
		}

		if ($kind == '1x1')
		{
			$parameters = $entity->getParameters();
			$ratio = (@$parameters['width'] ?: 1) / (@$parameters['height'] ?: 1);

			foreach ($this->_kinds as $kind2 => $ratio2)
			{
				if (abs($ratio - $ratio2) <= 0.007)
				{
					$tags[] = $kind2;
				}
			}

			foreach ($this->selectByHash($entity->getHash()) as $image)
			{
				if (($kind3 = $image->getKind()) && $kind3 != '1x1' && !in_array($kind3, $tags))
				{
					$tags[] = $kind3;
				}
			}
		}

		return $tags;
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function setStoragePath($path)
	{
		$this->_storagePath = $path.DIRECTORY_SEPARATOR.'upload';
		return $this;
	}

	/**
	 * @param string $hash
	 * @param string $kind
	 * @param null|bool $notCommit
	 * @return FileInterface
	 */
	public function createEntity($hash, $kind, $notCommit = null)
	{
		$entity = $this->_newEntityFromArray([EntityFile::PROP_HASH => $hash, EntityFile::PROP_KIND => $kind ]);

		$notCommit || $this->commit($entity);
		return $entity;
	}

	/**
	 * @param string $hash
	 * @param string $kind
	 * @param null|bool $withoutException
	 * @param null|bool $createOnFail
	 * @return FileInterface|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByHashAndKind($hash, $kind, $withoutException = null, $createOnFail = null)
	{
		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where('hash = ?')->andWhere('kind = ?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ $hash, $kind]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			return $this->_newEntityFromArray($record);
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'hash and kind', "$hash, $kind");
			throw new EntityNotFoundException($message, 0);
		}

		if (empty($createOnFail))
		{
			return null;
		}

		return $this->createEntity($hash, $kind, true);
	}

	/**
	 * @param string $hash
	 * @param string $kind
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function existsByHashAndKind($hash, $kind)
	{
		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('count(*)')->from($this->_table)->where('hash = ?')->andWhere('kind = ?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		return ($statement->execute([ $hash, $kind ]) && (int)$statement->fetchColumn());
	}

	/**
	 * @param string $hash
	 * @return \Moro\Platform\Model\Implementation\File\EntityFile[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectByHash($hash)
	{
		$result = [];

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where('hash = ?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		foreach ($statement->execute([ $hash ]) ? $statement->fetchAll(PDO::FETCH_ASSOC) : [] as $record)
		{
			$result[] = $this->_newEntityFromArray($record);
		}

		return $result;
	}

	/**
	 * @param string $hash
	 * @return string
	 */
	public function getPathForHash($hash)
	{
		return $this->_storagePath.DIRECTORY_SEPARATOR.substr($hash, -2).DIRECTORY_SEPARATOR.$hash;
	}

	/**
	 * @return array
	 */
	public function getKinds()
	{
		return $this->_kinds;
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
		$where === '~code' && $where = '~hash';
		$order === 'code'  && $order = 'name';

		is_array($where) && (false !== $index = array_search('~code', $where, true)) && ($where[$index] = '~hash');

		return $this->selectEntities($offset, $count, $order, $where, $value);
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		$where === '~code' && $where = '~hash';
		is_array($where) && (false !== $index = array_search('~code', $where, true)) && ($where[$index] = '~hash');

		return $this->getCount($where, $value);
	}

	/**
	 * @param string $hash
	 * @param string $kind
	 * @param null|bool $withoutException
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws EntityNotFoundException
	 */
	public function deleteByHashAndKind($hash, $kind, $withoutException = null)
	{
		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->delete($this->_table)
			->andwhere(FileInterface::PROP_HASH.'=?')
			->andWhere(FileInterface::PROP_KIND.'=?')
			->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ (string)$hash, (string)$kind ]) && $statement->rowCount())
		{
			return true;
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'hash and kind', "$hash, $kind");
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_ID);
		}

		return false;
	}

	/**
	 * @throws NotImplementedException
	 */
	public function createNewEntityWithId()
	{
		throw new NotImplementedException(__METHOD__);
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return Form
	 */
	public function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list = $this->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		$service = $application->getServiceFormFactory();
		$builder = $service->createBuilder(new ImagesIndexForm($list, true, $application), array_fill_keys(array_keys($list), false));

		return $builder->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @return Form
	 */
	public function createAdminUploadsForm(Application $application)
	{
		$service = $application->getServiceFormFactory();
		$builder = $service->createBuilder(new FilesUploadForm($application->url('admin-content-images-upload')));

		return $builder->getForm();
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getHashForFile($path)
	{
		return convertSha1toX32(sha1_file($path));
	}

	/**
	 * @param Application $application
	 * @param Form $form
	 * @return array
	 */
	public function applyAdminUploadForm(Application $application, Form $form)
	{
		$idList = [];
		$this->_connection->beginTransaction();

		try
		{
			foreach ($form->getData() as $name => $value)
			{
				if ($name == 'uploads')
				{
					/** @var \Symfony\Component\HttpFoundation\File\UploadedFile $object */
					foreach ((array)$value as $object)
					{
						$hash = $this->getHashForFile($object->getPathname());
						$file = $this->getPathForHash($hash);
						$file = file_exists($file) ? $object : $object->move(dirname($file), basename($file));

						if ($entity = $this->getByHashAndKind($hash, '1x1', true, false))
						{
							$name = $object->getClientOriginalName();
							$hash = $entity->getSmallHash();
							$message = sprintf('Для файла "%1$s" уже была запись в БД (%2$s).', $name, $hash);
							$application->getServiceFlash()->alert($message);
						}
						else
						{
							$name    = $object->getClientOriginalName();
							$image   = $application->getServiceImagine()->open($file);
							$width   = $image->getSize()->getWidth();
							$height  = $image->getSize()->getHeight();
							$minSize = min($width, $height);

							$entity = $this->createEntity($hash, '1x1');
							$entity->setName(substr($name, 0, strrpos($name, '.') ?: strlen($name)));
							$entity->setParameters([
								'size'   => $file->getSize(),
								'width'  => $width,
								'height' => $height,
								'crop_x' => floor(($width - $minSize) / 2),
								'crop_y' => 0,
								'crop_w' => $minSize,
								'crop_h' => $minSize,
							]);
						}

						$this->commit($entity);
						$idList[] = $entity->getId();
					}
				}
			}

			$this->_connection->commit();
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
		}

		return $idList;
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param FileInterface|\Moro\Platform\Model\EntityInterface $entity
	 * @param Request $request
	 * @return Form
	 */
	public function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request)
	{
		$args = $entity->getParameters();
		$tags = @$request->get('admin_update')['tags'] ?: [];
		$tags = array_unique(array_merge($tags, isset($args['tags']) ? $args['tags'] : []));
		$data = [
			'name' => $entity->getName(),
			'lead' => isset($args['lead']) ? $args['lead'] : '',
			'tags' => isset($args['tags']) ? $args['tags'] : [],
		];

		$useWatermark = !empty($application->getOption('images.watermark'));
		$useMask      = !empty($application->getOption('images.mask1'));

		$defaultWatermark = $application->getOption('images.default.watermark');
		$defaultHideMask  = $application->getOption('images.default.hide_mask');

		foreach (array_keys($this->_kinds) as $kind)
		{
			$data['watermark'.$kind] = $useWatermark ? $defaultWatermark : 0;
			$data['hide_mask'.$kind] = empty($useMask) || $defaultHideMask;
		}

		foreach ($this->selectByHash($entity->getHash()) as $item)
		{
			if (($kind = $item->getKind()) && in_array($kind, array_keys($this->_kinds)))
			{
				$options = $item->getParameters();

				$data['crop'.$kind.'_a'] = true;
				$data['crop'.$kind.'_x'] = $options['crop_x'];
				$data['crop'.$kind.'_y'] = $options['crop_y'];
				$data['crop'.$kind.'_w'] = $options['crop_w'];
				$data['crop'.$kind.'_h'] = $options['crop_h'];

				isset($options['watermark']) && ($data['watermark'.$kind] = $options['watermark']);
				isset($options['hide_mask']) && ($data['hide_mask'.$kind] = (bool)$options['hide_mask']);
			}
		}

		$service = $application->getServiceFormFactory();
		$form = new ImageUpdateForm(array_keys($this->_kinds), $tags, $useWatermark, $useMask);
		$builder = $service->createBuilder($form, $data);

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param FileInterface|\Moro\Platform\Model\EntityInterface $entity
	 * @param Form $form
	 */
	public function applyAdminUpdateForm(Application $application, EntityInterface $entity, Form $form)
	{
		$data = $form->getData();
		$hash = $entity->getHash();
		$imgOptions = $entity->getParameters();

		$this->_connection->beginTransaction();

		try
		{
			$useWatermark = !empty($application->getOption('images.watermark'));
			$useMask      = !empty($application->getOption('images.mask1'));

			foreach (array_keys($this->_kinds) as $kind)
			{
				if ($data['crop'.$kind.'_a'] || $kind == '1x1')
				{
					$item = $this->getByHashAndKind($hash, $kind, true, true);
					$item->getId() === $entity->getId() && $item = $entity;
					$options = array_merge($imgOptions, $item->getParameters());

					if ($kind == '1x1')
					{
						$item->setName($data['name']);
						$options['tags'] = array_values($data['tags']);
						$options['lead'] = $data['lead'];
					}
					else
					{
						unset($options['tags']);
						unset($options['lead']);
					}

					$options['crop_x'] = max(0, (int)$data['crop'.$kind.'_x']);
					$options['crop_y'] = max(0, (int)$data['crop'.$kind.'_y']);
					$options['crop_w'] = min((int)$data['crop'.$kind.'_w'], $options['width']  - $options['crop_x']);
					$options['crop_h'] = min((int)$data['crop'.$kind.'_h'], $options['height'] - $options['crop_y']);

					if ($useWatermark)
					{
						$options['watermark'] = min(max(0, (int)$data['watermark'.$kind]), 4);
					}

					if ($useMask)
					{
						$options['hide_mask'] = min(max(0, (int)$data['hide_mask'.$kind]), 1);
					}

					$item->setParameters($options);
					$this->commit($item);
				}
				else
				{
					$this->deleteByHashAndKind($hash, $kind, true);
				}
			}

			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи изображения были успешно сохранены.');
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
		}

		$this->recreateImageFiles($application, $hash);
	}

	/**
	 * @param Application $application
	 * @param string $hash
	 * @return int
	 */
	public function recreateImageFiles(Application $application, $hash)
	{
		$affectedFiles = 0;

		$url = $application->url('image', ['hash' => $hash, 'width' => 0, 'height' => 0, 'remember' => 0]);
		$url = substr($url, 0, strpos($url, '?') ?: strlen($url));
		$uri = substr($url, (strpos($url, 'index.php') ?: -9) + 9);
		$uri = substr($uri, strpos($uri, '/'));
		$dir = dirname($application->getOption('path.root').$uri);
		$pattern = '{^(?P<hash>'.$hash.')_(?P<width>\\d+)_(?P<height>\\d+)\\.(?P<format>jpg|png|gif)$}';

		/** @var \SplFileInfo $file */
		foreach (file_exists($dir) ? new DirectoryIterator($dir) : [] as $file)
		{
			if (preg_match($pattern, $file->getBasename(), $match))
			{
				$realPath = $file->getRealPath();
				$fileHash = sha1_file($realPath);
				$fileTime = filemtime($realPath);

				$match = array_intersect_key($match, array_fill_keys(array_filter(array_keys($match), 'is_string'), 0));
				$url = $application->url('image', $match);
				$tempFile = tempnam($dir, '_temp');

				$request = Request::create($url, 'GET', ['remember' => 0], [], [], $_SERVER);
				/** @var \Symfony\Component\HttpFoundation\Response $response */
				$response = $application->handle($request);

				if ($response->getStatusCode() == 200)
				{
					file_put_contents($tempFile, $response->getContent());
					@chmod($tempFile, 0644);
					rename($tempFile, $realPath);

					sha1_file($realPath) === $fileHash && touch($realPath, $fileTime);
					$affectedFiles++;
				}
			}
		}

		return $affectedFiles;
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param Form $form
	 * @param FileInterface $entity
	 * @param string $kind
	 * @return int
	 */
	public function applyAdminImageCopyForm(Application $application, Form $form, FileInterface $entity, $kind)
	{
		$data = $form->getData();
		$name = $entity->getName();
		$hash = $entity->getHash();
		$path = $this->getPathForHash($hash);
		$temp = tempnam(dirname($path), 'img');

		$x = max(0, (int)$data['crop'.$kind.'_x']);
		$y = max(0, (int)$data['crop'.$kind.'_y']);
		$w = min((int)$data['crop'.$kind.'_w'], $entity->getParameters()['width']  - $x);
		$h = min((int)$data['crop'.$kind.'_h'], $entity->getParameters()['height'] - $y);
		$minSize = min($w, $h);

		$image = $application->getServiceImagine()->open($path);
		$image->crop(new Point($x, $y), new Box($w, $h));
		$image->save($temp, ['format' => 'jpeg', 'jpeg_quality' => 100]);

		$hash = $this->getHashForFile($temp);
		$path = $this->getPathForHash($hash);
		@mkdir(dirname($path), 0755, true);
		rename($temp, $path);

		try
		{
			$entity = $this->createEntity($hash, '1x1', true);
			$entity->setName($name);
			$entity->setParameters([
				'size'   => filesize($path),
				'width'  => $w,
				'height' => $h,
				'crop_x' => floor(($w - $minSize) / 2),
				'crop_y' => 0,
				'crop_w' => $minSize,
				'crop_h' => $minSize,
			]);

			$this->commit($entity);
		}
		catch (UniqueConstraintViolationException $exception)
		{
			// Игнорируем исключение, так как этот случай предусмотрен алгоритмом.
			return $this->getByHashAndKind($hash, '1x1')->getId();
		}

		return $entity->getId();
	}

	/**
	 * @param array $list
	 * @return array
	 */
	public function filterHashList(array $list)
	{
		$query = $this->_connection->createQueryBuilder()->select('id')->from($this->_table)->where('hash=?')->getSQL();
		$statement = $this->_connection->prepare($query);
		$result = [];

		foreach ($list as $hash)
		{
			if ($statement->execute([$hash]) && $statement->fetchAll())
			{
				$result[] = $hash;
			}
		}

		return $result;
	}
}