<?php

spl_autoload_register(function($class)
{
	$folder = __DIR__ . '/src/';

	$signature = 'DecodeLLC\\MSP\\';

	$classWithoutSignature = substr($class, strlen($signature));

	if (strpos($class, $signature) === 0)
	{
		$filename = $folder . strtr($classWithoutSignature, '\\', '/') . '.php';

		if (file_exists($filename))
		{
			require_once $filename;

			return true;
		}
	}

}, true, true);
