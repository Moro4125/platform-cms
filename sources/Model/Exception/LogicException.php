<?php
/**
 * Class LogicException
 */
namespace Moro\Platform\Model\Exception;
use \LogicException as Exception;

/**
 * Class LogicException
 * @package Moro\Platform\Model\Exception
 */
class LogicException extends Exception
{
	const C_CALL_SET_CURRENT_PARENT_ID = 1;

	const M_CALL_METHOD_CHUNKS_BEHAVIOR = 'Please, call method "%1$s::setCurrentParentId" before create new entity.';
}