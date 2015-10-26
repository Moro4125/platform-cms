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
use \Moro\Platform\Form\ContentListForm;
use \Moro\Platform\Form\ContentForm;


use \Symfony\Component\Form\Form;
use \Symfony\Component\HttpFoundation\Request;
use \PDO;
use \Exception;

/**
 * Class ServiceContent
 * @package Model\Content
 */
class ServiceContent extends AbstractService implements ContentActionsInterface, TagsServiceInterface, ChainServiceInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Chain\ChainServiceTrait;
	use \Moro\Platform\Model\Accessory\LockTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'content_article';

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
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

		return $tags;
	}

	/**
	 * @return ContentInterface
	 */
	public function createEntity()
	{
		$entity = $this->_newEntityFromArray([]);

		$this->commit($entity);
		return $entity;
	}

	/**
	 * @param string $code
	 * @param null|bool $withoutException
	 * @return ContentInterface|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getEntityByCode($code, $withoutException = null)
	{
		assert(is_string($code));

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(ContentInterface::PROP_CODE.'=?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ (string)$code ]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			return $this->_newEntityFromArray($record);
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
		return $this->selectEntities($offset, $count, $order, $where, $value);
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		return $this->getCount($where, $value);
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
		$builder = $service->createBuilder(new ContentListForm($list), array_fill_keys(array_keys($list), false));

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
		];

		if ($request->getMethod() == Request::METHOD_GET)
		{
			foreach (array_filter(array_merge([$data['icon']], $data['gallery'])) as $hash)
			{
				if (!$application->getServiceFile()->existsByHashAndKind($hash, '1x1'))
				{
					$application->getServiceFlash()->alert('В материале были скрыты ссылки на удаленные изображения.');
					break;
				}
			}
		}

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

		$this->_connection->beginTransaction();

		try
		{
			$parameters['lead']         = $data['lead'];
			$parameters['link']         = $data['external'];
			$parameters['tags']         = array_values($data['tags']);
			$parameters['gallery']      = $data['gallery'];
			$parameters['gallery_text'] = $data['gallery_text'];

			$entity->setName($data['name']);
			$entity->setCode($data['code']);
			$entity->setIcon($data['icon']);
			$entity->setParameters($parameters);

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
}