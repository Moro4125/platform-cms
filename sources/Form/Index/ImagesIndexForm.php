<?php
/**
 * Class ImagesIndexForm
 */
namespace Moro\Platform\Form\Index;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ImagesIndexForm
 * @package Moro\Platform\Form\Index
 */
class ImagesIndexForm extends AbstractIndexForm
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);

		if ($this->_application->isGranted('ROLE_EDITOR') || $this->_application->isGranted('ROLE_CLIENT'))
		{
			if ($this->_application->getOption('images.watermark'))
			{
				$builder->add('hide_watermark', 'submit', [
					'label' => ' ',
					'attr' => [
						'title' => 'Скрыть водяной знак.',
					],
				]);

				$builder->add('show_watermark', 'submit', [
					'label' => ' ',
					'attr' => [
						'title' => 'Показать водяной знак.',
					],
				]);
			}

			if ($this->_application->getOption('images.mask1'))
			{
				$builder->add('hide_mask', 'submit', [
						'label' => ' ',
						'attr' => [
							'title' => 'Скрыть обрамление.',
						],
				]);

				$builder->add('show_mask', 'submit', [
						'label' => ' ',
						'attr' => [
							'title' => 'Показать обрамление.',
						],
				]);
			}
		}
	}
}