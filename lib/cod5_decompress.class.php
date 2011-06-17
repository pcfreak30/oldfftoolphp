<?php
/*
Program: ffManager PHP Version
Author: Derrick J. Hammer
Author Alias: PCFreak30
License: GPL v2 http://www.gnu.org/licenses/gpl.html
Last Updated: 6-17-11
*/
@define("DS", DIRECTORY_SEPARATOR);
class COD5_Decompress
{
	private $missing_files = Array();
	private $fastfile;
	private $offsets;
	private $console;
	private $extractDir;
	private $dumpDir;
	private $cli_command;
	public function __construct($file, $console)
	{
		$this->fastfile = $file;
		$this->console = $console;
		if (stristr(PHP_OS, 'WIN'))
		{
			$this->cli_command = ".".DS."offzip.exe";
		}
		else
		{
			$this->cli_command = "wine .".DS."offzip.exe";
		}
	}

	public function decompress($dir, $xml)
	{
		$this->offsets = simplexml_load_file($xml);
		if($this->offsets == false) return;
		if(is_dir($dir)) $this->rrmdir($dir);
		$this->extractDir = $dir.DS."scripts";
		$this->dumpDir = $dir.DS."raw";
		mkdir($dir);
		mkdir($this->extractDir);
		mkdir($this->dumpDir);

		if($this->console == "ps3")
		shell_exec($this->cli_command." -a -z -15 ".$this->fastfile." ".$this->dumpDir." 0");
		else if($this->console == "xbox")
		shell_exec($this->cli_command." -a ".$this->fastfile." ".$this->dumpDir." 0");
		$this->writeScripts();
	}
	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir.DS.$object) == "dir") $this->rrmdir($dir.DS.$object); else unlink($dir.DS.$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
	private function writeScripts()
	{
		foreach($this->offsets->file as $file)
		{
			$this->extractData($file);
		}
	}

	private function extractData(SimpleXMLElement $data)
	{
		foreach($data->datafile as $part)
		{
			$this->extractPart($part,$data["name"]);
		}
	}
	private function extractPart(SimpleXMLElement $part, $file)
	{
		$source = $this->locateDumpFile($part["name"]);
		if($source == false)
		{
			$this->missing_files[] = array
			(
			"dump"	=>	$part["name"],
			"file"	=>	$file
			);
			return;
		}
		$spos = $part["startpos"];
		$epos = $part["endpos"];
		$source_fhandle = fopen($this->dumpDir.DS.$source,"r");
		$file_fhandle = fopen($this->extractDir.DS.$file,"a+");
		fseek($source_fhandle, (int) $spos);
		$size = (int) $epos - (int) $spos;
		$len = 0;
		while(!feof($source_fhandle) && $len <= $size)
		{
			$char = fgetc($source_fhandle);
			if($char != "\0")
			fwrite($file_fhandle, $char);
			$len++;
		}
		fclose($source_fhandle);
		fclose($file_fhandle);
	}
	private function locateDumpFile($offset)
	{
		$files = scandir($this->dumpDir);
		foreach($files as $file)
		{
			$info = pathinfo($this->dumpDir.DS.$file);
			$name= $info["basename"];
			if($name == $offset) return $file;
		}
		return false;
	}
	public function getMissingFiles()
	{
		if(count($this->missing_files) > 0)
		return $this->missing_files;
		else
		return false;
	}
}
?>