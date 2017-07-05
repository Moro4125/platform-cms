<?php
/**
 * Class RoutesForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
	 * @param array $list
	 * @return $this
	 */
	public function setList(array $list)
	{
		$this->_list = $list;
		return $this;
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
			$builder->add($code, CheckboxType::class, [
				'label'    => ' ',
				'required' => false,
			]);
		}

		$builder->add('compile', SubmitType::class, [
			'label' => 'Скомпилировать',
		]);

		$builder->add('select_all', SubmitType::class, [
				'label' => 'Выбрать все',
		]);
	}
}