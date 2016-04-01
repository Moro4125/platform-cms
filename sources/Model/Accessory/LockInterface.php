<?php
/**
 * Interface LockInterface
 */
namespace Moro\Platform\Model\Accessory;

/**
 * Interface LockInterface
 * @package Moro\Platform\Model\Accessory
 */
interface LockInterface
{
	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @return bool|string
	 */
	function isLocked($entity, $lockTime = null);

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @return bool
	 */
	function tryLock($entity, $lockTime = null);

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @param null|string $stamp
	 * @return bool
	 */
	function tryUnlock($entity, $lockTime = null, $stamp = null);
}