<?php
/**
 * Class UpdatedByDecoratorTrait
 */
namespace Moro\Platform\Model\Accessory\UpdatedBy;

/**
 * Class UpdatedByDecoratorTrait
 * @package Model\Accessory
 */
trait UpdatedByDecoratorTrait
{
	/**
	 * @return string|null
	 */
	public function getCreatedBy()
	{
		return isset($this->_entity) ? $this->_entity->getCreatedBy() : null;
	}

	/**
	 * @param string|null $user
	 * @return $this
	 */
	public function setCreatedBy($user)
	{
		if (isset($this->_entity))
		{
			$this->_entity->setCreatedBy($user ? (string)$user : null);
		}

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getUpdatedBy()
	{
		return isset($this->_entity) ? $this->_entity->getUpdatedBy() : null;
	}

	/**
	 * @param string|null $user
	 * @return $this
	 */
	public function setUpdatedBy($user)
	{
		if (isset($this->_entity))
		{
			$this->_entity->setUpdatedBy($user ? (string)$user : null);
		}

		return $this;
	}
}