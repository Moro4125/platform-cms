<?php
/**
 * Class AbstractContent
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AbstractContent
 * @package Moro\Platform\Form
 */
abstract class AbstractContent extends  AbstractType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('commit', 'submit', [
			'label' => 'Сохранить',
			'attr' => [
				'data-lock' => '1',
			],
		]);

		$builder->add('apply', 'submit', [
			'label' => 'Применить',
		]);

		$builder->add('cancel', 'submit', [
			'label' => 'Отмена',
		]);

		$builder->add('delete', 'submit', [
			'label' => 'Удалить',
		]);
	}
}