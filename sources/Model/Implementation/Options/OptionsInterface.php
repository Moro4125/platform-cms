<?php
/**
 * Interface OptionsInterface
 */
namespace Moro\Platform\Model\Implementation\Options;
use \Moro\Platform\Model\EntityInterface;

/**
 * Interface OptionsInterface
 * @package Model\Implementation\Options
 */
interface OptionsInterface extends EntityInterface
{
	const PROP_CODE      = 'code';
	const PROP_TYPE      = 'type';
	const PROP_VALUE     = 'value';
	const PROP_BLOCK     = 'block';
	const PROP_LABEL     = 'label';
	const PROP_VALIDATOR = 'validator';
	const PROP_SORT      = 'sort';

	const FREE_CREATED_AT = self::PROP_CREATED_AT;
	const FREE_UPDATED_AT = self::PROP_UPDATED_AT;

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
	 * @return string
	 */
	function getType();

	/**
	 * @param string $type
	 * @return $this
	 */
	function setType($type);

	/**
	 * @return string
	 */
	function getValue();

	/**
	 * @param mixed $value
	 * @return $this
	 */
	function setValue($value);

	/**
	 * @return string
	 */
	function getBlock();

	/**
	 * @param string $block
	 * @return $this
	 */
	function setBlock($block);

	/**
	 * @return string
	 */
	function getLabel();

	/**
	 * @param string $label
	 * @return $this
	 */
	function setLabel($label);

	/**
	 * @return string
	 */
	function getValidator();

	/**
	 * @param string $validator
	 * @return $this
	 */
	function setValidator($validator);

	/**
	 * @return int
	 */
	function getSort();

	/**
	 * @param int $sort
	 * @return $this
	 */
	function setSort($sort);
}