<?php
// Used in task.0005.ini
/** @var $container  \Moro\Platform\Application */
/** @var $service  \Moro\Migration\Handler\FilesStorageHandler *///* Service.
/** @var $arguments *///* Arguments for this script.

$uploadPath = $service->getStoragePath().DIRECTORY_SEPARATOR.'upload';

if (!file_exists($uploadPath))
{
	if (@mkdir($uploadPath, 0755))
	{
		$service->writeln('Create directory "upload"');
	}
	else
	{
		$service->error('Failed create directory "upload".');
	}
}

for ($i = 0, $createdCount = 0; $i < 1024; $i++)
{
	$subFolder = str_pad(base_convert($i, 10, 32), 2, '0', STR_PAD_LEFT);

	if (!file_exists($uploadPath.DIRECTORY_SEPARATOR.$subFolder))
	{
		if (!@mkdir($uploadPath.DIRECTORY_SEPARATOR.$subFolder, 0755))
		{
			throw new Exception("Failed create directory \"upload/$subFolder\".");
		}

		$createdCount++;
	}
}

if ($createdCount)
{
	$service->writeln('Create '.$createdCount.' subdirectories in "upload".');
}