<?php
/*
Program: ffManager PHP Version
Author: Derrick J. Hammer
Author Alias: PCFreak30
License: GPL v2 http://www.gnu.org/licenses/gpl.html
Last Updated: 6-17-11
*/
@define("DS", DIRECTORY_SEPARATOR);
class COD4_Compress
{
	private $missing_files = Array();
	private $overFlow_files = Array();
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
			$this->cli_command = ".".DS."packzip.exe";
		}
		else
		{
			$cli_command = "wine .".DS."packzip.exe";
		}
	}

	public function compress($dir, $xml)
	{
		$this->offsets = simplexml_load_file($xml);
		if($this->offsets == false) return;
		$this->extractDir = $dir.DS."scripts";
		$this->dumpDir = $dir.DS."raw";
		$this->packData();

		$process_files = array();

		foreach($this->offsets->file as $file)
		{
			print "Processing ".$file["name"]."\n";
			foreach($file->datafile as $dat)
			{
				if(!in_array($dat["name"],$process_files))
				{
					if($this->console == "ps3")
					{
						$data_file = $this->locateDumpFile($dat["name"]);
						if($data_file != false)
						shell_exec($this->cli_command." -w -15 -o 0x".$dat["name"]." \"".$this->dumpDir.DS.$data_file."\" \"".$this->fastfile."\"");

					}
					else if($this->console == "xbox")
					{
						$data_file = $this->locateDumpFile($dat["name"]);
						if($data_file != false)
						shell_exec($this->cli_command." -o 0x".$dat["name"]." \"".$this->dumpDir.DS.$data_file."\" \"".$this->fastfile."\"");
					}
					$process_files[] = $dat["name"];
				}
			}
		}
	}
	private function checkSize($file, $size)
	{
		$info = stat($this->extractDir.DS.$file);
		$size =  $size;
		if($info["size"] > $size)
		{
			$this->overFlow_files [] = array
			(
				"name"	=> $file,
				"size"	=> $size,
				"overflow"	=> $info["size"] - $size
			);
			return false;
		}
		else
		return $size - $info["size"];
	}

	private function packData()
	{
		foreach($this->offsets->file as $file)
		{
			$size = $this->checkSize($file["name"],$file["size"]);
			if($size != false)
			{
				$pos = 0;
				if($size > 0)
				$this->fillPadding($file["name"],$size);
				foreach($file->datafile as $data)
				{
					$this->packPart($data,$file["name"], $pos);
					$pos += $data["endpos"] - $data["startpos"];
				}
			}
		}
	}
	private function packPart(SimpleXMLElement $part, $file, $foffset)
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
		$spos = (int) $part["startpos"];
		$epos =(int)  $part["endpos"];
		$source_fhandle = fopen($this->dumpDir.DS.$source,"a+");
		$file_fhandle = fopen($this->extractDir.DS.$file,"r");
		$temp_handle =  fopen($this->dumpDir.DS."temp.dat","w");
		$size = $epos -  $spos;
		$len = 0;
		while(!feof($source_fhandle) && $len < $spos)
		{
			$char = fgetc($source_fhandle);
			fwrite($temp_handle, $char);
			$len++;
		}
		$len = 0;
		fseek($file_fhandle, $foffset);
		while(!feof($file_fhandle) && $len < $size)
		{
			$char = fgetc($file_fhandle);
			fwrite($temp_handle, $char);
			$len++;
		}
		fseek($source_fhandle, $epos);
		unset($len);
		while(!feof($source_fhandle))
		{
			$char = fgetc($source_fhandle);
			fwrite($temp_handle, $char);
		}
		fclose($source_fhandle);
		fclose($file_fhandle);
		fclose($temp_handle);
		copy($this->dumpDir.DS."temp.dat",$this->dumpDir.DS.$source);
		@unlink($this->dumpDir.DS."temp.dat");
	}
	private function locateDumpFile($offset)
	{
		$files = scandir($this->dumpDir);
		foreach($files as $file)
		{
			$info = pathinfo($this->dumpDir.DS.$file);
			$name= $info["filename"];
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
	public function getOverflow()
	{
		if(count($this->overFlow_files) > 0)
		return $this->overFlow_files;
		else
		return false;
	}
	private function fillPadding($file, $num)
	{
		$fhandle= fopen($this->extractDir."/".$file,"a");
		for($i=0; $i < $num; $i++)
		{
			fwrite($fhandle, "\0");
		}
		fclose($fhandle);
	}
}
?>
