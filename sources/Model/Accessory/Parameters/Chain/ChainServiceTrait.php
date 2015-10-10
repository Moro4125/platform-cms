<?php
/**
 * Class ChainServiceTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Chain;
use \Moro\Platform\Model\AbstractDecorator;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \ArrayObject;
use \PDO;

/**
 * Class ChainServiceTrait
 * @package Model\Accessory\Parameters\Chain
 *
 * @property string $_table
 * @property \Doctrine\DBAL\Connection $_connection
 * @method notify($state, $query, $args, $entity, $nextFlag)
 * @method EntityInterface getEntityById($id, $withoutException)
 * @method commit($entity);
 */
trait ChainServiceTrait
{
	/**
	 * @var array
	 */
	protected $_chainWorkIds = [];

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null $previous
	 * @return \Moro\Platform\Model\EntityInterface|null
	 */
	public function getEntityByChain(EntityInterface $entity, $previous = null)
	{
		if ($entity instanceof ParametersInterface)
		{
			$parameters = $entity->getParameters();
			$id = isset($parameters['chain'])
				?( $previous
					? end($parameters['chain'])
					: reset($parameters['chain'])
				): null;

			return $id ? $this->getEntityById($id, true) : null;
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function ___initTraitChain()
	{
		return [
			AbstractService::STATE_COMMIT_STARTED  => '_chainCommitStarted',
			AbstractService::STATE_COMMIT_SUCCESS  => '_chainSuccess',
			AbstractService::STATE_COMMIT_FAILED   => '_chainFailed',
			AbstractService::STATE_DELETE_STARTED  => '_chainDeleteStarted',
			AbstractService::STATE_DELETE_SUCCESS  => '_chainSuccess',
			AbstractService::STATE_DELETE_FAILED   => '_chainFailed',
		];
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @return void
	 */
	protected function _chainCommitStarted(EntityInterface $entity)
	{
		$id = $entity->getId();

		if ($entity instanceof ParametersInterface && empty($this->_chainWorkIds[$id]) && count($this->_chainWorkIds) < 16)
		{
			if ($entity instanceof AbstractDecorator)
			{
				$entity = $entity->decorate(false);
			}

			$this->_chainWorkIds[$id] = [$id];

			$parameters = $entity->getParameters();
			$oldNextId = isset($parameters['chain']) ? reset($parameters['chain']) : null;
			$oldPrevId = isset($parameters['chain']) ? end($parameters['chain'])   : null;
			$newNextId = null;
			$newPrevId = null;


			$builder = $this->_connection->createQueryBuilder();
			$query = $builder->select('m.id')->from($this->_table, 'm')->where('m.id < :id')->orderBy('m.id', 'desc')->setMaxResults(1);
			$args = new ArrayObject([':id' => $id]);

			if (false !== $this->notify(ChainServiceInterface::STATE_BUILD_CHAIN_QUERY, $query, $args, clone $entity, true))
			{
				$statement = $this->_connection->prepare($query->getSQL());
				$record = $statement->execute($args->getArrayCopy()) ? $statement->fetch(PDO::FETCH_ASSOC) : null;
				$newNextId = isset($record['id']) ? (int)$record['id'] : null;
			}

			if ($newNextId != $oldNextId)
			{
				$newNextId && empty($this->_chainWorkIds[$newNextId]) && ($this->_chainWorkIds[$id][] = $newNextId);
				$oldNextId && empty($this->_chainWorkIds[$oldNextId]) && ($this->_chainWorkIds[$id][] = $oldNextId);
			}


			$builder = $this->_connection->createQueryBuilder();
			$query = $builder->select('m.id')->from($this->_table, 'm')->where('m.id > :id')->orderBy('m.id', 'asc')->setMaxResults(1);
			$args = new ArrayObject([':id' => $id]);

			if (false !== $this->notify(ChainServiceInterface::STATE_BUILD_CHAIN_QUERY, $query, $args, clone $entity, false))
			{
				$statement = $this->_connection->prepare($query->getSQL());
				$record = $statement->execute($args->getArrayCopy()) ? $statement->fetch(PDO::FETCH_ASSOC) : null;
				$newPrevId = isset($record['id']) ? (int)$record['id'] : null;
			}

			if ($newPrevId != $oldPrevId)
			{
				$newPrevId && empty($this->_chainWorkIds[$newPrevId]) && ($this->_chainWorkIds[$id][] = $newPrevId);
				$oldPrevId && empty($this->_chainWorkIds[$oldPrevId]) && ($this->_chainWorkIds[$id][] = $oldPrevId);
			}


			$parameters['chain'] = [$newNextId, $newPrevId];
			$entity->setParameters($parameters);
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @return void
	 */
	protected function _chainDeleteStarted(EntityInterface $entity)
	{
		$id = $entity->getId();

		if ($entity instanceof ParametersInterface && empty($this->_chainWorkIds[$id]) && count($this->_chainWorkIds) < 16)
		{
			$this->_chainWorkIds[$id] = [$id];

			$parameters = $entity->getParameters();
			$oldNextId = isset($parameters['chain']) ? reset($parameters['chain']) : false;
			$oldPrevId = isset($parameters['chain']) ? end($parameters['chain'])   : false;

			$oldNextId && empty($this->_chainWorkIds[$oldNextId]) && ($this->_chainWorkIds[$id][] = $oldNextId);
			$oldPrevId && empty($this->_chainWorkIds[$oldPrevId]) && ($this->_chainWorkIds[$id][] = $oldPrevId);
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @return void
	 */
	protected function _chainSuccess(EntityInterface $entity)
	{
		$id = $entity->getId();

		try
		{
			if (!empty($this->_chainWorkIds[$id]) && $id == array_shift($this->_chainWorkIds[$id]))
			{
				foreach ($this->_chainWorkIds[$id] as $workId)
				{
					$tempEntity = $this->getEntityById($workId, true);
					$tempEntity && $tempEntity->addFlag(EntityInterface::FLAG_SYSTEM_CHANGES);
					$tempEntity && $this->commit($tempEntity);
				}
			}
		}
		finally
		{
			unset($this->_chainWorkIds[$id]);
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @return void
	 */
	protected function _chainFailed(EntityInterface $entity)
	{
		$id = $entity->getId();
		unset($this->_chainWorkIds[$id]);
	}
}