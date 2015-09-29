<?php
/**
 * Interface OrderAtInterface
 */
namespace Moro\Platform\Model\Accessory\OrderAt;

/**
 * Interface OrderAtInterface
 * @package Model\Accessory\OrderAt
 */
interface OrderAtInterface
{
	const PROP_ORDER_AT = 'order_at';

	/**
	 * @return integer
	 */
	function getOrderAt();

	/**
	 * @param integer $time
	 * @return $this
	 */
	function setOrderAt($time);
}