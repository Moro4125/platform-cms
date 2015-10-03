<?php
define('INDEX_PAGE', basename(__DIR__));

if (preg_match('{^/'.preg_quote(INDEX_PAGE).'/?($|\\?.*)}', $_SERVER['REQUEST_URI'], $match))
{
	header('Location: /'.INDEX_PAGE.'/index.php'.$match[1], true, 302);
	exit;
}

/** @noinspection PhpIncludeInspection */
require implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

Moro\Platform\Application::getInstance()->run();