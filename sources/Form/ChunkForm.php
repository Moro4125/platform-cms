<?php
/**
 * Class ChunkForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\ArticleChoiceType;
use \Moro\Platform\Form\Type\ImageChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\Form\Extension\Core\Type\TextareaType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChunkForm
 * @package Form
 */
class ChunkForm extends AbstractContent
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', TextType::class, [
			'label' => 'Название',
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('lead', TextareaType::class, [
			'label'    => 'Описание',
			'required' => false,
			'attr' => ['placeholder' => 'Лид'],
		]);

		$builder->add('gallery', ImageChoiceType::class, [
			'label'    => 'Фотографии',
			'multiple' => true,
			'required' => false,
		]);

		$builder->add('gallery_text', TextareaType::class, [
			'label'    => 'Текстовый блок',
			'required' => false,
		]);

		$builder->add('articles', ArticleChoiceType::class, [
			'label'    => 'Связанные тексты',
			'multiple' => true,
			'required' => false,
		]);

		parent::buildForm($builder, $options);

		$builder->add('get_chunk', SubmitType::class, [
			'label' => 'Выбрать часть',
		]);

		$builder->add('add_chunk', SubmitType::class, [
			'label' => 'Добавить часть',
		]);

		$builder->add('del_chunk', SubmitType::class, [
			'label' => 'Удалить часть',
		]);
	}
}