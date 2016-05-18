<?php
/**
 * Interface MessagesInterface
 */
namespace Moro\Platform\Model\Implementation\Messages;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtInterface;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;

/**
 * Interface MessagesInterface
 * @package Moro\Platform\Model\Implementation\Messages
 */
interface MessagesInterface extends EntityInterface, OrderAtInterface, UpdatedByInterface, ParametersInterface, TagsEntityInterface
{
	const PROP_NAME   = 'name';
	const PROP_STATUS = 'status';

	const STATUS_DRAFT     = 0;
	const STATUS_COMPLETED = 1;

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
	 * @return int
	 */
	function getStatus();

	/**
	 * @param int $status
	 * @return $this
	 */
	function setStatus($status);
}