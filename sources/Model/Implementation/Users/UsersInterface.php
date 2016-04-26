<?php
/**
 * Interface UsersInterface
 */
namespace Moro\Platform\Model\Implementation\Users;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;

/**
 * Interface UsersInterface
 * @package Moro\Platform\Model\Implementation\Users
 */
interface UsersInterface extends EntityInterface, UpdatedByInterface, ParametersInterface, TagsEntityInterface
{
	const PROP_NAME  = 'name';
	const PROP_EMAIL = 'email';

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
}