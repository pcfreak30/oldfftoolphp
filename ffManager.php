<?php
/*
Program: ffManager PHP Version
Author: Derrick J. Hammer
Author Alias: PCFreak30
License: GPL v2 http://www.gnu.org/licenses/gpl.html
Last Updated: 6-17-11
*/

echo <<<OUT

ffManager PHP Interactive Tool
------------------------------
Created by Derrick Hammer, A.K.A. PCFreak30
Released under GPLv3 License, http://www.gnu.org/licenses/gpl.html

Visit PCFreak30.com for News and Updates

Visit SimplyHacks.com for Releases and a fun, leech-free community..
------------------------------

OUT;

$valid_fastfile = false;
$valid_profile = false;
$valid_console = false;
$valid_action = false;
$fastfile = "";
$game = "";
$console = "";
$profile = "";
define("DS", DIRECTORY_SEPARATOR);
libxml_use_internal_errors(true);
if (stristr(PHP_OS, 'WIN'))
{
	$cli_clear = "cls";
}
else
{
	$cli_clear = "clear";
}
while(!$valid_fastfile)
{
	$p = getInput("Please Drag and Drop a FastFile into this window, then press ENTER:\n");
	$p=  str_replace("\"","",trim($p));
	if($p != "" && file_exists($p))
	{

		if(is_valid_ff($p))
		{
			$supported = is_supported_game($p);
			if($supported != false)
			{
				$game = $supported;
				$fastfile = $p;
				$valid_fastfile = true;
			}
			else
				fputs(STDOUT, "That FastFile is for a game that is NOT supported\n");
		}
			
	}
	else
	{
		fputs(STDOUT,"Invalid FastFile\n");
	}
}
fputs(STDOUT,"\n\n");
switch($game)
{
	case "cod4":
		fputs(STDOUT,"Call of Duty 4 FastFile Detected");
	break;
	case "cod5":
		fputs(STDOUT,"Call of Duty World at War FastFile Detected");
	break;
	case "mw2":
		fputs(STDOUT,"Modern Warfare 2 FastFile Detected");
	break;
	
}
fputs(STDOUT,"\n\n");
while(!$valid_profile)
{
	$p = getInput("Please Drag and Drop a FastFile XML Profile into this window, then press ENTER:\n");
	$p=  str_replace("\"","",trim($p));
	if($p != "" && file_exists($p))
	{
		if(!simplexml_load_file($p))
		{
			fputs(STDOUT, "XML Parse ERROR(s):\n\n");
			    foreach(libxml_get_errors() as $error)
				{
					print "\n". $error->message;
				}
		}
		else
		{
			$valid_profile = true;
			$profile = $p;
		}
	}
}
while(!$valid_console)
{
	$p = getInput("Please give the console type for this FastFile:..Do know that if you give the wrong 
one, extraction will have possibly random results and you will have to re-run this tool..\n\n Valid 
options are \"ps3\" and \"xbox\" without the \"\n\n Console:\n");
	$p=  str_replace("\"","",trim($p));
	if($p != "" && ($p == "xbox" || $p == "ps3"))
	{
		$console = $p;
		$valid_console = true;
	}
}
$fastfile_parts = pathinfo($fastfile);
$fastfile_extract_dir = $fastfile_parts["dirname"].DS.$fastfile_parts["filename"]."_extract";
while(!$valid_action)
{
	$p = getInput("What do you wish to do?\n\n You can \"extract\" or \"compress\"\n\n Action:\n");
	$p=  str_replace("\"","",trim($p));
	if($p != "" && $p == "compress" || $p == "extract")
	{
		$cod;
		switch($game)
		{
			case "cod4":
				switch($p)
				{
					case "extract":
						require ".".DS."lib".DS."cod4_decompress.class.php";
						$cod= new COD4_Decompress($fastfile,$console);
						break;
					case "compress":
						require ".".DS."lib".DS."cod4_compress.class.php";
							$cod= new COD4_Compress($fastfile,$console);
						break;
				}
				break;
			case "cod5":
				switch($p)
				{
					case "extract":
						require ".".DS."lib".DS."cod5_decompress.class.php";
						$cod= new COD5_Decompress($fastfile,$console);
						break;
					case "compress":
						require ".".DS."lib".DS."cod5_compress.class.php";
						$cod= new COD5_Compress($fastfile,$console);
						break;
				}
				break;
			case "mw2":
				switch($p)
				{
					case "extract":
						require ".".DS."lib".DS."mw2_decompress.class.php";
						$cod= new MW2_Decompress($fastfile,$console);
						break;
					case "compress":
						require ".".DS."lib".DS."mw2_compress.class.php";
						$cod= new MW2_Compress($fastfile,$console);
						break;
				}
				break;
		}
		
		if($p == "extract")
		{
			$cod->decompress($fastfile_extract_dir,$profile);
		}
		elseif($p == "compress")
		{
			$cod->compress($fastfile_extract_dir,$profile);
		}
		
		$errors = $cod->getMissingFiles();
		if(is_array($errors))
		{
			foreach($errors as $error)
			{
				print "Missing Data ".$error["dump"]." for file ".$error["file"]."\n";
			}
		}
		print "\n\n\n";
		if($p == "compress")
		{
			$overflow = $cod->getOverflow();
			if(is_array($overflow))
			{
				foreach($overflow as $o)
				{
					print "Too many bytes in ".$o["name"].". Max is ".$o["size"].".. Overflow is ". $o["overflow"]."\n";
				}
			}
		}
		$valid_action = true;
	}
}
function getInput($msg)
{
	fputs(STDOUT, $msg);
	return fgets(STDIN);
}

function is_valid_ff($file)
{
	$handle = @fopen($file, "r");
	$data = "";
	for($i=0; $i < 8; $i++)
	{
		$data .= fgetc($handle);
	}
	if($data == "IWffs100")
	{
		fputs(STDOUT,"Signed FastFiles are NOT supported\n");
		return false;
	}
	elseif($data != "IWffu100" && $data != "IWff0100")
	{
		fputs(STDOUT,"File NOT a FastFile\n");
		return false;
	}
	return true;
}

function is_supported_game($file)
{
	$handle = fopen($file, "r");
	$data = "";
	fseek($handle, 10);
	$data = "";
	for($i=0; $i < 2; $i++)
	{
		$data .= ord(fgetc($handle));
	}
	$data = (int) $data;
	switch($data)
	{
		case 1:
			return "cod4";
		case 113:
			return "mw2";
		case 1131:
			return "cod5";
		default:
			return false;
	}
}
?>