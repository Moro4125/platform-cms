<?php
/**
 * Class EntityHistory
 */
namespace Moro\Platform\Model\Implementation\History;
use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
use \Moro\Platform\Model\EntityTrait;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;

/**
 * Class EntityHistory
 * @package Moro\Platform\Model\Implementation\History
 */
class EntityHistory implements HistoryInterface
{
	use EntityTrait;
	use UpdatedByEntityTrait;
	use ParametersEntityTrait;

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setService($name)
	{
		$this->_properties[self::PROP_SERVICE] = (string)$name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getService()
	{
		return $this->_properties[self::PROP_SERVICE];
	}

	/**
	 * @param integer $id
	 * @return $this
	 */
	public function setEntityId($id)
	{
		$this->_properties[self::PROP_ENTITY_ID] = (int)$id;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getEntityId()
	{
		return $this->_properties[self::PROP_ENTITY_ID];
	}

	/**
	 * @return string
	 */
	public function getRequestId()
	{
		return $this->_properties[self::PROP_REQUEST_ID];
	}

	/**
	 * @param string $id
	 * @return $this
	 */
	public function setRequestId($id)
	{
		$this->_properties[self::PROP_REQUEST_ID] = (string)$id;
		return $this;
	}
}