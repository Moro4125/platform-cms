<?php
/**
 * Class ContentListForm
 */
namespace Moro\Platform\Form\Index;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\Form\FormBuilderInterface;


/**
 * Class ContentListForm
 * @package Form
 */
class AbstractIndexForm extends AbstractType
{
	/**
	 * @var \Moro\Platform\Model\Implementation\Content\EntityContent[]
	 */
	protected $_list;

	/**
	 * @var bool
	 */
	protected $_withoutCreate;

	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_application;

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
	 * @param bool $flag
	 * @return $this
	 */
	public function setWithoutCreate($flag)
	{
		$this->_withoutCreate = (bool)$flag;
		return $this;
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @return $this
	 */
	public function setApplication($application)
	{
		$this->_application = $application;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'admin_list';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		foreach ($this->_list as $code => $entity)
		{
			$builder->add($code, CheckboxType::class, [
				'label'    => ' ',
				'required' => false,
			]);
		}

		if (empty($this->_withoutCreate))
		{
			$builder->add('create', SubmitType::class, [
				'label' => 'Добавить',
				'attr' => [
					'title' => 'Создать новый элемент и перейти к его редактированию.',
				],
			]);
		}

		$builder->add('update', SubmitType::class, [
			'label' => 'Редактировать',
			'attr' => [
				'title' => 'Перейти к редактированию выделенных элементов списка.',
			],
		]);

		$builder->add('delete', SubmitType::class, [
			'label' => 'Удалить',
			'attr' => [
				'title' => 'Удалить выделенные элементы списка.',
			],
		]);

		$builder->add('bind', SubmitType::class, [
			'label' => ' ',
			'attr' => [
				'title' => 'Назначение или удаление ярлыков у записей.',
			],
		]);
	}
}