<?php


include("func.php");
include("praser.php");

function RunAsm($filename){
	global $Output, $Contents, $name;

	$extension = str_chop($filename, $left_chr='.', $right_chr='');
	
	$name_arr = explode("\\", $filename);
	$name = str_chop($name_arr[count($name_arr)-1], $left_chr='', $right_chr='.');

	$Contents = read_file($filename);

	praser_LoadDef();
	praser_ModuleStart();

	return;
}

namespace ASM1802_Class;

class ASM1802
{
	function main($filename): string {
		return RunAsm($filename);
	}

	function getFilename(){
		global $name;
		return $name;
	}

	function setFilename($n){
		global $name;
		$name = $n;
	}

	function getOutputBin(){
		global $Output;
		asm1802_save_as_bin($Output);
	}
	
	function getOutputHex(){
		global $Output;
		asm1802_save_as_hex($Output);
	}	

	function getOutputCOF(){
		global $Output;
		asm1802_save_as_cof($Output);
	}
	
	function getOutputVHDL(){
		global $Output;
		asm1802_save_as_vhd($Output);
	}
	
	function getOutputMem(){
		global $Output;
		asm1802_save_as_mem($Output);
	}
}
