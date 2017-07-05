<?php
/**
 * Class ServiceSubscribers
 */
namespace Moro\Platform\Model\Implementation\Subscribers;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\LockTrait;
use \Moro\Platform\Model\Accessory\MonologServiceTrait;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtServiceTrait;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
use \Moro\Platform\Model\Accessory\Parameters\Star\StarServiceTrait;
use \Moro\Platform\Model\Implementation\History\HistoryInterface;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Moro\Platform\Form\SubscribersForm;
use \Moro\Platform\Form\Index\AbstractIndexForm;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Form\Form;
use \ArrayObject;
use \DateTime;
use \DateTimeZone;
use \PDO;
use \Exception;

/**
 * Class ServiceSubscribers
 * @package Moro\Platform\Model\Implementation\Subscribers
 *
 * @method SubscribersInterface getEntityById($id, $withoutException = null, $flags = null)
 */
class ServiceSubscribers extends AbstractService implements ContentActionsInterface, TagsServiceInterface
{
	use UpdatedByServiceTrait;
	use OrderAtServiceTrait;
	use TagsServiceTrait;
	use StarServiceTrait;
	use MonologServiceTrait;
	use LockTrait;

	protected $_table = 'users_subscribers';

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
		$this->_traits[static::class][HistoryInterface::STATE_TRY_MERGE_HISTORY] = '_mergeHistory';
	}

	/**
	 * @param array $tags
	 * @param EntitySubscribers $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		$tags[] = normalizeTag($entity->getActive() ? 'флаг: активен' : 'флаг: отключён');

		return $tags;
	}

	/**
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 */
	protected function _mergeHistory(ArrayObject $next, ArrayObject $prev)
	{
		foreach ($next->getArrayCopy() as $key => $value)
		{
			if ($key == 'parameters.tags')
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeList($key, $next, $prev);
			}
			elseif (in_array($key, ['name', 'email', 'active']))
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeSimple($key, $next, $prev);
			}
		}
	}

	/**
	 * @param string $email
	 * @param null|bool $withoutCommit
	 * @return SubscribersInterface
	 */
	public function createEntity($email, $withoutCommit = null)
	{
		$fields = [SubscribersInterface::PROP_EMAIL => $email];
		$entity = $this->_newEntityFromArray($fields, SubscribersInterface::FLAG_GET_FOR_UPDATE);

		$flags = SubscribersInterface::FLAG_GET_FOR_UPDATE;
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$withoutCommit || $this->commit($entity) || $entity = $this->getEntityById($entity->getId(), null, $flags);

		return $entity;
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
		$list  = $this->selectEntities($offset, $count, $order, $where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);

		if ($this->_userToken)
		{
			$user  = '+star:'.$this->_userToken->getUsername();
			$stars = $this->selectEntities(0, ceil($count / 3), '!updated_at', 'tag', $user, EntityInterface::FLAG_GET_FOR_UPDATE);
			$list  = array_merge($stars, $list);
		}

		return $list;
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		return $this->getCount($where, $value, SubscribersInterface::FLAG_GET_FOR_UPDATE);
	}

	/**
	 * @param null|string $email
	 * @param null|bool $withoutCommit
	 * @return SubscribersInterface
	 */
	public function createNewEntityWithId($email = null, $withoutCommit = null)
	{
		assert(is_string($email) && strlen($email));
		return $this->createEntity($email, $withoutCommit);
	}

	/**
	 * @param Application $application
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list = $this->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		$application->extend(AbstractIndexForm::class, function(AbstractIndexForm $form, $app) use ($list) {
			$form->setApplication($app);
			$form->setList($list);
			return $form;
		});

		$service = $application->getServiceFormFactory();
		$dataArr = array_fill_keys(array_keys($list), false);
		$builder = $service->createNamedBuilder('admin_list', AbstractIndexForm::class, $dataArr);

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param SubscribersInterface|EntityInterface $entity
	 * @param Request $request
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request)
	{
		$args = $entity->getParameters();
		$tags = @$request->get('admin_update')['tags'] ?: [];
		$tags = array_unique(array_merge($tags, isset($args['tags']) ? $args['tags'] : []));

		$data = [
			'name'   => $entity->getName(),
			'email'  => $entity->getEmail(),
			'active' => (bool)$entity->getActive(),
			'tags'   => isset($args['tags']) ? $args['tags'] : [],
		];

		$application->extend(SubscribersForm::class, function(SubscribersForm $form) use ($entity, $tags) {
			$form->setId($entity->getId());
			$form->setTags($tags);
			return $form;
		});

		$service = $application->getServiceFormFactory();
		$builder = $service->createNamedBuilder('admin_update', SubscribersForm::class, $data);

		return $builder->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param SubscribersInterface|\Moro\Platform\Model\EntityInterface $entity
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
			$parameters['comment'] = $data['comment'];

			$entity->setName($data['name']);
			$entity->setEmail($data['email']);
			$entity->setActive(!empty($data['active']));
			$entity->setOrderAt(time());
			$entity->setParameters($parameters);

			$this->commit($entity);
			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи подписчика были успешно сохранены.');
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
			($sentry = $application->getServiceSentry()) && $sentry->captureException($exception);
		}

		unset($application);
	}

	/**
	 * @return int
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getStartingLine()
	{
		$builder = $this->_connection->createQueryBuilder();
		$builder->select('MIN(order_at)')->from($this->_table);
		$builder->where(SubscribersInterface::PROP_ACTIVE.'=?');

		$statement = $this->_connection->prepare($builder->getSQL());

		$line = $statement->execute([1]) ? $statement->fetch(PDO::FETCH_COLUMN) : null;
		$line || $line = gmdate('Y-m-d H:i:s');
		$line = new DateTime($line, new DateTimeZone('UTC'));

		return $line->getTimestamp();
	}

	/**
	 * @param integer $finishLine
	 * @param null|int $count
	 * @return SubscribersInterface[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectEntitiesByFinishLine($finishLine, $count = null)
	{
		$builder = $this->_connection->createQueryBuilder();
		$builder->select('id')->from($this->_table);
		$builder->where(SubscribersInterface::PROP_ACTIVE.'=?')
			-> andWhere(SubscribersInterface::PROP_ORDER_AT.'<?');
		$builder->setMaxResults($count ?: 100);

		$statement = $this->_connection->prepare($builder->getSQL());

		$line = gmdate($this->_connection->getDatabasePlatform()->getDateTimeFormatString(), $finishLine);
		$list = [];

		foreach ($statement->execute([1,$line]) ? array_map('intval',$statement->fetchAll(PDO::FETCH_COLUMN)) : [] as $id)
		{
			$list[] = $this->getEntityById($id, null, SubscribersInterface::FLAG_SYSTEM_CHANGES);
		}

		return $list;
	}

	/**
	 * @param string $email
	 * @param null|bool $withoutException
	 * @param null|integer $flags
	 * @return SubscribersInterface|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getEntityByEMail($email, $withoutException = null, $flags = null)
	{
		assert(is_string($email));

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(SubscribersInterface::PROP_EMAIL.'=?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ (string)$email ]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			return $this->_newEntityFromArray($record, (int)$flags);
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'EMAIL', $email);
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_EMAIL);
		}

		return null;
	}
}