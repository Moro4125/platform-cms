<?php
/**
 * Class EntityOptions
 */
namespace Moro\Platform\Model\Implementation\Options;


/**
 * Class EntityOptions
 * @package Model\Options
 */
class EntityOptions implements OptionsInterface
{
	use \Moro\Platform\Model\EntityTrait;

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->_properties[self::PROP_CODE];
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->_properties[self::PROP_CODE] = $code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->_properties[self::PROP_TYPE];
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->_properties[self::PROP_TYPE] = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->_properties[self::PROP_VALUE];
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public function setValue($value)
	{
		$this->_properties[self::PROP_VALUE] = $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBlock()
	{
		return $this->_properties[self::PROP_BLOCK];
	}

	/**
	 * @param string $block
	 * @return $this
	 */
	public function setBlock($block)
	{
		$this->_properties[self::PROP_BLOCK] = $block;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->_properties[self::PROP_LABEL];
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->_properties[self::PROP_LABEL] = $label;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValidator()
	{
		return $this->_properties[self::PROP_VALIDATOR];
	}

	/**
	 * @param string $validator
	 * @return $this
	 */
	public function setValidator($validator)
	{
		$this->_properties[self::PROP_VALIDATOR] = $validator;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSort()
	{
		return (int)$this->_properties[self::PROP_SORT];
	}

	/**
	 * @param int $sort
	 * @return $this
	 */
	public function setSort($sort)
	{
		$this->_properties[self::PROP_SORT] = (int)$sort;
		return $this;
	}
}