<?php
/**
 * Class EntityMessages
 */
namespace Moro\Platform\Model\Implementation\Messages;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\Star\StarEntityTrait;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
use \Moro\Platform\Model\EntityTrait;

/**
 * Class EntityMessages
 * @package Moro\Platform\Model\Implementation\Messages
 */
class EntityMessages implements MessagesInterface
{
	use EntityTrait;
	use OrderAtEntityTrait;
	use UpdatedByEntityTrait;
	use ParametersEntityTrait;
	use TagsEntityTrait;
	use StarEntityTrait;

	/**
	 * @return string
	 */
	public function getName()
	{
		return (string)$this->_properties[self::PROP_NAME];
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->_properties[self::PROP_NAME] = (string)$name;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatus()
	{
		return (int)$this->_properties[self::PROP_STATUS];
	}

	/**
	 * @param int $status
	 * @return $this
	 */
	public function setStatus($status)
	{
		$this->_properties[self::PROP_STATUS] = (int)$status;
		return $this;
	}
}