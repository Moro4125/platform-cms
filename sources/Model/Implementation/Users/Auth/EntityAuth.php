<?php
/**
 * Class EntityUsersAuth
 */
namespace Moro\Platform\Model\Implementation\Users\Auth;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtEntityTrait;
use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
use \Moro\Platform\Model\EntityTrait;

/**
 * Class EntityUsersAuth
 * @package Moro\Platform\Model\Implementation\Users\Auth
 */
class EntityAuth implements UsersAuthInterface
{
	use EntityTrait;
	use OrderAtEntityTrait;
	use ParametersEntityTrait;

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return (int)$this->_properties[self::PROP_USER_ID];
	}

	/**
	 * @return string
	 */
	public function getProvider()
	{
		return (string)$this->_properties[self::PROP_PROVIDER];
	}

	/**
	 * @return string
	 */
	public function getIdentifier()
	{
		return (string)$this->_properties[self::PROP_IDENTIFIER];
	}

	/**
	 * @return string
	 */
	public function getCredential()
	{
		return (string)$this->_properties[self::PROP_CREDENTIAL];
	}

	/**
	 * @return string
	 */
	public function getRoles()
	{
		return (string)$this->_properties[self::PROP_ROLES];
	}

	/**
	 * @return string
	 */
	public function getUpdatedIp()
	{
		return (string)$this->_properties[self::PROP_UPDATED_IP];
	}

	/**
	 * @return int
	 */
	public function getSuccess()
	{
		return (int)$this->_properties[self::PROP_SUCCESS];
	}

	/**
	 * @return int
	 */
	public function getFailure()
	{
		return (int)$this->_properties[self::PROP_FAILURE];
	}

	/**
	 * @return int
	 */
	public function getResult()
	{
		return (int)$this->_properties[self::PROP_RESULT];
	}

	/**
	 * @return int
	 */
	public function getBanned()
	{
		return (int)$this->_properties[self::PROP_BANNED];
	}
}