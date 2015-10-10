<?php
/**
 * Class AbstractDecorator
 */
namespace Moro\Platform\Model\Implementation\Content\Decorator;
use \Moro\Platform\Model\AbstractDecorator as CDecorator;
use \Moro\Platform\Model\Implementation\Content\ContentInterface;

/**
 * Class AbstractDecorator
 * @package Model\Implementation\Content\Decorator
 */
class AbstractDecorator extends CDecorator implements ContentInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByDecoratorTrait;
	use \Moro\Platform\Model\Accessory\OrderAt\OrderAtDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsDecoratorTrait;

	/**
	 * @var ContentInterface
	 */
	protected $_entity;

	/**
	 * @var array
	 */
	protected $_urlParameters;

	/**
	 * @var string
	 */
	protected $_url;

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->_entity->getCode();
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->_entity->setCode($code);
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
	public function getIcon()
	{
		return $this->_entity->getIcon();
	}

	/**
	 * @param string $hash
	 * @return $this
	 */
	public function setIcon($hash)
	{
		$this->_entity->setIcon($hash);
		return $this;
	}
}