<?php
/**
 * Class EntitySubscribers
 */
namespace Moro\Platform\Model\Implementation\Subscribers;
use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtEntityTrait;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
use \Moro\Platform\Model\EntityTrait;

/**
 * Class EntitySubscribers
 * @package Moro\Platform\Model\Implementation\Subscribers
 */
class EntitySubscribers implements SubscribersInterface
{
	use EntityTrait;
	use UpdatedByEntityTrait;
	use OrderAtEntityTrait;
	use ParametersEntityTrait;
	use TagsEntityTrait;

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
	 * @return string
	 */
	public function getEmail()
	{
		return (string)$this->_properties[self::PROP_EMAIL];
	}

	/**
	 * @param string $email
	 * @return $this
	 */
	public function setEmail($email)
	{
		$this->_properties[self::PROP_EMAIL] = (string)$email;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getActive()
	{
		return (int)$this->_properties[self::PROP_ACTIVE];
	}

	/**
	 * @param int $flag
	 * @return $this
	 */
	public function setActive($flag)
	{
		$this->_properties[self::PROP_ACTIVE] = (int)$flag;
		return $this;
	}
}