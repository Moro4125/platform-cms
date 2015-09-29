<?php
/**
 * Interface UpdatedByInterface
 */
namespace Moro\Platform\Model\Accessory\UpdatedBy;

/**
 * Interface UpdatedByInterface
 * @package Model\Accessory
 */
interface UpdatedByInterface
{
	const PROP_CREATED_BY = 'created_by';
	const PROP_UPDATED_BY = 'updated_by';

	/**
	 * @return string|null
	 */
	function getCreatedBy();

	/**
	 * @param string|null $user
	 * @return $this
	 */
	function setCreatedBy($user);

	/**
	 * @return string|null
	 */
	function getUpdatedBy();

	/**
	 * @param string|null $user
	 * @return $this
	 */
	function setUpdatedBy($user);
}