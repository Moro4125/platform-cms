<?php
/**
 * Interface EntityInterface
 */
namespace Moro\Platform\Model;
use \Moro\Platform\Model\Exception\UnknownPropertyException;
use \JsonSerializable;
use \ArrayAccess;

/**
 * Interface EntityInterface
 * @package Model
 */
interface EntityInterface extends JsonSerializable, ArrayAccess
{
	const PROP_ID         = 'id';
	const PROP_CREATED_AT = 'created_at';
	const PROP_UPDATED_AT = 'updated_at';

	const FLAG_CLONED              =  1;
	const FLAG_DATABASE            =  2;
	const FLAG_TIMESTAMP_CONVERTED =  4;
	const FLAG_SYSTEM_CHANGES      =  8;
	const FLAG_GET_FOR_UPDATE      = 16;

	/**
	 * @param string $name
	 * @return bool
	 */
	function hasProperty($name);

	/**
	 * @param array|\Traversable $properties
	 * @return $this
	 * @throws UnknownPropertyException
	 */
	function setProperties($properties);

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 * @throws UnknownPropertyException
	 */
	function setProperty($name, $value);

	/**
	 * @return array
	 */
	function getProperties();

	/**
	 * @param string $name
	 * @return mixed
	 * @throws \Moro\Platform\Model\Exception\UnknownPropertyException
	 */
	function getProperty($name);

	/**
	 * @return int
	 */
	function getId();

	/**
	 * @param int $id
	 * @return $this
	 */
	function setId($id);

	/**
	 * @return int
	 */
	function getFlags();

	/**
	 * @param int $flags
	 * @return $this
	 */
	function setFlags($flags);

	/**
	 * @param int $flag
	 * @return $this
	 */
	function addFlag($flag);

	/**
	 * @param int $flag
	 * @return $this
	 */
	function delFlag($flag);

	/**
	 * @param int $flag
	 * @return bool
	 */
	function hasFlag($flag);

	/**
	 * @return int
	 */
	function getCreatedAt();

	/**
	 * @return int
	 */
	function getUpdatedAt();
}