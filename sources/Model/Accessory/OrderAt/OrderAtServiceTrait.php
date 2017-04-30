<?php
/**
 * Class OrderAtServiceTrait
 */
namespace Moro\Platform\Model\Accessory\OrderAt;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;

/**
 * Class OrderAtServiceTrait
 * @package Moro\Platform\Model\Accessory\OrderAt
 */
trait OrderAtServiceTrait
{
	/**
	 * @return array
	 */
	protected function ___initTraitOrderAt()
	{
		if (isset($this->_specials))
		{
			$this->_specials[] = OrderAtInterface::PROP_ORDER_AT;
		}

		return [
			AbstractService::STATE_PREPARE_COMMIT => '_prepareSpecialsOrderAt',
		];
	}

	/**
	 * @param EntityInterface $entity
	 * @param bool $insert
	 * @param \ArrayObject $params
	 * @return array
	 */
	protected function _prepareSpecialsOrderAt(EntityInterface $entity, $insert, $params)
	{
		$fields = [];

		if ($entity->hasProperty(OrderAtInterface::PROP_ORDER_AT))
		{
			if ($insert && isset($this->_connection))
			{
				/** @noinspection PhpUndefinedMethodInspection */
				$now = $this->_connection->getDriver()->getDatabasePlatform()->getNowExpression();
				$fields[OrderAtInterface::PROP_ORDER_AT] = $now;
			}
			else
			{
				$key = ':'.OrderAtInterface::PROP_ORDER_AT;
				$fields[OrderAtInterface::PROP_ORDER_AT] = $key;
				$params[$key] = $entity->getProperty(OrderAtInterface::PROP_ORDER_AT);
			}
		}

		return $fields;
	}
}