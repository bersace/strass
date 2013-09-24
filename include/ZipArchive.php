<?php

if (class_exists('ZipArchive'))
	return;

// Réimplémentation de la classe ZipArchive en utilisant system("zip …"); pour la portabilité.

function za_rm_rf($file0)
{
	$files = func_get_args();
	$file = array_shift($files);

	if ($files)
		call_user_func_array('za_rm_rf', $files);
		
	if (is_dir($file)) {
		$dir = $file;
		$files = scandir($dir);
		unset($files[0], $files[1]);
		foreach($files as $i => $file)
			$files[$i] = $dir.'/'.$file;
		call_user_func_array('za_rm_rf', $files);
		rmdir($dir);
	}
	else {
		unlink($file);
	}
}

class ZipArchive
{
	const OVERWRITE	=	0;

	protected $_filename;
	protected $_tmpdir;

	function open($filename, $mode = ZipArchive::OVERWRITE)
	{
		$this->_tmpdir = tempnam(null, 'za-');
		unlink($this->_tmpdir);
		mkdir($this->_tmpdir, 0755, true);
		$this->_filename = $filename;
	}

	function close()
	{
		$pwd = getcwd();
		chdir($this->_tmpdir);
		$cmd = "zip -r ".escapeshellarg($this->_filename)." *";
		$stdout = exec($cmd);
		chdir($pwd);

		za_rm_rf($this->_tmpdir);
	}

	function addEmptyDir($dir)
	{
		if (!file_exists($dir))
			mkdir($this->_tmpdir.'/'.$dir, 0755, true);
	}

	function addFromString($name, $content)
	{
		$filename = $this->_tmpdir.'/'.$name;
		$this->addEmptyDir(dirname($filename));
		file_put_contents($filename, $content);
	}
  }
