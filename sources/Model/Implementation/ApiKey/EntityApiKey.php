<?php
/**
 * Class EntityApiKey
 */
namespace Moro\Platform\Model\Implementation\ApiKey;

/**
 * Class EntityApiKey
 * @package Moro\Platform\Model\Implementation\ApiKey
 */
class EntityApiKey implements ApiKeyInterface
{
	use \Moro\Platform\Model\EntityTrait;

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->_properties[self::PROP_KEY];
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->_properties[self::PROP_USER];
	}

	/**
	 * @return string
	 */
	public function getRoles()
	{
		return $this->_properties[self::PROP_ROLES];
	}

	/**
	 * @return array
	 */
	public function getGroups()
	{
		return explode(',', $this->_properties[self::PROP_ROLES]);
	}

	/**
	 * @return string
	 */
	public function getTarget()
	{
		return $this->_properties[self::PROP_TARGET];
	}

	/**
	 * @return integer
	 */
	public function getCounter()
	{
		return (int)$this->_properties[self::PROP_COUNTER];
	}
}