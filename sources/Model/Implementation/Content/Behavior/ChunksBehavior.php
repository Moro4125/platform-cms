<?php
/**
 * Class ChunksBehavior
 */
namespace Moro\Platform\Model\Implementation\Content\Behavior;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Accessory\LockInterface;
use \Moro\Platform\Model\Accessory\EventBridgeBehavior;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Implementation\Content\ServiceContent;
use \Moro\Platform\Model\Exception\LogicException;

/**
 * Class ChunksBehavior
 * @package Moro\Platform\Model\Implementation\Content\Behavior
 */
class ChunksBehavior extends AbstractBehavior
{
	const KEY_PARENT_ID       = 'parent_id';
	const STATE_PARENT_DELETE = 3001;

	/**
	 * @var ServiceContent
	 */
	protected $_service;

	/**
	 * @var bool
	 */
	protected $_offOnDeleteFinished;

	/**
	 * @param ServiceContent $service
	 * @return $this
	 */
	public function setContentService(ServiceContent $service)
	{
		$this->_service = $service;
		return $this;
	}

	/**
	 * @param ServiceContent $service
	 */
	protected function _initContext($service)
	{
		assert(isset($this->_service));

		$bridge = new EventBridgeBehavior(AbstractService::STATE_DELETE_FINISHED, self::STATE_PARENT_DELETE, $service);
		$this->_service->attach($bridge);

		$this->_context[self::KEY_LOCKED] = false;
		$this->_context[self::KEY_HANDLERS] = [
			AbstractService::STATE_ENTITY_LOADED      => '_onEntityLoaded',
			AbstractService::STATE_PREPARE_COMMIT     => '_onPrepareCommit',
			AbstractService::STATE_COMMIT_FINISHED    => '_onCommitFinished',
			AbstractService::STATE_DELETE_FINISHED    => '_onDeleteFinished',
			self::STATE_PARENT_DELETE                 => '_onDeleteParent',
			LockInterface::STATE_CHECK_LOCK           => '_onCheckLock',
			LockInterface::STATE_TRY_LOCK             => '_onTryLock',
			LockInterface::STATE_TRY_UNLOCK           => '_onTryUnlock',
			TagsServiceInterface::STATE_TAGS_GENERATE => '_stopNotify',
			TagsServiceInterface::STATE_TAGS_DELETING => '_stopNotify',
		];
	}

	/**
	 * @return void
	 */
	protected function _stopNotify()
	{
		/** @var ServiceContent $service */
		$service  = $this->_context[self::KEY_SUBJECT];
		$service->stopNotify();
	}

	/**
	 * @param int $id
	 */
	protected function setCurrentParentId($id)
	{
		$this->_context[self::KEY_PARENT_ID] = (int)$id;
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 */
	protected function _onEntityLoaded($entity)
	{
		$parameters = $entity->getParameters();

		if (empty($parameters[self::KEY_PARENT_ID]))
		{
			if (empty($this->_context[self::KEY_PARENT_ID]))
			{
				/** @var \Moro\Platform\Model\Implementation\Content\ServiceContent $service */
				$service = $this->_context[self::KEY_SUBJECT];
				$message = sprintf(LogicException::M_CALL_METHOD_CHUNKS_BEHAVIOR, $service->getServiceCode());
				throw new LogicException($message, LogicException::C_CALL_SET_CURRENT_PARENT_ID);
			}

			/** @var \Moro\Platform\Model\Implementation\Content\ServiceContent $service */
			$service  = $this->_context[self::KEY_SUBJECT];
			$parentId = $this->_context[self::KEY_PARENT_ID];
			$number   = $service->getCount('parent_id', $parentId) + 1;

			$entity->setCode('chunk-'.$parentId.'-'.$number);
			$entity->setName((string)$number);

			$parameters[self::KEY_PARENT_ID] = $parentId;
			$entity->setParameters($parameters);
		}
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param boolean $insert
	 * @param \ArrayObject $params
	 * @return null|array
	 */
	protected function _onPrepareCommit($entity, $insert, $params)
	{
		if ($insert)
		{
			$list = [];
			$parentId = $this->_context[self::KEY_PARENT_ID];

			if ($this->_service && $parent = $this->_service->getEntityById($parentId, true))
			{
				/** @var \Moro\Platform\Model\Implementation\Content\EntityContent $parent */
				$params[':'.UpdatedByInterface::PROP_CREATED_BY] = $parent->getCreatedBy();
				$list[UpdatedByInterface::PROP_CREATED_BY] = ':'.UpdatedByInterface::PROP_CREATED_BY;
			}

			$params[':'.self::KEY_PARENT_ID] = $parentId;
			$list[self::KEY_PARENT_ID] = ':'.self::KEY_PARENT_ID;

			return $list;
		}

		unset($entity);
		return null;
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param string $table
	 * @return void
	 */
	protected function _onCommitFinished($entity, $table)
	{
		if ($this->_service)
		{
			$parameters = $entity->getParameters();
			$parentId = isset($parameters[self::KEY_PARENT_ID]) ? $parameters[self::KEY_PARENT_ID] : null;
			$parent = $parentId ? $this->_service->getEntityById($parentId, true, EntityInterface::FLAG_GET_FOR_UPDATE) : null;

			/** @var \Moro\Platform\Model\Implementation\Content\EntityContent $parent */
			if ($parent)
			{
				/** @var \Moro\Platform\Model\Implementation\Content\ServiceContent $subject */
				$subject = $this->_context[self::KEY_SUBJECT];

				$parameters = $parent->getParameters();
				$chunks = isset($parameters['chunks']) ? $parameters['chunks'] : [];

				$n = (int)explode('-', $entity->getCode())[2];
				$chunks['count'] = $subject->getCount('parent_id', $parentId);
				$chunks["num$n"] = gmdate("Y-m-d H:i:s", time());

				$parameters['chunks'] = $chunks;
				$parent->setParameters($parameters);

				$this->_service->commit($parent);
			}
		}

		unset($table);
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param string $table
	 * @param integer $count
	 */
	protected function _onDeleteFinished($entity, $table, $count)
	{
		if ($this->_service && empty($this->_offOnDeleteFinished))
		{
			$this->_onCommitFinished($entity, $table);
		}

		unset($count);
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param string $table
	 * @param integer $count
	 */
	protected function _onDeleteParent($entity, $table, $count)
	{
		try
		{
			$this->_offOnDeleteFinished = true;

			/** @var \Moro\Platform\Model\Implementation\Content\ServiceContent $subject */
			$subject = $this->_context[self::KEY_SUBJECT];
			$idsList = array_map(function(EntityInterface $entity) {
				return $entity->getId();
			}, $subject->selectEntities(null, null, null, 'parent_id', $entity->getId()));
			$subject->deleteEntitiesById($idsList);
		}
		finally
		{
			$this->_offOnDeleteFinished = false;
		}

		unset($table, $count);
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param int|null $time
	 * @param string $user
	 * @return null|bool|string
	 */
	protected function _onCheckLock($entity, $time, $user)
	{
		if ($this->_service)
		{
			$parameters = $entity->getParameters();
			$parentId = isset($parameters[self::KEY_PARENT_ID]) ? $parameters[self::KEY_PARENT_ID] : null;
			$entity = $parentId ? $this->_service->getEntityById($parentId, true) : null;

			if ($entity && $owner = $this->_service->isLocked($entity, $time))
			{
				/** @var \Moro\Platform\Model\Implementation\Content\ServiceContent $service */
				$service  = $this->_context[self::KEY_SUBJECT];
				$service->stopNotify();

				return $owner;
			}
		}

		unset($user);
		return null;
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param int|null $time
	 * @param string|null $token
	 * @param string $user
	 * @return null|bool|string
	 */
	protected function _onTryLock($entity, $time, $token, $user)
	{
		if ($this->_service)
		{
			$parameters = $entity->getParameters();
			$parentId = isset($parameters[self::KEY_PARENT_ID]) ? $parameters[self::KEY_PARENT_ID] : null;
			$entity = $parentId ? $this->_service->getEntityById($parentId, true) : null;

			if ($entity && !$this->_service->tryLock($entity, $time, $token))
			{
				/** @var \Moro\Platform\Model\Implementation\Content\ServiceContent $service */
				$service  = $this->_context[self::KEY_SUBJECT];
				$service->stopNotify();

				return false;
			}
		}

		unset($user);
		return null;
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent $entity
	 * @param int|null $time
	 * @param string|null $token
	 * @param string $user
	 * @return null|bool
	 */
	protected function _onTryUnlock($entity, $time, $token, $user)
	{
		if ($this->_service)
		{
			$parameters = $entity->getParameters();
			$parentId = isset($parameters[self::KEY_PARENT_ID]) ? $parameters[self::KEY_PARENT_ID] : null;
			$entity = $parentId ? $this->_service->getEntityById($parentId, true) : null;

			if ($entity && !$this->_service->tryUnlock($entity, $time, $token))
			{
				return false;
			}
		}

		unset($user);
		return null;
	}
}