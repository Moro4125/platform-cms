<?php
/**
 * Class MessagesIndexForm
 */
namespace Moro\Platform\Form\Index;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class MessagesIndexForm
 * @package Moro\Platform\Form\Index
 */
class MessagesIndexForm extends AbstractIndexForm
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);

		$builder->add('send', 'submit', [
				'label' => ' ',
				'attr' => [
					'title' => 'Отправить оповещение подписчикам.',
				],
		]);
	}
}