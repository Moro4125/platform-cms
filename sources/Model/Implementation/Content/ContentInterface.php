<?php
/**
 * Interface ContentInterface
 */
namespace Moro\Platform\Model\Implementation\Content;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\EntityInterface;

/**
 * Interface ContentInterface
 * @package Model\Content
 */
interface ContentInterface extends EntityInterface, UpdatedByInterface, ParametersInterface, OrderAtInterface, TagsEntityInterface
{
	const PROP_CODE = 'code';
	const PROP_NAME = 'name';
	const PROP_ICON = 'icon';

	/**
	 * @return string
	 */
	function getCode();

	/**
	 * @param string $code
	 * @return $this
	 */
	function setCode($code);

	/**
	 * @return string|null
	 */
	function getName();

	/**
	 * @param string|null $name
	 * @return $this
	 */
	function setName($name);

	/**
	 * @return string
	 */
	function getIcon();

	/**
	 * @param string $hash
	 * @return $this
	 */
	function setIcon($hash);
}