<?php
/**
 * Interface StarInterface
 */
namespace Moro\Platform\Model\Accessory\Parameters\Star;

/**
 * Interface StarInterface
 * @package Moro\Platform\Model\Accessory\Parameters\Star
 */
interface StarInterface
{
	/**
	 * @param $user
	 * @return bool|null
	 */
	function hasStar($user);

	/**
	 * @param $user
	 * @return $this
	 */
	function addStar($user);

	/**
	 * @param $user
	 * @return $this
	 */
	function delStar($user);
}