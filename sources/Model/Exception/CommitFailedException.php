<?php
/**
 * Class CommitFailedException
 */
namespace Moro\Platform\Model\Exception;
use \RuntimeException;

/**
 * Class CommitFailedException
 * @package Model\Exception
 */
class CommitFailedException extends RuntimeException
{
	const C_DB_ERROR      = 1;
	const C_UNKNOWN_ERROR = 2;

	const M_NOT_EXECUTE = 'Commit of entity %1$s is failed.';
	const M_ROLLBACK_F  = 'Commit of entity %1$s has rollback flag.';
	const M_NOT_FAILED  = 'Commit entity with id %2$s to %1$s - SUCCESS.';
	const M_FAILED      = 'Commit entity with id %2$s to %1$s - FAILED.';
}