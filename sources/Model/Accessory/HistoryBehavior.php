<?php
/**
 * Class HistoryBehavior
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Implementation\History\HistoryInterface;
use \Moro\Platform\Model\Implementation\History\ServiceHistory;
use \Moro\Platform\Security\User\PlatformUser;
use \Moro\Platform\Tools\DiffMatchPatch;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \ArrayObject;

/**
 * Class HistoryBehavior
 * @package Moro\Platform\Model\Accessory
 */
class HistoryBehavior extends AbstractBehavior
{
	const KEY_CACHE        = 'cache';
	const KEY_PATCH_FIELDS = 'patch';
	const KEY_BLACK_FIELDS = 'black';
	const KEY_WHITE_FIELDS = 'white';

	/**
	 * @var ServiceHistory
	 */
	protected $_service;

	/**
	 * @var DiffMatchPatch
	 */
	protected $_diffMatchPatchService;

	/**
	 * @var TokenInterface
	 */
	protected $_userToken;

	/**
	 * @param $service
	 */
	public function setServiceHistory(ServiceHistory $service)
	{
		$this->_service = $service;
	}

	/**
	 * @param DiffMatchPatch $service
	 * @return $this
	 */
	public function setServiceDiffMatchPatch(DiffMatchPatch $service)
	{
		$this->_diffMatchPatchService = $service;
		return $this;
	}

	/**
	 * @param TokenInterface $token
	 * @return $this
	 */
	public function setUserToken(TokenInterface $token)
	{
		$this->_userToken = $token;
		return $this;
	}

	/**
	 * @param AbstractService $service
	 */
	protected function _initContext($service)
	{
		assert($this->_service);

		$this->_context[self::KEY_LOCKED] = false;
		$this->_context[self::KEY_HANDLERS] = [
			AbstractService::STATE_ENTITY_LOADED   => '_onEntityLoaded',
			AbstractService::STATE_COMMIT_FINISHED => '_onCommitFinished',
			AbstractService::STATE_DELETE_FINISHED => '_onDeleteFinished',
		];

		$meta = ($service instanceof HistoryMetaInterface) ? $service->getHistoryMetadata() : null;

		$this->setPatchFields($meta ? (array)$meta[HistoryMetaInterface::HISTORY_META_PATCH_FIELDS] : []);
		$this->setBlackFields($meta ? (array)$meta[HistoryMetaInterface::HISTORY_META_BLACK_FIELDS] : []);
		$this->setWhiteFields($meta ? (array)$meta[HistoryMetaInterface::HISTORY_META_WHITE_FIELDS] : []);
	}

	/**
	 * @param array $fields
	 */
	public function setPatchFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_PATCH_FIELDS] = $fields;
	}

	/**
	 * @param array $fields
	 */
	public function addPatchFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_PATCH_FIELDS] = isset($this->_context[self::KEY_PATCH_FIELDS])
			? array_unique(array_merge($this->_context[self::KEY_PATCH_FIELDS], $fields))
			: $fields;
	}

	/**
	 * @param array $fields
	 */
	public function setBlackFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_BLACK_FIELDS] = $fields;
	}

	/**
	 * @param array $fields
	 */
	public function addBlackFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_BLACK_FIELDS] = isset($this->_context[self::KEY_BLACK_FIELDS])
			? array_unique(array_merge($this->_context[self::KEY_BLACK_FIELDS], $fields))
			: $fields;
	}

	/**
	 * @param array $fields
	 */
	public function setWhiteFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_WHITE_FIELDS] = $fields;
	}

	/**
	 * @param array $fields
	 */
	public function addWhiteFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_WHITE_FIELDS] = isset($this->_context[self::KEY_WHITE_FIELDS])
			? array_unique(array_merge($this->_context[self::KEY_WHITE_FIELDS], $fields))
			: $fields;
	}

	/**
	 * Helper for handlers of event HistoryInterface::STATE_TRY_MERGE_HISTORY
	 *
	 * @param string $key
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 * @param null|string|array $notNull
	 */
	protected function historyMergeSimple($key, ArrayObject $next, ArrayObject $prev, $notNull = null)
	{
		$value = $next->offsetGet($key);

		if (!$prev->offsetExists($key))
		{
			$prev->offsetSet($key, $value);
			$next->offsetUnset($key);
		}
		elseif ($prev->offsetGet($key)[0] == $value[1] && !in_array($key, (array)$notNull))
		{
			$prev->offsetUnset($key);
			$next->offsetUnset($key);
		}
		elseif ($prev->offsetGet($key)[1] == $value[0])
		{
			$prev->offsetSet($key, [$prev->offsetGet($key)[0], $value[1]]);
			$next->offsetUnset($key);
		}
	}

	/**
	 * Helper for handlers of event HistoryInterface::STATE_TRY_MERGE_HISTORY
	 *
	 * @param string $key
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 */
	protected function historyMergePatch($key, ArrayObject $next, ArrayObject $prev)
	{
		$value = $next->offsetGet($key);

		if (!$prev->offsetExists($key))
		{
			$prev->offsetSet($key, $value);
			$next->offsetUnset($key);
		}
		else
		{
			$prev->offsetSet($key, array_merge((array)$value, (array)$prev->offsetGet($key)));
			$next->offsetUnset($key);
		}
	}

	/**
	 * Helper for handlers of event HistoryInterface::STATE_TRY_MERGE_HISTORY
	 *
	 * @param string $key
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 * @param null|string|array $notNull
	 */
	protected function historyMergeList($key, ArrayObject $next, ArrayObject $prev, $notNull = null)
	{
		$value = $next->offsetGet($key);

		if (!$prev->offsetExists($key))
		{
			$prev->offsetSet($key, $value);
			$next->offsetUnset($key);
		}
		else
		{
			list($del1, $add1) = $prev->offsetGet($key);
			list($del2, $add2) = $next->offsetGet($key);

			$del0 = array_merge(array_diff((array)$del1, (array)$add2), array_diff((array)$del2, (array)$add1));
			$add0 = array_merge(array_diff((array)$add1, (array)$del2), array_diff((array)$add2, (array)$del1));

			$prev->offsetSet($key, [array_unique($del0), array_unique($add0)]);
			$next->offsetUnset($key);

			empty($del0) && empty($add0) && !in_array($key, (array)$notNull) && $prev->offsetUnset($key);
		}
	}

	/**
	 * Helper for handlers of event HistoryInterface::STATE_TRY_MERGE_HISTORY
	 *
	 * @param string $key
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 * @param null|string|array $notNull
	 */
	protected function historyMergeIndex($key, ArrayObject $next, ArrayObject $prev, $notNull = null)
	{
		$value = $next->offsetGet($key);

		if (!$prev->offsetExists($key))
		{
			list($del, $add, $upd) = $next->offsetGet($key);

			foreach (array_unique(array_merge($del, $add, $upd)) as $index)
			{
				if ($next->offsetExists($offset = $key.'.'.$index))
				{
					$this->historyMergeSimple($offset, $next, $prev, $notNull);
				}
			}

			$prev->offsetSet($key, $value);
			$next->offsetUnset($key);
		}
		else
		{
			list($del1, $add1, $upd1) = $prev->offsetGet($key);
			list($del2, $add2, $upd2) = $next->offsetGet($key);

			$all0 = array_merge($del1, $del2, $add1, $add2, $upd1, $upd2);
			$del0 = array_merge(array_diff((array)$del1, (array)$add2), array_diff((array)$del2, (array)$add1));
			$add0 = array_merge(array_diff((array)$add1, (array)$del2), array_diff((array)$add2, (array)$del1));
			$upd0 = array_diff($all0, $del0, $add0);

			foreach (array_unique($all0) as $index)
			{
				if ($next->offsetExists($offset = $key.'.'.$index))
				{
					$this->historyMergeSimple($offset, $next, $prev, $notNull);
				}
			}

			$upd0 = array_filter($upd0, function($index) use ($key, $prev) {
				return $prev->offsetExists($key.'.'.$index);
			});

			$prev->offsetSet($key, [array_unique($del0), array_unique($add0), array_unique($upd0)]);
			$next->offsetUnset($key);

			empty($del0) && empty($add0) && empty($upd0) && !in_array($key,(array)$notNull) && $prev->offsetUnset($key);
		}
	}

	/**
	 * Helper for handlers of event HistoryInterface::STATE_TRY_MERGE_HISTORY
	 *
	 * @return null|string
	 */
	protected function historyHasNotMergedItems()
	{
		$requestId = $this->_service->getCurrentRequestId();
		return empty($this->_service->findByRequestId($requestId)) ? null : $requestId;
	}

	/**
	 * Helper for handlers of event HistoryInterface::STATE_TRY_MERGE_HISTORY
	 *
	 * @param string $requestId
	 * @param null|string $newRequestId
	 * @return int
	 */
	protected function historyReplaceRequestId($requestId, $newRequestId = null)
	{
		$newRequestId = is_null($newRequestId) ? dechex(mt_rand(0x10000000, 0x7FFFFFFF)) : (string)$newRequestId;
		return $this->_service->replaceRequestId($requestId, $newRequestId);
	}

	/**
	 * @param EntityInterface $entity
	 */
	protected function _onEntityLoaded(EntityInterface $entity)
	{
		$id = $entity->getId();
		$flag = $entity->hasFlag(EntityInterface::FLAG_SYSTEM_CHANGES);

		if ($id && empty($this->_context[self::KEY_CACHE][$id]) && !$flag)
		{
			$this->_context[self::KEY_CACHE][$id] = clone $entity;
		}
	}

	/**
	 * @param EntityInterface $entity
	 * @param string $table
	 * @param bool $insert
	 */
	protected function _onCommitFinished(EntityInterface $entity, $table, $insert)
	{
		$id = $entity->getId();
		$flag = $entity->hasFlag(EntityInterface::FLAG_SYSTEM_CHANGES);

		if ($insert)
		{
			/** @var AbstractService $subject */
			$subject = $this->_context[self::KEY_SUBJECT];
			$this->_service->deleteByServiceAndEntity($subject->getServiceCode(), $entity->getId());
		}

		if (isset($this->_context[self::KEY_CACHE][$id]) && !$flag)
		{
			/** @var AbstractService $subject */
			$subject = $this->_context[self::KEY_SUBJECT];
			$diff = $subject->calculateDiff($this->_context[self::KEY_CACHE][$id], $entity);

			foreach ($this->_context[self::KEY_PATCH_FIELDS] as $key)
			{
				if (isset($diff[$key]) && $this->_diffMatchPatchService)
				{
					$path = $this->_diffMatchPatchService->patchMake((string)$diff[$key][1], (string)$diff[$key][0]);
					$diff[$key] = $this->_diffMatchPatchService->patchToText($path);
				}
			}

			foreach ($this->_context[self::KEY_BLACK_FIELDS] as $key)
			{
				unset($diff[$key]);
			}

			if ($diff)
			{
				foreach ($this->_context[self::KEY_WHITE_FIELDS] as $key)
				{
					$value = $entity;

					foreach (explode('.', $key) as $index)
					{
						$value = ($value !== null && isset($value[$index])) ? $value[$index] : null;
					}

					if ($value === null)
					{
						unset($diff[$key]);
					}
					else
					{
						$diff[$key] = $value;
					}
				}

				$list = $this->_service->selectEntities(0, 1, '!'.EntityInterface::PROP_CREATED_AT, [
					HistoryInterface::PROP_SERVICE   => $subject->getServiceCode(),
					HistoryInterface::PROP_ENTITY_ID => $id,
				], null, EntityInterface::FLAG_GET_FOR_UPDATE);

				/** @see \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait */
				/** @var \Moro\Platform\Security\User\PlatformUser $h */
				if (($h = $this->_userToken->getUser()) instanceof PlatformUser && $profile = $h->getProfile())
				{
					$user = explode('@', $profile->getEmail())[0];
				}
				else
				{
					$user = $this->_userToken->getUsername();
				}

				/** @var HistoryInterface $last */
				while (TRUE)
				{
					$t = ['parameters.comment' => 1];

					switch (FALSE)
					{
						case ($last = reset($list)): break;
						case ($user == $last->getCreatedBy()): break;
						case ($updatedAt = $last->getUpdatedAt() ?: $last->getCreatedAt()): break;
						case (time() - $updatedAt < 3600): break;
						case (time() - $last->getCreatedAt() < 43200): break;
						case (!isset($last->getParameters()['parameters.comment'])): break;
						case ($prev = new ArrayObject($last->getParameters() + array_intersect_key($diff, $t))); break;
						case ($next = new ArrayObject(array_diff_key($diff, $t))); break;
						case ($last->getRequestId() === (isset($prev['request_id']) ? $prev['request_id'] : 0)): break;
						case is_null($subject->notify(HistoryInterface::STATE_TRY_MERGE_HISTORY, $next, $prev)): break;
						case ($next->count() === 0): break;

						default:
							$last->setParameters($prev->getArrayCopy());
							$this->_service->commit($last);
							break 2;
					}

					$this->_service->createEntity($subject->getServiceCode(), $id, $diff);
					break;
				}
			}

			unset($this->_context[self::KEY_CACHE][$id]);
		}

		unset($table);
	}

	/**
	 * @param EntityInterface $entity
	 * @param null $temp
	 * @param bool $success
	 */
	protected function _onDeleteFinished(EntityInterface $entity, $temp, $success)
	{
		if ($success)
		{
			/** @var AbstractService $subject */
			$subject = $this->_context[self::KEY_SUBJECT];
			$this->_service->deleteByServiceAndEntity($subject->getServiceCode(), $entity->getId());
		}

		unset($temp);
	}
}