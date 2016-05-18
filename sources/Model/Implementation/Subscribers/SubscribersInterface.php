<?php
/**
 * Interface SubscribersInterface
 */
namespace Moro\Platform\Model\Implementation\Subscribers;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;

/**
 * Interface SubscribersInterface
 * @package Moro\Platform\Model\Implementation\Subscribers
 */
interface SubscribersInterface extends EntityInterface, UpdatedByInterface, OrderAtInterface, ParametersInterface, TagsEntityInterface
{
	const PROP_NAME   = 'name';
	const PROP_EMAIL  = 'email';
	const PROP_ACTIVE = 'active';

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
	 * @return string
	 */
	function getEmail();

	/**
	 * @param string $email
	 * @return $this
	 */
	function setEmail($email);

	/**
	 * @return int
	 */
	function getActive();

	/**
	 * @param int $flag
	 * @return $this
	 */
	function setActive($flag);
}