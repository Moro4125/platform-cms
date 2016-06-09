<?php
/**
 * AbstractDecorator
 */
namespace Moro\Platform\Model\Implementation\File\Decorator;
use \Moro\Platform\Model\Implementation\File\FileInterface;
use \Moro\Platform\Model\AbstractDecorator as CAbstractDecorator;

/**
 * Class AbstractDecorator
 * @package Model\Implementation\File\Decorator
 */
abstract class AbstractDecorator extends CAbstractDecorator implements FileInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByDecoratorTrait;
	use \Moro\Platform\Model\Accessory\OrderAt\OrderAtDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Star\StarEntityTrait;

	/**
	 * @var FileInterface
	 */
	protected $_entity;

	/**
	 * @return string
	 */
	public function getHash()
	{
		return $this->_entity->getHash();
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setHash($code)
	{
		$this->_entity->setHash($code);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKind()
	{
		return $this->_entity->getKind();
	}

	/**
	 * @param string $kind
	 * @return $this
	 */
	public function setKind($kind)
	{
		$this->_entity->setKind($kind);
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getName()
	{
		return $this->_entity->getName();
	}

	/**
	 * @param string|null $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->_entity->setName($name);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSmallHash()
	{
		return $this->_entity->getSmallHash();
	}
}