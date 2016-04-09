<?php
/**
 * Class ServiceHistory
 */
namespace Moro\Platform\Model\Implementation\History;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
use \Moro\Platform\Model\EntityInterface;

/**
 * Class ServiceHistory
 * @package Moro\Platform\Model\Implementation\History
 */
class ServiceHistory extends AbstractService
{
	use UpdatedByServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'history';

	/**
	 * @var string
	 */
	protected static $_requestId;

	/**
	 * @return string
	 */
	public function getCurrentRequestId()
	{
		return self::$_requestId ?: self::$_requestId = dechex(mt_rand(0x10000000, min(0x7FFFFFFF, PHP_INT_MAX)));
	}

	/**
	 * @param string $service
	 * @param integer $entityId
	 * @param array $diff
	 * @return HistoryInterface
	 * @throws \Exception
	 */
	public function createEntity($service, $entityId, array $diff)
	{
		$requestId = $this->getCurrentRequestId();
		$diff[HistoryInterface::PROP_REQUEST_ID] = $requestId;

		$entity = $this->_newEntityFromArray([
			HistoryInterface::PROP_SERVICE => (string)$service,
			HistoryInterface::PROP_ENTITY_ID => (int)$entityId,
			HistoryInterface::PROP_REQUEST_ID => $requestId,
			HistoryInterface::PROP_PARAMETERS => $diff,
		], EntityInterface::FLAG_GET_FOR_UPDATE);

		$this->commit($entity);
		return $entity;
	}

	/**
	 * @param string $requestId
	 * @param null|EntityInterface|string|int $excludeId
	 * @param null|int $flags
	 * @return HistoryInterface[]
	 */
	public function findByRequestId($requestId, $excludeId = null, $flags = null)
	{
		$requestId = (string)$requestId;
		$excludeId = (int)($excludeId instanceof EntityInterface ? $excludeId->getId() : $excludeId);

		$result = $this->selectEntities(null, 100, 'id', HistoryInterface::PROP_REQUEST_ID, $requestId, $flags);
		$result = array_filter($result, function(HistoryInterface $entity) use ($excludeId) {
			return $entity->getId() !== $excludeId;
		});

		return $result;
	}

	/**
	 * @param AbstractService|string $service
	 * @param EntityInterface|string|int $entity
	 * @return HistoryInterface[]
	 */
	public function findByServiceAndEntity($service, $entity)
	{
		$service = $service instanceof AbstractService ? $service->getServiceCode() : (string)$service;
		$entity instanceof EntityInterface && $entity = $entity->getId();
		is_string($entity) && $entity = (int)$entity;

		$filter = [HistoryInterface::PROP_SERVICE => $service, HistoryInterface::PROP_ENTITY_ID => $entity];
		$result = $this->selectEntities(null, 100, '!created_at', array_keys($filter), array_values($filter));

		return $result;
	}

	/**
	 * @param string $oldRequestId
	 * @param string $newRequestId
	 * @return int
	 */
	public function replaceRequestId($oldRequestId, $newRequestId)
	{
		$builder = $this->_connection->createQueryBuilder()
			->update($this->_table)
			->set(HistoryInterface::PROP_REQUEST_ID, '?')
			->where(HistoryInterface::PROP_REQUEST_ID.'=?');
		$statement = $this->_connection->prepare($builder->getSQL());
		return $statement->execute([$newRequestId, $oldRequestId]) ? $statement->rowCount() : 0;
	}

	/**
	 * @param AbstractService|string $service
	 * @param EntityInterface|string|int $entity
	 * @return bool
	 */
	public function deleteByServiceAndEntity($service, $entity)
	{
		$service = $service instanceof AbstractService ? $service->getServiceCode() : (string)$service;
		$entity instanceof EntityInterface && $entity = $entity->getId();
		is_string($entity) && $entity = (int)$entity;

		$query = $this->_connection->createQueryBuilder()
			->delete($this->_table)
			->where(HistoryInterface::PROP_SERVICE.' = ?')
			->andWhere(HistoryInterface::PROP_ENTITY_ID.' = ?')
			->getSQL();
		return $this->_connection->prepare($query)->execute([$service, $entity]);
	}
}