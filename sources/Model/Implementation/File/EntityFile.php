<?php
/**
 * Class EntityFile
 */
namespace Moro\Platform\Model\Implementation\File;


/**
 * Class EntityFile
 * @package Model\File
 */
class EntityFile implements FileInterface
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
	public function getHash()
	{
		return $this->_properties[self::PROP_HASH];
	}

	/**
	 * @param string $hash
	 * @return $this
	 */
	public function setHash($hash)
	{
		$this->_properties[self::PROP_HASH] = (string)$hash;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKind()
	{
		return $this->_properties[self::PROP_KIND];
	}

	/**
	 * @param string $kind
	 * @return $this
	 */
	public function setKind($kind)
	{
		$this->_properties[self::PROP_KIND] = (string)$kind;
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
	public function getSmallHash()
	{
		return substr($this->getHash(), 0, 4).'…'.substr($this->getHash(), -4);
	}
}