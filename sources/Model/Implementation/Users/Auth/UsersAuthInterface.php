<?php
/**
 * Interface UsersAuthInterface
 */
namespace Moro\Platform\Model\Implementation\Users\Auth;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\OrderAt\OrderAtInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;

/**
 * Interface UsersAuthInterface
 * @package Moro\Platform\Model\Implementation\Users\Auth
 */
interface UsersAuthInterface extends EntityInterface, OrderAtInterface, ParametersInterface
{
	const PROP_USER_ID    = 'user_id';
	const PROP_PROVIDER   = 'provider';
	const PROP_IDENTIFIER = 'identifier';
	const PROP_CREDENTIAL = 'credential';
	const PROP_ROLES      = 'roles';
	const PROP_UPDATED_IP = 'updated_ip';
	const PROP_SUCCESS    = 'success';
	const PROP_FAILURE    = 'failure';
	const PROP_RESULT     = 'result';
	const PROP_BANNED     = 'banned';

	const MAIN_PROVIDER = 'Platform';

	/**
	 * @return int
	 */
	function getUserId();

	/**
	 * @return string
	 */
	function getProvider();

	/**
	 * @return string
	 */
	function getIdentifier();

	/**
	 * @return string
	 */
	function getCredential();

	/**
	 * @return string
	 */
	function getRoles();

	/**
	 * @return string
	 */
	function getUpdatedIp();

	/**
	 * @return int
	 */
	function getSuccess();

	/**
	 * @return int
	 */
	function getFailure();

	/**
	 * @return int
	 */
	function getResult();

	/**
	 * @return int
	 */
	function getBanned();
}