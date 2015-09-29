<?php
/**
 * Class OptionsForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;


use \Moro\Platform\Model\Implementation\Options\EntityOptions;
use \Moro\Platform\Application;

/**
 * Class OptionsForm
 * @package Form
 */
class OptionsForm extends AbstractType
{
	/**
	 * @var Application
	 */
	protected $_application;

	/**
	 * @var EntityOptions[]
	 */
	protected $_dataList;

	/**
	 * @param \Moro\Platform\Application $application
	 * @param \Moro\Platform\Model\Implementation\Options\EntityOptions[] $dataList
	 */
	public function __construct(Application $application, array $dataList)
	{
		$this->_application = $application;
		$this->_dataList = $dataList;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'admin_options';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Options\EntityOptions[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		foreach ($this->_dataList as $code => $entity)
		{
			$parameters = [
				'group'    => $entity->getBlock(),
				'label'    => $entity->getLabel(),
				'required' => false,
			];

			$builder->add($code, $entity->getType(), $parameters);
		}
	}
}