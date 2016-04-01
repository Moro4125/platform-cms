<?php
/**
 * Class ServiceTags
 */
namespace Moro\Platform\Model\Implementation\Tags;

use \Moro\Platform\Application;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use Moro\Platform\Form\Index\AbstractIndexForm;
use \Moro\Platform\Form\TagsForm;
use \Symfony\Component\Form\Form;
use \Symfony\Component\HttpFoundation\Request;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Exception;
use \PDO;

/**
 * Class ServiceTags
 * @package Model\Content
 *
 * @method EntityTags[] selectEntities($offset = null, $count = null, $orderBy = null, $filter = null, $value = null)
 */
class ServiceTags extends AbstractService implements ContentActionsInterface, TagsServiceInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
	use \Moro\Platform\Model\Accessory\LockTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'content_tags';

	/**
	 * @var array
	 */
	protected $_tagsKind = ['обычный',  'синоним', 'системный'];

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
		$this->_tagsKind = array_map('normalizeTag', $this->_tagsKind);
	}

	/**
	 * @param array $tags
	 * @param EntityTags $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		$tags = array_diff($tags, $this->_tagsKind);
		$tags[] = $this->_tagsKind[$entity->getKind()];
		$parameters = $entity->getParameters();

		if (empty($parameters['lead']))
		{
			$tags[] = normalizeTag('флаг: без пояснения');
		}

		return $tags;
	}

	/**
	 * @return TagsInterface
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
	 * @return TagsInterface|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getEntityByCode($code, $withoutException = null)
	{
		assert(is_string($code));

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(TagsInterface::PROP_CODE.'=?')->getSQL();
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
	 * @return TagsInterface
	 */
	public function createNewEntityWithId()
	{
		return $this->createEntity();
	}

	/**
	 * @param Application $application
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
	 * @param Application $application
	 * @param TagsInterface|EntityInterface $entity
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
			'kind' => $entity->getKind(),
			'code' => $entity->getCode(),
			'lead' => isset($args['lead']) ? $args['lead'] : '',
			'tags' => isset($args['tags']) ? $args['tags'] : [],
		];

		return $application->getServiceFormFactory()->createBuilder(new TagsForm($entity->getId(), $tags), $data)->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param TagsInterface|\Moro\Platform\Model\EntityInterface $entity
	 * @param Form $form
	 */
	public function applyAdminUpdateForm(Application $application, EntityInterface $entity, Form $form)
	{
		$data = $form->getData();
		$parameters = $entity->getParameters();

		$this->_connection->beginTransaction();

		try
		{
			$parameters['tags']    = array_values($data['tags']);
			$parameters['lead']    = $data['lead'];
			$parameters['comment'] = $data['comment'];

			$entity->setName($data['name']);
			$entity->setKind($data['kind']);
			$entity->setParameters($parameters);

			$this->commit($entity);
			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи ярлыка были успешно сохранены.');
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
		}

		unset($application);
	}
}