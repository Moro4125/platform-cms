<?php
/**
 * Class EntityContent
 */
namespace Moro\Platform\Model\Implementation\Content;

/**
 * Class EntityContent
 * @package Model\Content
 */
class EntityContent implements ContentInterface
{
	use \Moro\Platform\Model\EntityTrait;
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
	use \Moro\Platform\Model\Accessory\OrderAt\OrderAtEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Star\StarEntityTrait;

	/**
	 * @return string
	 */
	public function getCode()
	{
		if (empty($this->_properties[self::PROP_CODE]))
		{
			$this->_properties[self::PROP_CODE] = uniqid('temp_');
		}

		return $this->_properties[self::PROP_CODE];
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->_properties[self::PROP_CODE] = (string)$code;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getName()
	{
		return $this->_properties[self::PROP_NAME];
	}

	/**
	 * @param string|null $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->_properties[self::PROP_NAME] = $name ? (string)$name : null;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return (string)$this->_properties[self::PROP_ICON];
	}

	/**
	 * @param string $hash
	 * @return $this
	 */
	public function setIcon($hash)
	{
		$this->_properties[self::PROP_ICON] = $hash;
		return $this;
	}
}