<?php
/**
 * Class EntityUsers
 */
namespace Moro\Platform\Model\Implementation\Users;
use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\Star\StarEntityTrait;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
use \Moro\Platform\Model\EntityTrait;

/**
 * Class EntityUsers
 * @package Moro\Platform\Model\Implementation\Users
 */
class EntityUsers implements UsersInterface
{
	use EntityTrait;
	use UpdatedByEntityTrait;
	use ParametersEntityTrait;
	use TagsEntityTrait;
	use StarEntityTrait;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->_properties[self::PROP_NAME];
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
		return $this->_properties[self::PROP_EMAIL];
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
}