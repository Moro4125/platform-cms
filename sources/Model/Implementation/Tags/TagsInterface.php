<?php
/**
 * Interface TagsInterface
 */
namespace Moro\Platform\Model\Implementation\Tags;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\EntityInterface;

/**
 * Interface TagsInterface
 * @package Model\Content
 */
interface TagsInterface extends EntityInterface, UpdatedByInterface, ParametersInterface, TagsEntityInterface
{
	const PROP_CODE = 'code';
	const PROP_NAME = 'name';
	const PROP_KIND = 'kind';

	const KIND_STANDARD = 0;
	const KIND_SYNONYM  = 1;
	const KIND_SYSTEM   = 2;

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
	 * @return int
	 */
	function getKind();

	/**
	 * @param int $kind
	 * @return $this
	 */
	function setKind($kind);
}