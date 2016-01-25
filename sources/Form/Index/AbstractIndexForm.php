<?php
/**
 * Class ContentListForm
 */
namespace Moro\Platform\Form\Index;
use \Symfony\Component\Form\AbstractType;
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
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $list
	 * @param null|boolean $withoutCreate
	 * @param null|\Moro\Platform\Application $application
	 */
	public function __construct(array $list, $withoutCreate = null, $application = null)
	{
		$this->_list = $list;
		$this->_withoutCreate = (bool)$withoutCreate;
		$this->_application = $application;
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
			$builder->add($code, 'checkbox', [
				'label'    => ' ',
				'required' => false,
			]);
		}

		if (empty($this->_withoutCreate))
		{
			$builder->add('create', 'submit', [
				'label' => 'Добавить',
				'attr' => [
					'title' => 'Создать новый элемент и перейти к его редактированию.',
				],
			]);
		}

		$builder->add('update', 'submit', [
			'label' => 'Редактировать',
			'attr' => [
				'title' => 'Перейти к редактированию выделенных элементов списка.',
			],
		]);

		$builder->add('delete', 'submit', [
			'label' => 'Удалить',
			'attr' => [
				'title' => 'Удалить выделенные элементы списка.',
			],
		]);

		$builder->add('bind', 'submit', [
			'label' => ' ',
			'attr' => [
				'title' => 'Назначение или удаление ярлыков у записей.',
			],
		]);
	}
}