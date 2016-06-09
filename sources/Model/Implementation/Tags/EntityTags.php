<?php
/**
 * Class EntityTags
 */
namespace Moro\Platform\Model\Implementation\Tags;
use \Moro\Platform\Model\Exception\ReadOnlyPropertyException;

/**
 * Class EntityTags
 * @package Model\Content
 */
class EntityTags implements TagsInterface
{
	use \Moro\Platform\Model\EntityTrait;
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Star\StarEntityTrait;

	/**
	 * @return string
	 */
	public function getCode()
	{
		return normalizeTag(trim($this->getName()) ?: uniqid());
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		if (empty($this->_properties[self::PROP_CODE]))
		{
			$this->_properties[self::PROP_CODE] = $code;
			return $this;
		}

		$message = sprintf(ReadOnlyPropertyException::ERROR_READ_ONLY_PROPERTY, 'code');
		throw new ReadOnlyPropertyException($message, ReadOnlyPropertyException::CODE_CODE_PROPERTY_IS_READ_ONLY);
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
	 * @return int
	 */
	public function getKind()
	{
		return (int)$this->_properties[self::PROP_KIND];
	}

	/**
	 * @param int $kind
	 * @return $this
	 */
	public function setKind($kind)
	{
		$this->_properties[self::PROP_KIND] = (int)$kind;
		return $this;
	}
}