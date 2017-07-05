<?php
/**
 * Class ContentForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\ImageChoiceType;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Moro\Platform\Form\Constraints\UniqueField;
use \Moro\Platform\Model\Implementation\Content\EntityContent;
use \Moro\Platform\Application;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Regex;

/**
 * Class ContentForm
 * @package Form
 */
class ContentForm extends ChunkForm
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('code', TextType::class, [
			'label' => 'Код',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Символьный код".']),
				new Regex([
					'message' => 'Значение поля "Символьный код" должно состоять только из прописных латинских букв, цифр, дефиса и символа подчёркивания.'
							.' Рекомендуется использовать английские слова и обозначения. ',
					'pattern' => '{^[a-z][-a-z0-9_]*$}',
				]),
				new UniqueField([
					'message' => 'Значение поля "Символьный код" должно быть уникальным для каждого материала.',
					'table' => Application::getInstance()->getServiceContent()->getTableName(),
					'field' => EntityContent::PROP_CODE,
					'ignoreId' => $this->_id,
					'dbal' => Application::getInstance()->getServiceDataBase(),
				]),
			],
			'attr' => ['placeholder' => 'Символьный код', 'title' => 'Уникальный символьный код материала'],
		]);

		$builder->add('icon', ImageChoiceType::class, [
			'label'    => 'Анонс',
			'required' => false,
		]);

		$builder->add('tags', TagsChoiceType::class, [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_CONTENT,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		$builder->add('external', TextType::class, [
			'label' => 'Ссылка',
			'required' => false,
			'attr' => ['placeholder' => 'Только для внешней ссылки', 'title' => 'Ссылка, используемая в анонсе материала.'],
		]);

		parent::buildForm($builder, $options);
	}
}