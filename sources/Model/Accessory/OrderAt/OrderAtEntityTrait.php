<?php
/**
 * Class OrderAtEntityTrait
 */
namespace Moro\Platform\Model\Accessory\OrderAt;

use \Moro\Platform\Model\EntityInterface;

/**
 * Class OrderAtEntityTrait
 * @package Model\Accessory\OrderAt
 *
 * @method getCreatedAt()
 */
trait OrderAtEntityTrait
{
	/**
	 * @return int
	 */
	public function getOrderAt()
	{
		$time = (isset($this->_properties) && !empty($this->_properties[OrderAtInterface::PROP_ORDER_AT]))
			? $this->_properties[OrderAtInterface::PROP_ORDER_AT]
			: $this->getCreatedAt();

		if (isset($this->_flags) && ($this->_flags & EntityInterface::FLAG_DATABASE) && ($this->_flags & EntityInterface::FLAG_TIMESTAMP_CONVERTED))
		{
			$time = gmdate('Y-m-d H:i:s', $time);
		}

		return $time;
	}

	/**
	 * @param int|string $value
	 * @return $this
	 */
	public function setOrderAt($value)
	{
		if (isset($this->_flags) && ($this->_flags & EntityInterface::FLAG_TIMESTAMP_CONVERTED) && is_string($value))
		{
			$value = strtotime($value);
		}

		if (isset($this->_properties))
		{
			$this->_properties[OrderAtInterface::PROP_ORDER_AT] = (int)$value;
		}

		return $this;
	}
}