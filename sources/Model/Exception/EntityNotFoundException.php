<?php
/**
 * Class EntityNotFoundException
 */
namespace Moro\Platform\Model\Exception;
use \RuntimeException;

/**
 * Class EntityNotFoundException
 * @package Model\Exception
 */
class EntityNotFoundException extends RuntimeException
{
	const C_BY_ID    = 1;
	const C_BY_CODE  = 2;
	const C_BY_EMAIL = 3;

	const M_NOT_FOUND = 'Entity with %1$s "%2$s" is not exists.';
}