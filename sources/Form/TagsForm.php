<?php
/**
 * Class TagsForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\HiddenType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Regex;
use \Moro\Platform\Form\Constraints\UniqueField;
use \Moro\Platform\Model\Implementation\Tags\TagsInterface;
use \Moro\Platform\Application;

/**
 * Class TagsForm
 * @package Form
 */
class TagsForm extends AbstractContent
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', TextType::class, [
			'label' => 'Ярлык',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Ярлык".']),
				new Regex([
					'message' => 'Поле "Ярлык" не должно содержать символа запятая.',
					'pattern' => '{^[^,]*$}',
				]),
			],
			'required' => false,
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('code', HiddenType::class, [
			'constraints' => [
				new UniqueField([
					'message'  => 'Название ярлыка должно быть уникальным.',
					'table'    => Application::getInstance()->getServiceTags()->getTableName(),
					'field'    => TagsInterface::PROP_CODE,
					'ignoreId' => $this->_id,
					'dbal'     => Application::getInstance()->getServiceDataBase(),
				]),
			],
			'error_bubbling' => false,
			'required' => false,
		]);

		$builder->add('kind', ChoiceType::class, [
			'label' => 'Тип ярлыка',
			'choices' => [
				0 => 'Обычный',
				1 => 'Синоним',
				2 => 'Системный',
			],
		]);

		$builder->add('lead', TextType::class, [
			'label' => 'Описание',
			'required' => false,
		]);

		$builder->add('tags', TagsChoiceType::class, [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_TAGS,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);


		parent::buildForm($builder, $options);
	}
}