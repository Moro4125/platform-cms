<?php
/**
 * Interface FileInterface
 */
namespace Moro\Platform\Model\Implementation\File;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\EntityInterface;

/**
 * Interface FileInterface
 * @package Model\File
 */
interface FileInterface extends EntityInterface, UpdatedByInterface, ParametersInterface, OrderAtInterface, TagsEntityInterface
{
	const PROP_HASH = 'hash';
	const PROP_KIND = 'kind';
	const PROP_NAME = 'name';

	/**
	 * @return string
	 */
	function getHash();

	/**
	 * @param string $code
	 * @return $this
	 */
	function setHash($code);

	/**
	 * @return string
	 */
	function getKind();

	/**
	 * @param string $kind
	 * @return $this
	 */
	function setKind($kind);

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
	function getSmallHash();
}