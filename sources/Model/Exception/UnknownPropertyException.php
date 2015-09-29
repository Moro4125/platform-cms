<?php
/**
 * Class UnknownPropertyException
 */
namespace Moro\Platform\Model\Exception;
use \InvalidArgumentException;

/**
 * Class ReadOnlyPropertyException
 * @package Model\Exception
 */
class UnknownPropertyException extends InvalidArgumentException
{
	const CODE_SET_UNKNOWN_PROPERTY_NAME = 1;
	const CODE_GET_UNKNOWN_PROPERTY_NAME = 2;

	const ERROR_UNKNOWN_PROPERTY = 'The property "%1$s" is not exists.';
}