<?php
/**
 * Class MessagesForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\TextareaType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\Regex;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Moro\Platform\Application;

/**
 * Class MessagesForm
 * @package Moro\Platform\Form
 */
class MessagesForm extends AbstractContent
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', TextType::class, [
			'label' => 'Заголовок',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Заголовок".']),
				new Regex([
					'message' => 'Поле "Имя" не должно содержать символа запятая.',
					'pattern' => '{^[^,]*$}',
				]),
			],
			'required' => false,
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('text', TextareaType::class, [
			'label'    => 'Сообщение',
			'required' => false,
			'attr'	=> [
				'placeholder' => 'Текст сообщения',
			]
		]);

		$builder->add('tags', TagsChoiceType::class, [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_MESSAGES,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		parent::buildForm($builder, $options);
	}
}