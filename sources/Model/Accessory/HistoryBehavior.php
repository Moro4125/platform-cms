<?php
/**
 * Class HistoryBehavior
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Implementation\History\ServiceHistory;
use \Moro\Platform\Tools\DiffMatchPatch;

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
	 * @param AbstractService $service
	 */
	protected function _initContext($service)
	{
		assert($this->_service);

		$this->_context[self::KEY_HANDLERS] = [
			AbstractService::STATE_ENTITY_LOADED   => '_onEntityLoaded',
			AbstractService::STATE_COMMIT_FINISHED => '_onCommitFinished',
			AbstractService::STATE_DELETE_FINISHED => '_onDeleteFinished',
		];
		$this->setPatchFields([]);
		$this->setBlackFields([]);
		$this->setWhiteFields([]);
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
	public function setBlackFields(array $fields)
	{
		assert(isset($this->_context));
		$this->_context[self::KEY_BLACK_FIELDS] = $fields;
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
	 */
	protected function _onCommitFinished(EntityInterface $entity)
	{
		$id = $entity->getId();
		$flag = $entity->hasFlag(EntityInterface::FLAG_SYSTEM_CHANGES);

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

				$this->_service->createEntity($subject->getServiceCode(), $id, $diff);
			}

			unset($this->_context[self::KEY_CACHE][$id]);
		}
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