<?php
/**
 * Interface ApiKeyInterface
 */
namespace Moro\Platform\Model\Implementation\ApiKey;
use \Moro\Platform\Model\EntityInterface;

/**
 * Interface ApiKeyInterface
 * @package Moro\Platform\Model\Implementation\ApiKey
 */
interface ApiKeyInterface extends EntityInterface
{
	const PROP_KEY     = 'key';
	const PROP_USER    = 'user';
	const PROP_ROLES   = 'roles';
	const PROP_TARGET  = 'target';
	const PROP_COUNTER = 'counter';

	/**
	 * @return string
	 */
	function getKey();

	/**
	 * @return string
	 */
	function getUser();

	/**
	 * @return string
	 */
	function getRoles();

	/**
	 * @return array
	 */
	function getGroups();

	/**
	 * @return string
	 */
	function getTarget();

	/**
	 * @return integer
	 */
	function getCounter();
}