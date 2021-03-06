<?php
/**
 * Class ImageUpdateForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Regex;
use \Moro\Platform\Application;

/**
 * Class ImageUpdateForm
 * @package Form
 */
class ImageUpdateForm extends AbstractContent
{
	/**
	 * @var array
	 */
	protected $_kinds;

	/**
	 * @var array
	 */
	protected $_tags;

	/**
	 * @var bool
	 */
	protected $_useWatermark;

	/**
	 * @var bool
	 */
	protected $_useMask;

	/**
	 * @param array $kinds
	 * @param array $tags
	 * @param bool $useWatermark
	 * @param bool $useMask
	 */
	public function __construct(array $kinds, array $tags, $useWatermark, $useMask)
	{
		$this->_kinds = $kinds;
		$this->_tags = $tags;
		$this->_useWatermark = $useWatermark;
		$this->_useMask = $useMask;
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
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', 'text', [
			'label' => 'Название',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Название".']),
				new Regex([
					'message' => 'Значение поля "Название" не должно содержать квадратных скобок.',
					'pattern' => '{^[^\[\]]*$}',
				]),
				new Regex([
					'message' => 'Значение поля "Название" не должно содержать двойных пробелов.',
					'pattern' => '{^[^ ]*([ ][^ ]+)*$}',
				]),
			],
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('lead', 'text', [
			'label' => 'Описание',
			'required' => false,
			'attr' => ['placeholder' => 'Необязательное текстовое описание изображения'],
		]);

		$builder->add('tags', 'choice_tags', [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_FILE,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		foreach ($this->_kinds as $kind)
		{
			if ($this->_useWatermark)
			{
				$builder->add('watermark'.$kind, 'choice', [
					'label' => 'Логотип',
					'choices' => [
						'1' => 'верхний правый угол',
						'2' => 'нижний правый угол',
						'3' => 'нижний левый угол',
						'4' => 'верхний левый угол',
						'0' => 'отсутствует',
					],
					'attr' => ['style' => 'width:100%;'],
				]);
			}

			if ($this->_useMask)
			{
				$builder->add('hide_mask'.$kind, 'checkbox', [
					'label' => 'Не накладывать маску',
					'required' => false,
				]);
			}

			$builder->add('copy'.$kind, 'submit', [
				'label' => 'Вырезать область',
				'attr'  => ['title' => 'Вырезать выделенную область и сохранить в качестве нового изображения.'],
			]);
			$builder->add('append'.$kind, 'submit', [
				'label' => 'Добавить поля',
				'attr'  => ['title' => 'Добавить поля так, что бы изображение полностью вписывалось в заданное соотношение сторон. Сохранить в качестве нового изображения.'],
			]);
			$builder->add('crop'.$kind.'_a', 'checkbox', [
				'label'    => '',
				'required' => false,
			]);
			$builder->add('crop'.$kind.'_x', 'hidden', [
				'label' => 'X',
			]);
			$builder->add('crop'.$kind.'_y', 'hidden', [
				'label' => 'Y',
			]);
			$builder->add('crop'.$kind.'_w', 'hidden', [
				'label' => 'W',
			]);
			$builder->add('crop'.$kind.'_h', 'hidden', [
				'label' => 'H',
			]);
		}


		parent::buildForm($builder, $options);
	}
}