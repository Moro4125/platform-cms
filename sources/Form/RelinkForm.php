<?php
/**
 * Class RelinkForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Moro\Platform\Application;

/**
 * Class RelinkForm
 * @package Form
 */
class RelinkForm extends AbstractContent
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', TextType::class, [
			'label' => 'Название',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Название".']),
			],
			'required' => false,
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('tags', TagsChoiceType::class, [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_RELINK,
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
			$builder->add($code, TextType::class, [
				'label' => $label,
				'group' => $group1,
				'required' => false,
				'attr' => [
					'placeholder' => $title,
				],
				'label_attr' => [
					'title' => $title,
				],
			]);
		}

		$group2 = 'Ссылка';
		$list = [
			'href'  => 'URL',
			'title' => 'Подсказка',
			'class' => 'CSS класс',
		];

		foreach ($list as $code => $label)
		{
			$builder->add($code, TextType::class, [
				'label' => $label,
				'group' => $group2,
				'required' => false,
			]);
		}

		$list = [
			'open_tab' => 'Открывать ссылку в новой вкладке или окне',
			'nofollow' => 'Запретить роботам переход по ссылке',
			'is_abbr'  => 'Является аббревиатурой',
			'use_name' => 'Заменить фразу на название',
		];

		foreach ($list as $code => $label)
		{
			$builder->add($code, CheckboxType::class, [
				'label' => $label,
				'group' => $group2,
				'required' => false,
			]);
		}


		parent::buildForm($builder, $options);
	}
}