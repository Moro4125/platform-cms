<?php
use Moro\Platform\Application;

define('USE_INDEX_PHP', true);
define('INDEX_PAGE', 'admin');

if (USE_INDEX_PHP && preg_match('{^/'.preg_quote(basename(__DIR__)).'/?($|\\?.*)}', $_SERVER['REQUEST_URI'], $match))
{
	header('Location: /'.basename(__DIR__).'/index.php'.$match[1], true, 302);
	exit;
}

/** @noinspection PhpIncludeInspection */
require implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

Application::getInstance()->run();