<?php
/**
 * Class ServiceMessages
 */
namespace Moro\Platform\Model\Implementation\Messages;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\LockTrait;
use \Moro\Platform\Model\Accessory\MonologServiceTrait;
use \Moro\Platform\Model\Accessory\FileAttachTrait;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Accessory\Parameters\Star\StarServiceTrait;
use \Moro\Platform\Model\Implementation\History\HistoryInterface;
use \Moro\Platform\Form\MessagesForm;
use \Moro\Platform\Form\Index\MessagesIndexForm;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Form\Form;
use \ArrayObject;
use \DateTime;
use \DateTimeZone;
use \PDO;
use \Exception;

/**
 * Class ServiceMessages
 * @package Moro\Platform\Model\Implementation\Messages
 */
class ServiceMessages extends AbstractService implements ContentActionsInterface, TagsServiceInterface
{
	use UpdatedByServiceTrait;
	use TagsServiceTrait;
	use MonologServiceTrait;
	use LockTrait;
	use FileAttachTrait;
	use StarServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'content_messages';

	/**
	 * @var string
	 */
	protected $_attachRoute = 'admin-content-messages-attach';

	/**
	 * @var string
	 */
	protected $_idPrefix = 'm';

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
	 * @param EntityMessages $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		/** @var \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceBehavior $that */
		// $that = $this;

		unset($entity);
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
			if ($key == 'parameters.tags' || $key == 'parameters.attachments')
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeList($key, $next, $prev);
			}
			elseif ($key == 'name')
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeSimple($key, $next, $prev);
			}
			elseif ($key == 'parameters.text')
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergePatch($key, $next, $prev);
			}
		}
	}

	/**
	 * @param null|bool $withoutCommit
	 * @return MessagesInterface
	 */
	public function createEntity($withoutCommit = null)
	{
		$fields = [];
		$entity = $this->_newEntityFromArray($fields, MessagesInterface::FLAG_GET_FOR_UPDATE);

		$withoutCommit || $this->commit($entity);
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
		$user  = '+star:'.$this->_userToken->getUsername();
		$stars = $this->selectEntities(0, ceil($count / 3), '!updated_at', 'tag', $user, EntityInterface::FLAG_GET_FOR_UPDATE);

		return array_merge($stars, $list);
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		return $this->getCount($where, $value, MessagesInterface::FLAG_GET_FOR_UPDATE);
	}

	/**
	 * @return MessagesInterface
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
		$builder = $service->createBuilder(new MessagesIndexForm($list), array_fill_keys(array_keys($list), false));

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param MessagesInterface|EntityInterface $entity
	 * @param Request $request
	 * @return \Symfony\Component\Form\Form
	 */
	public function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request)
	{
		$args = $entity->getParameters();
		$tags = @$request->get('admin_update')['tags'] ?: [];
		$tags = array_unique(array_merge($tags, isset($args['tags']) ? $args['tags'] : []));

		$data = [
			'name' => $entity->getName(),
			'tags' => isset($args['tags']) ? $args['tags'] : [],
			'text' => isset($args['text']) ? $args['text'] : '',
		];

		return $application->getServiceFormFactory()->createBuilder(new MessagesForm($entity->getId(), $tags), $data)->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param MessagesInterface|\Moro\Platform\Model\EntityInterface $entity
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
			$parameters['text']    = $data['text'];
			$parameters['comment'] = $data['comment'];

			$parameters['attachments']  = [];
			foreach ($this->selectAttachmentByEntity($entity) as $attachment)
			{
				$parameters['attachments'][] = $attachment->getName();
			}

			$entity->setName($data['name']);
			$entity->setParameters($parameters);

			$this->commit($entity);
			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи оповещения были успешно сохранены.');
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
	public function getFinishLine()
	{
		$builder = $this->_connection->createQueryBuilder();
		$builder->select('MAX(order_at)')->from($this->_table);

		$statement = $this->_connection->prepare($builder->getSQL());

		$line = $statement->execute() ? $statement->fetch(PDO::FETCH_COLUMN) : null;
		return $line ? (new DateTime($line, new DateTimeZone('UTC')))->getTimestamp() : 0;
	}

	/**
	 * @param integer $startingLine
	 * @param null|int $count
	 * @return MessagesInterface[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectEntitiesByStartingLine($startingLine, $count = null)
	{
		$builder = $this->_connection->createQueryBuilder();
		$builder->select('id')->from($this->_table);
		$builder->where(MessagesInterface::PROP_ORDER_AT.'>?')
			-> andWhere(MessagesInterface::PROP_STATUS.'='.MessagesInterface::STATUS_COMPLETED)
			->orderBy(MessagesInterface::PROP_ORDER_AT, 'ASC');
		$builder->setMaxResults($count ?: 100);

		$statement = $this->_connection->prepare($builder->getSQL());
		$line = gmdate($this->_connection->getDatabasePlatform()->getDateTimeFormatString(), $startingLine);
		$list = [];

		foreach ($statement->execute([$line]) ? array_map('intval',$statement->fetchAll(PDO::FETCH_COLUMN)) : [] as $id)
		{
			$list[] = $this->getEntityById($id);
		}

		return $list;
	}
}