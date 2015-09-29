<?php
/**
 * Class ReadOnlyPropertyException
 */
namespace Moro\Platform\Model\Exception;
use \RuntimeException;

/**
 * Class ReadOnlyPropertyException
 * @package Model\Exception
 */
class ReadOnlyPropertyException extends RuntimeException
{
	const CODE_ID_PROPERTY_IS_READ_ONLY = 1;
	const CODE_CODE_PROPERTY_IS_READ_ONLY = 2;

	const ERROR_READ_ONLY_PROPERTY = 'The property "%1$s" is read only.';
}