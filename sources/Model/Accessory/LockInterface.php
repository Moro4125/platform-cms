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
	const STATE_CHECK_LOCK = 2001;
	const STATE_TRY_LOCK   = 2002;
	const STATE_TRY_UNLOCK = 2003;

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @return bool|string
	 */
	function isLocked($entity, $lockTime = null);

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @param null|string $token
	 * @return bool
	 */
	function tryLock($entity, $lockTime = null, $token = null);

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @param null|string $token
	 * @return bool
	 */
	function tryUnlock($entity, $lockTime = null, $token = null);
}