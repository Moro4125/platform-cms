<?php
/**
 * Class ShowImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Imagine\Image\Box;
use \Imagine\Image\Point;
use \Imagine\Image\ImageInterface;


/**
 * Class ShowImagesAction
 * @package Action
 */
class ShowImagesAction
{
	const DELTA = 0.007;

	/**
	 * @var array
	 */
	protected $_mimes = [
		'jpg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
	];

	/**
	 * @var array  Список файлов с тенью для изображения.
	 */
	protected static $_masks = [];

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @param string $salt
	 * @param string $hash
	 * @param integer $width
	 * @param integer $height
	 * @param string $format
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $salt, $hash, $width, $height, $format = 'jpg')
	{
		$errorFileSuffix = "{$width}_{$height}.jpg?original={$hash}&format=".urlencode($format);

		if (isset($this->_mimes[$format]))
		{
			if (!$request->query->has('silent') || !$request->query->get('silent'))
			{
				$app->getServiceFlash()->error("Задан неизвестный формат \"$format\" для формирования изображения.");
			}

			return $app->redirect($app->getOption('images.not_found').$errorFileSuffix);
		}

		if (!file_exists($file = $app->getServiceFile()->getPathForHash($hash)))
		{
			if (!$request->query->has('silent') || !$request->query->get('silent'))
			{
				$app->getServiceFlash()->error("Отсутствует изображение с хэшем \"$hash\".");
			}

			return $app->redirect($app->getOption('images.not_found').$errorFileSuffix);
		}

		if (empty(self::$_masks))
		{
			for ($options = $app->getOptions('images'), $i = 1; !empty($options["mask$i"]); $i++)
			{
				$record = array_map('trim', explode(',', $options["mask$i"]));
				self::$_masks[array_shift($record)] = array_values($record);
			}
		}

		$image = $app->getServiceImagine()->open($file);
		$name = $hash;
		$size = $image->getSize();

		$watermarkPercent = 0.333;
		$toSmallForWatermark = false;
		$watermarkFile = $app->getOption('images.watermark');

		$parameters = $defaults = [
			'crop_x'    => 0,
			'crop_y'    => 0,
			'crop_w'    => $size->getWidth(),
			'crop_h'    => $size->getHeight(),
			'hide_mask' => false,
			'watermark' => 3,
		];

		// Кроп и ресайз оригинальной картинки.
		if ($width && $height)
		{
			$difference = PHP_INT_MAX;

			foreach ($app->getServiceFile()->selectByHash($hash) as $entity)
			{
				if (($options = $entity->getParameters()) && !empty($options['crop_w']) && !empty($options['crop_h']))
				{
					$name = $entity->getName() ?: $name;
					$delta = abs($width / $height - $options['crop_w'] / $options['crop_h']);

					if ($difference > $delta || abs($difference - $delta) < self::DELTA)
					{
						$difference = $delta;
						$parameters = array_merge($defaults, $options);
					}
				}
			}

			$difference = abs($width / $height - $size->getWidth() / $size->getHeight());
			$delta = abs($width / $height - $parameters['crop_w'] / $parameters['crop_h']);

			// Когда отсутствует кроп под нужное соотношение сторон, а картинка имеет подходящие соотношение размеров.
			if ($delta - $difference > self::DELTA * 3)
			{
				$parameters = array_merge($parameters, [
					'crop_x'    => 0,
					'crop_y'    => 0,
					'crop_w'    => $size->getWidth(),
					'crop_h'    => $size->getHeight(),
				]);
			}

			$point = new Point(@$parameters['crop_x'] ?: 0, @$parameters['crop_y'] ?: 0);
			$sizes = new Box($parameters['crop_w'], $parameters['crop_h']);
			$image->crop($point, $sizes);

			if (!$sizes->contains(new Box($width, $height)))
			{
				$ratio = max($width / $sizes->getWidth(), $height / $sizes->getHeight());
				$image->resize(new Box(ceil($sizes->getWidth() * $ratio), ceil($sizes->getHeight() * $ratio)));
			}

			$image = $image->thumbnail(new Box($width, $height), ImageInterface::THUMBNAIL_OUTBOUND);
			$size = $image->getSize();
		}

		// Наложение тени.
		if ($width && $height)
		{
			$difference = 0;
			$maskPath = false;
			$imgRatio = $size->getWidth() / $size->getHeight();

			foreach (self::$_masks as $maskFile => $meta)
			{
				$delta = abs($imgRatio - $meta[0]);

				if (empty($maskPath) || $difference > $delta || abs($difference - $delta) < self::DELTA)
				{
					$difference = $delta;
					$watermarkPercent = $meta[1];
					$toSmallForWatermark = $meta[2] >= $size->getWidth();
					$maskPath = realpath($app->getOption('path.ir6e').DIRECTORY_SEPARATOR.$maskFile);
				}
			}

			if ($maskPath && empty($parameters['hide_mask']))
			{
				$mask = $app->getServiceImagine()->open($maskPath);
				$mask->resize($size);
				$image->paste($mask, new Point(0, 0));
			}
		}

		// Наложение водяного знака.
		if ($width && $height && !empty($parameters['watermark']) && !$toSmallForWatermark && $watermarkFile)
		{
			if ($watermarkPath = realpath($app->getOption('path.ir6e').DIRECTORY_SEPARATOR.$watermarkFile))
			{
				$watermark = $app->getServiceImagine()->open($watermarkPath);
				$watermarkSize = $watermark->getSize();

				$watermarkWidth = round($size->getWidth() * $watermarkPercent);
				$watermarkHeight = round($watermarkSize->getHeight() * ($watermarkWidth / $watermarkSize->getWidth()));
				$watermarkSize = new Box($watermarkWidth, $watermarkHeight);

				$x0 = $y0 = 0;
				$x1 = $size->getWidth() - $watermarkSize->getWidth();
				$y1 = $size->getHeight() - $watermarkSize->getHeight();

				switch ($parameters['watermark'])
				{
					case  1: $point = new Point($x1, $y0); break;
					case  2: $point = new Point($x1, $y1); break;
					case  3: $point = new Point($x0, $y1); break;

					default: $point = new Point($x0, $y0);
				}

				$watermark->resize($watermarkSize);
				$image->paste($watermark, $point);
			}
		}

		// Наложение резкости.
		if ($size->getWidth() <= 224)
		{
			$image->effects()->sharpen();
		}

		// Завершающие действия.
		$name = strtr($name.' '.$size->getWidth().'x'.$size->getHeight(), '"', "'");
		return Response::create(
			$image->get($format, ['jpeg_quality' => ($size->getWidth()) < 112 ? 100 : 90]),
			Response::HTTP_OK,
			[
				'Content-Type'        => $this->_mimes[$format],
				'Content-Disposition' => 'inline; filename="'.$name.'.'.$format.'"'
			]
		);
	}
}