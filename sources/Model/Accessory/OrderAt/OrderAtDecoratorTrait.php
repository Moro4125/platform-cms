<?php
/**
 * Class OrderAtDecoratorTrait
 */
namespace Moro\Platform\Model\Accessory\OrderAt;

/**
 * Class OrderAtDecoratorTrait
 * @package Model\Accessory\OrderAt
 */
trait OrderAtDecoratorTrait
{
	/**
	 * @return int
	 */
	public function getOrderAt()
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return isset($this->_entity) ? $this->_entity->getOrderAt() : null;
	}

	/**
	 * @param int|string $value
	 * @return $this
	 */
	public function setOrderAt($value)
	{
		if (isset($this->_entity))
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$this->_entity->setOrderAt($value);
		}

		return $this;
	}
}