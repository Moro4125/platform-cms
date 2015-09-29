<?php
/**
 * Interface ChainServiceInterface
 */
namespace Moro\Platform\Model\Accessory\Parameters\Chain;
use \Moro\Platform\Model\EntityInterface;

/**
 * Interface ChainServiceInterface
 * @package Model\Accessory\Parameters\Chain
 */
interface ChainServiceInterface
{
	const STATE_BUILD_CHAIN_QUERY = 1101;

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null $previous
	 * @return EntityInterface|null
	 */
	function getEntityByChain(EntityInterface $entity, $previous = null);
}