<?php
/**
 * Class EntityRelink
 */
namespace Moro\Platform\Model\Implementation\Relink;


/**
 * Class EntityRelink
 * @package Model\Implementation\Relink
 */
class EntityRelink implements RelinkInterface
{
	use \Moro\Platform\Model\EntityTrait;
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Star\StarEntityTrait;

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
	public function getHREF()
	{
		return (string)$this->_properties[self::PROP_HREF];
	}

	/**
	 * @param string $href
	 * @return $this
	 */
	public function setHREF($href)
	{
		$this->_properties[self::PROP_HREF] = (string)$href;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getClass()
	{
		return (string)$this->_properties[self::PROP_CLASS];
	}

	/**
	 * @param string $class
	 * @return $this
	 */
	public function setClass($class)
	{
		$this->_properties[self::PROP_CLASS] = (string)$class;
		return $this;
	}
}