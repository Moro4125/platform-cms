<?php
/**
 * Class UniqueFieldValidator
 */
namespace Moro\Platform\Form\Constraints;

use \Symfony\Component\Validator\Constraint;
use \Symfony\Component\Validator\ConstraintValidator;
use \PDO;

/**
 * Class UniqueFieldValidator
 * @package Form\Constraints
 */
class UniqueFieldValidator extends ConstraintValidator
{
	/**
	 * @param string $value
	 * @param Constraint|UniqueField $constraint
	 */
	public function validate($value, Constraint $constraint)
	{
		$builder = $constraint->dbal->createQueryBuilder();
		$builder->select('id')->from($constraint->table)->where($constraint->field.' = ?');
		$statement = $constraint->dbal->prepare($builder->getSQL());

		if ($statement->execute([$value]))
		{
			while ($record = $statement->fetch(PDO::FETCH_ASSOC))
			{
				if (empty($constraint->ignoreId) || $constraint->ignoreId !== (int)reset($record))
				{
					$this->context->addViolation($constraint->message);
					break;
				}
			}
		}
	}
}
