<?php
/**
 * Class RoutesForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Moro\Platform\Model\Implementation\Routes\EntityRoutes;

/**
 * Class RoutesForm
 * @package Form
 */
class RoutesForm extends AbstractType
{
	/**
	 * @var \Moro\Platform\Model\Implementation\Routes\EntityRoutes[]
	 */
	protected $_list;

	/**
	 * @param EntityRoutes[] $list
	 */
	public function __construct(array $list)
	{
		$this->_list = $list;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'admin_routes';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param EntityRoutes[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		foreach ($this->_list as $code => $entity)
		{
			$builder->add($code, 'checkbox', [
				'label'    => ' ',
				'required' => false,
			]);
		}

		$builder->add('compile', 'submit', [
			'label' => 'Скомпилировать',
		]);

		$builder->add('select_all', 'submit', [
				'label' => 'Выбрать все',
		]);
	}
}