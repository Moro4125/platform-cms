<?php
/**
 * Class ServiceOptions
 */
namespace Moro\Platform\Model\Implementation\Options;
use \Moro\Platform\Application;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Form\OptionsForm;
use \Moro\Platform\Model\EntityInterface;
use \Symfony\Component\Form\Form;
use \ArrayAccess;


/**
 * Class ServiceOptions
 * @package Model\Options
 */
class ServiceOptions extends AbstractService implements ArrayAccess
{
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'options';

	/**
	 * @var EntityOptions[]
	 */
	protected $_cachedFormItems;

	/**
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getAllOptions()
	{
		$result = [];
		$sql = "SELECT"." * "."FROM {$this->_table} ORDER BY " .EntityOptions::PROP_SORT;
		$statement = $this->_connection->prepare($sql);

		foreach ($statement->execute() ? $statement->fetchAll() : [] as $record)
		{
			$result[$record['code']] = $this->_newEntityFromArray($record, EntityInterface::FLAG_GET_FOR_UPDATE);
		}

		return $result;
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminForm(Application $application)
	{
		$this->_cachedFormItems || $this->_cachedFormItems = $this->getAllOptions();

		/** @var EntityOptions[] $dataList */
		$dataList = array_filter($this->_cachedFormItems, function(EntityOptions $entity) {
			return $entity->getSort() > 0;
		});

		$service = $application->getServiceFormFactory();
		$builder = $service->createBuilder(new OptionsForm($application, $dataList), array_map(function(EntityOptions $entity) {
			return $entity->getValue();
		}, $dataList));

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param Form $form
	 */
	public function commitAdminForm(Application $application, Form $form)
	{
		$this->_cachedFormItems || $this->_cachedFormItems = $this->getAllOptions();

		foreach ($form->getData() as $code => $value)
		{
			if (empty($this->_cachedFormItems[$code]))
			{
				continue;
			}

			if ($this->_cachedFormItems[$code]->getValue() != $value)
			{
				$this->_cachedFormItems[$code]->setValue($value);
				$this->commit($this->_cachedFormItems[$code]);
			}
		}

		unset($application);
	}

	/**
	 * @param string $offset
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function offsetExists($offset)
	{
		$query = $this->_connection->createQueryBuilder()->select('id')->from($this->_table)->where('code = ?');
		$statement = $this->_connection->prepare($query->getSQL());
		return $statement->execute([$offset]) && (bool)$statement->fetchColumn();
	}

	/**
	 * @param string $offset
	 * @return mixed|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function offsetGet($offset)
	{
		$query = $this->_connection->createQueryBuilder()->select('value')->from($this->_table)->where('code = ?');
		$statement = $this->_connection->prepare($query->getSQL());
		return $statement->execute([$offset]) ? $statement->fetchColumn() : null;
	}

	/**
	 * @param string $offset
	 * @param mixed $value
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function offsetSet($offset, $value)
	{
		$parameters = [':offset' => $offset, ':value' => $value];

		if ($this->offsetExists($offset))
		{
			$query = $this->_connection->createQueryBuilder()->update($this->_table)->where('code = :offset')
				->set('value', ':value');
			$statement = $this->_connection->prepare($query->getSQL());
		}
		else
		{
			$query = $this->_connection->createQueryBuilder()->insert($this->_table)
				->setValue('value', ':value')->setValue('code', ':offset');
			$statement = $this->_connection->prepare($query->getSQL());
		}

		return $statement->execute($parameters);
	}

	/**
	 * @param string $offset
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function offsetUnset($offset)
	{
		$query = $this->_connection->createQueryBuilder()->delete($this->_table)->where('code = ?');
		$statement = $this->_connection->prepare($query->getSQL());
		return $statement->execute([$offset]);
	}
}