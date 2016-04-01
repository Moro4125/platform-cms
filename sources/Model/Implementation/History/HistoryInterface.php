<?php
/**
 * Interface HistoryInterface
 */
namespace Moro\Platform\Model\Implementation\History;
use Moro\Platform\Model\EntityInterface;
use Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use Moro\Platform\Model\Accessory\Parameters\ParametersInterface;


/**
 * Interface HistoryInterface
 * @package Moro\Platform\Model\Implementation\History
 */
interface HistoryInterface extends EntityInterface, UpdatedByInterface, ParametersInterface
{
	const PROP_SERVICE    = 'service';
	const PROP_ENTITY_ID  = 'entity_id';
	const PROP_REQUEST_ID = 'request_id';

	const FREE_UPDATED_AT = EntityInterface::PROP_UPDATED_AT;
	const FREE_UPDATED_BY = UpdatedByInterface::PROP_UPDATED_BY;

	/**
	 * @param string $name
	 * @return $this
	 */
	function setService($name);

	/**
	 * @return string
	 */
	function getService();

	/**
	 * @param integer $id
	 * @return $this
	 */
	function setEntityId($id);

	/**
	 * @return integer
	 */
	function getEntityId();

	/**
	 * @param string $id
	 * @return $this
	 */
	function setRequestId($id);

	/**
	 * @return string
	 */
	function getRequestId();
}