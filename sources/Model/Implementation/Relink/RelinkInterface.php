<?php
/**
 * Interface RelinkInterface
 */
namespace Moro\Platform\Model\Implementation\Relink;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\Star\StarInterface;

/**
 * Interface RelinkInterface
 * @package Model\Implementation\Relink
 */
interface RelinkInterface extends EntityInterface, UpdatedByInterface, ParametersInterface, TagsEntityInterface, StarInterface
{
	const PROP_NAME  = 'name';
	const PROP_HREF  = 'href';
	const PROP_CLASS = 'class';

	/**
	 * @return string
	 */
	function getName();

	/**
	 * @param string $name
	 * @return $this
	 */
	function setName($name);

	/**
	 * @return string
	 */
	function getHREF();

	/**
	 * @param string $href
	 * @return $this
	 */
	function setHREF($href);

	/**
	 * @return string
	 */
	function getClass();

	/**
	 * @param string $class
	 * @return $this
	 */
	function setClass($class);
}