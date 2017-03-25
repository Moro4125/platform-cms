<?php
/**
 * ClientRoleBehavior
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Exception\AlienEntityException;

/**
 * Class ClientRoleBehavior
 * @package Model\Accessory
 */
class ClientRoleBehavior extends AbstractBehavior
{
	/**
	 * @var string
	 */
	protected $_client;

	/**
	 * @param string $client
	 * @return $this
	 */
	public function setClient($client)
	{
		$this->_client = (string)$client;
		return $this;
	}

	/**
	 * @param AbstractService $service
	 */
	protected function _initContext($service)
	{
		$this->_context = [
			self::KEY_HANDLERS => [
				AbstractService::STATE_BEFORE_SELECT => '_onBeforeSelect',
				AbstractService::STATE_ENTITY_LOADED => '_onEntityLoaded',
			],
		];
	}

	/**
	 * @param \ArrayObject $args
	 * @return array|null
	 */
	protected function _onBeforeSelect($args)
	{
		if ($args['flags'] & EntityInterface::FLAG_GET_FOR_UPDATE == 0)
		{
			return null;
		}

		if ($args['flags'] & EntityInterface::FLAG_SYSTEM_CHANGES)
		{
			return null;
		}

		$filter = $args['filter'];
		$value  = $args['value'];

		is_array($filter) || $filter = ($filter === null) ? [] : [$filter];
		is_array($value)  || $value  = ($value  === null) ? [] : [$value];

		$filter[] = UpdatedByInterface::PROP_CREATED_BY;
		$value[]  = $this->_client;

		$args['filter'] = $filter;
		$args['value']  = $value;

		return null;
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @throws AlienEntityException
	 */
	protected function _onEntityLoaded($entity)
	{
		if ($entity->hasFlag(EntityInterface::FLAG_SYSTEM_CHANGES) || !$entity->hasFlag(EntityInterface::FLAG_GET_FOR_UPDATE))
		{
			return;
		}

		if ($entity instanceof UpdatedByInterface && $entity->getId() && $entity->getCreatedBy() != $this->_client)
		{
			throw new AlienEntityException($entity->hasProperty('name') ? $entity->getProperty('name') : $entity->getId());
		}
	}
}