<?php
/**
 * Class RelinkForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\NotBlank;


/**
 * Class RelinkForm
 * @package Form
 */
class RelinkForm extends AbstractContent
{
	/**
	 * @var integer
	 */
	protected $_id;

	/**
	 * @var array
	 */
	protected $_tags;

	/**
	 * @param int $id
	 * @param array $tags
	 */
	public function __construct($id, array $tags)
	{
		$this->_id = $id;
		$this->_tags = $tags;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'admin_update';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', 'text', [
			'label' => 'Название',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Название".']),
			],
			'required' => false,
		]);

		$builder->add('tags', 'choice_tags', [
			'label'    => 'Ярлыки',
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		$group1 = 'Искомая фраза';
		$list = [
			'nominativus'      => ['Кто/Что',   'Кто или что есть в наличии?' ],
			'genitivus'        => ['Кого/Чего', 'Кого или чего нет в наличии?' ],
			'dativus'          => ['Кому/Чему', 'Кому или чему дать что-либо?' ],
			'accusativus'      => ['Кого/Что',  'Кого или что винить в чём-либо?' ],
			'instrumentalis'   => ['Кем/Чем',   'Кем или чем доволен?' ],
			'praepositionalis' => ['О ком/чём', 'О ком или о чём думаешь?' ],
		];

		foreach ($list as $code => $temp)
		{
			list($label, $title) = $temp;
			$builder->add($code, 'text', [
				'label' => $label,
				'group' => $group1,
				'required' => false,
				'attr' => [
					'placeholder' => $title,
				]
			]);
		}

		$group2 = 'Параметры ссылки';
		$list = [
			'href'  => 'URL',
			'title' => 'Подсказка',
			'class' => 'CSS класс',
		];

		foreach ($list as $code => $label)
		{
			$builder->add($code, 'text', [
				'label' => $label,
				'group' => $group2,
				'required' => false,
			]);
		}

		$list = [
			'open_tab' => 'Открывать ссылку в новой вкладке или окне',
			'nofollow' => 'Запретить роботам переход по ссылке',
		];

		foreach ($list as $code => $label)
		{
			$builder->add($code, 'checkbox', [
				'label' => $label,
				'group' => $group2,
				'required' => false,
			]);
		}


		parent::buildForm($builder, $options);
	}
}