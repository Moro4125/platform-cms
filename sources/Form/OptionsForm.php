<?php
/**
 * Class OptionsForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\ImageChoiceType;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\TextareaType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
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
	 * @var array
	 */
	protected $_typesMap = [
		'text'         => TextType::class,
		'textarea'     => TextareaType::class,
		'choice_image' => ImageChoiceType::class,
	];

	/**
	 * @param $application
	 * @return $this
	 */
	public function setApplication($application)
	{
		$this->_application = $application;
		return $this;
	}

	/**
	 * @param array $list
	 * @return $this
	 */
	public function setDataList(array $list)
	{
		$this->_dataList = $list;
		return $this;
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

			$builder->add($code, $this->_typesMap[$entity->getType()], $parameters);
		}
	}
}