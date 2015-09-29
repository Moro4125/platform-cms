<?php
/**
 * Interface EntityInterface
 */
namespace Moro\Platform\Model;
use \Moro\Platform\Model\Exception\UnknownPropertyException;
use \JsonSerializable;

/**
 * Interface EntityInterface
 * @package Model
 */
interface EntityInterface extends JsonSerializable
{
	const PROP_ID         = 'id';
	const PROP_CREATED_AT = 'created_at';
	const PROP_UPDATED_AT = 'updated_at';

	const FLAG_CLONED              = 1;
	const FLAG_DATABASE            = 2;
	const FLAG_TIMESTAMP_CONVERTED = 4;

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
	 * @return int
	 */
	function getCreatedAt();

	/**
	 * @return int
	 */
	function getUpdatedAt();
}