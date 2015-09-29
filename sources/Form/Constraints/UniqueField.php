<?php
/**
 * Class UniqueField
 */
namespace Moro\Platform\Form\Constraints;
use \Doctrine\DBAL\Connection;
use \Symfony\Component\Validator\Constraint;
use \Symfony\Component\Validator\Exception\MissingOptionsException;
use \Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Class UniqueField
 * @package Form\Constraints
 */
class UniqueField extends Constraint
{
	const ERROR_1 = 'The option "dbal_connection" is mandatory for constraint %s';
	const ERROR_2 = 'The option "dbal_connection" must be an instance of Doctrine\DBAL\Connection for constraint %s';
	const ERROR_3 = 'The option "table" is mandatory for constraint %s';
	const ERROR_4 = 'The option "table" must be a valid string for constraint %s';
	const ERROR_5 = 'The option "field" is mandatory for constraint %s';
	const ERROR_6 = 'The option "field" must be a valid string for constraint %s';

	/**
	 * @var string
	 */
	public $message = 'This value already exist in the database';

	/**
	 * @var Connection
	 */
	public $dbal;

	/**
	 * @var string
	 */
	public $table;

	/**
	 * @var string
	 */
	public $field;

	/**
	 * @var integer
	 */
	public $ignoreId;

	/**
	 * @param null|string|array $options
	 */
	public function __construct($options = null)
	{
		parent::__construct($options);

		if ($this->dbal === null)
		{
			throw new MissingOptionsException(sprintf(self::ERROR_1, __CLASS__), ['dbal']);
		}

		if (!$this->dbal instanceof Connection)
		{
			throw new InvalidArgumentException(sprintf(self::ERROR_2, __CLASS__));
		}

		if ($this->table === null)
		{
			throw new MissingOptionsException(sprintf(self::ERROR_3, __CLASS__), ['table']);
		}

		if (!is_string($this->table) || $this->table == '')
		{
			throw new InvalidArgumentException(sprintf(self::ERROR_4, __CLASS__));
		}

		if ($this->field === null)
		{
			throw new MissingOptionsException(sprintf(self::ERROR_5, __CLASS__), ['field']);
		}

		if (!is_string($this->field) || $this->field == '')
		{
			throw new InvalidArgumentException(sprintf(self::ERROR_6, __CLASS__));
		}
	}

	/**
	 * Returns the name of the default option.
	 *
	 * @return string
	 */
	public function getDefaultOption()
	{
		return 'message';
	}
}
