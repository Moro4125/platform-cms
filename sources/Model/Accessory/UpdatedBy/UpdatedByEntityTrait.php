<?php
/**
 * Class UpdatedByEntityTrait
 */
namespace Moro\Platform\Model\Accessory\UpdatedBy;


/**
 * Class UpdatedByEntityTrait
 * @package Model\Accessory
 */
trait UpdatedByEntityTrait
{
	/**
	 * @return string|null
	 */
	public function getCreatedBy()
	{
		return isset($this->_properties) ? $this->_properties[UpdatedByInterface::PROP_CREATED_BY] : null;
	}

	/**
	 * @param string|null $user
	 * @return $this
	 */
	public function setCreatedBy($user)
	{
		if (isset($this->_properties))
		{
			$this->_properties[UpdatedByInterface::PROP_CREATED_BY] = $user ? (string)$user : null;
		}

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getUpdatedBy()
	{
		return isset($this->_properties) ? $this->_properties[UpdatedByInterface::PROP_UPDATED_BY] : null;
	}

	/**
	 * @param string|null $user
	 * @return $this
	 */
	public function setUpdatedBy($user)
	{
		if (isset($this->_properties))
		{
			$this->_properties[UpdatedByInterface::PROP_UPDATED_BY] = $user ? (string)$user : null;
		}

		return $this;
	}
}