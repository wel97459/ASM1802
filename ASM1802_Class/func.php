<?php

	function echoLog($str){
		$Console = new Helper\Console;
		$Console->Write($str);
	}
	
	function echoDump($obj){
		$Console = new Helper\Console;
		$Console->WriteLine(var_export($obj, true));
	}

    function log2($x){
        return (log10($x) / log10(2));
    }

    function log2Up($x)
    {
        return ceil(log2($x));
    }

    function str_chop($input, $left_chr='', $right_chr='', $cutLeft=0, $cutRight=0)
    {
    	if($left_chr !== ""){
    		$input = strstr($input, $left_chr);
    		$input = substr($input, strlen($left_chr)-$cutLeft);
    	}

    	if($right_chr !== ""){
    		$input=strrev(strstr(strrev($input),strrev($right_chr)));
    		$input=substr($input, 0, -strlen($right_chr)-$cutRight);
    	}
    	return $input;
    }

    function read_file ($file_name)
    {
         	clearstatcache();
         	$file = fopen($file_name, "r");
         	flock($file, LOCK_SH);
         	$content = fread($file, filesize($file_name));
         	flock($file, LOCK_UN);
         	fclose ($file);
         	return ($content);
    }

    function write_file ($content, $file_name)
    {
          $file = fopen($file_name, "w");
          flock($file, LOCK_EX);
          fwrite($file, $content);
          flock($file, LOCK_UN);
          fclose ($file);
    }

    function is_hex($hexValue){
		$zeros = strlen($hexValue);
		$hex = strtoupper(dechex(hexdec($hexValue)));
		if($zeros < 2) $zeros = 2; else
		if($zeros >= 2) $hex = fix_zero($hex, $zeros);
		//echo strtoupper($hexValue) . " == " . $hex . "\r\n";
        if(strtoupper($hexValue) == $hex)
            return true;
        return false;
    }

	function fix_zero($hexValue, $n=2)
	{
		while(strlen($hexValue)/$n  > floor(strlen($hexValue)/$n)){$hexValue="0".$hexValue;}
		return strtoupper($hexValue);
	}

	function fix_spaces($str, $n)
	{
		$s = $str;
		while($n > strlen($s)){
			$s .= " ";
		}
		return $s;
	}

	function HexToAsc($hex)
	{
	  for($i=0;$i<strlen($hex);$i++)
	  {
	    $h = substr($hex, $i, 1);
	    switch($h)
	    {
	      case "0":
	        $bin = "0000";
	        break;
	      case "1":
	        $bin = "0001";
	        break;
	      case "2":
	        $bin = "0010";
	        break;
	      case "3":
	        $bin = "0011";
	        break;
	      case "4":
	        $bin = "0100";
	        break;
	      case "5":
	        $bin = "0101";
	        break;
	      case "6":
	        $bin = "0110";
	        break;
	      case "7":
	        $bin = "0111";
	        break;
	      case "8":
	        $bin = "1000";
	        break;
	      case "9":
	        $bin = "1001";
	        break;
	      case "A":
	        $bin = "1010";
	        break;
	      case "B":
	        $bin = "1011";
	        break;
	      case "C":
	        $bin = "1100";
	        break;
	      case "D":
	        $bin = "1101";
	        break;
	      case "E":
	        $bin = "1110";
	        break;
	      case "F":
	        $bin = "1111";
	        break;
	      case "a":
	        $bin = "1010";
	        break;
	      case "b":
	        $bin = "1011";
	        break;
	      case "c":
	        $bin = "1100";
	        break;
	      case "d":
	        $bin = "1101";
	        break;
	      case "e":
	        $bin = "1110";
	        break;
	      case "f":
	        $bin = "1111";
	        break;
	    }
	      $out .=  $bin;
	  }
	  return $out;
	}

?>
