<?php
/**
 * @param string $string
 * @return int
 */
function str_to_int($string)
{
	return (int)$string;
}

/**
 * Преобразование SHA1 хэша из 40 символов (x16) в 32 символа (x32).
 *
 * @param string $hash
 * @return string
 */
function convertSha1toX32($hash)
{
	return str_pad(base_convert(substr($hash,  0, 10), 16, 32), 8, '0', STR_PAD_LEFT)
		.  str_pad(base_convert(substr($hash, 10, 10), 16, 32), 8, '0', STR_PAD_LEFT)
		.  str_pad(base_convert(substr($hash, 20, 10), 16, 32), 8, '0', STR_PAD_LEFT)
		.  str_pad(base_convert(substr($hash, 30, 10), 16, 32), 8, '0', STR_PAD_LEFT);
}

/**
 * @param \Traversable|array $array
 * @param string|int $key
 * @param null|bool $strict
 * @return int|null|string
 */
function getPrevKey($array, $key, $strict = null)
{
	$prevKey = null;

	foreach ($array as $currentKey => $value)
	{
		if ($strict ? $currentKey === $key : $currentKey == $key)
		{
			return $prevKey;
		}

		$prevKey = $currentKey;
	}

	return null;
}

/**
 * @param \Traversable|array $array
 * @param string|int $key
 * @param null|bool $strict
 * @return int|null|string
 */
function getNextKey($array, $key, $strict = null)
{
	$flag = false;

	foreach ($array as $currentKey => $value)
	{
		if ($flag)
		{
			return $currentKey;
		}

		if ($strict ? $currentKey === $key : $currentKey == $key)
		{
			$flag = true;
		}
	}

	return null;
}

/**
 * Нормализация имени ярлыка.
 *
 * @param string $tag
 * @return string
 */
function normalizeTag($tag)
{
	return trim(str_replace(' ', '', mb_strtolower($tag, 'UTF-8')));
}

/**
 * Нормализация текстовой строки для сравнения и сортировки.
 *
 * @param string $text
 * @return string
 */
function my_sqlite_simplify_text($text)
{
	return strtr($text, [
		'А' => 'а',
		'Б' => 'б',
		'В' => 'в',
		'Г' => 'г',
		'Д' => 'д',
		'Е' => 'е',
		'Ё' => 'ё',
		'Ж' => 'ж',
		'З' => 'з',
		'И' => 'и',
		'Й' => 'й',
		'К' => 'к',
		'Л' => 'л',
		'М' => 'м',
		'Н' => 'н',
		'О' => 'о',
		'П' => 'п',
		'Р' => 'р',
		'С' => 'с',
		'Т' => 'т',
		'У' => 'у',
		'Ф' => 'ф',
		'Х' => 'х',
		'Ц' => 'ц',
		'Ч' => 'ч',
		'Ш' => 'ш',
		'Щ' => 'щ',
		'Ь' => 'ь',
		'Ы' => 'ы',
		'Ъ' => 'ъ',
		'Э' => 'э',
		'Ю' => 'ю',
		'Я' => 'я',
	]);
}