<?php
  $Output=array(); $asm1802_Calls=array(); $PC=0;

  echoLog ("\r\n\r\n-=== Loaded 1802 Assembler Definition ===-\r\n\r\n");

  function asm1802_load()
  {
    global $Contents, $Output ;

    $Contents = asm1802_CleanUp($Contents);
    asm1802_GetCalls();
    $Output=array();
    asm1802_run();
  }

  function asm1802_GetCalls()
  {
    global $Contents, $asm1802_Calls, $PC, $Output, $name, $Log;

    $PC=0; $tpc = 0; $str = array();
    foreach($Contents as $key=>$val)
    {
      if(strpos($val, ":")>-1 && !(strpos($val, "\"")>-1)){
		//echo str_chop($val,"",":"). ": ".fix_zero(dechex($PC),4)."\r\n";
        $asm1802_Calls[str_chop($val,"",":")] = $PC;
        $val = "";
      } elseif (strpos($val, "#define")>-1) {
        $Split1 = explode(" ", $val);
        $vals = array_slice($Split1, 1);
        $vals = asm1802_ValConverter($vals, $Split1[0]);
        $vals1 = array_slice($Split1, 1);
        $asm1802_Calls[$vals1[0]] = hexdec($vals[1]);
        $val = "";
      }else{
        $Split1 = explode(" ", $val);
		$vals = array_slice($Split1, 1);
        $vals = asm1802_ValConverter($vals, $Split1[0]);
		$temp = fix_zero(dechex($PC),4);
        $tpc =  call_user_func("asm1802_func_". $Split1[0], $vals, $key+1, $val);
		$PC += $tpc;
      }
	  //if(strlen($Output[count($Output)-1])<32){
	  //  echoLog ("$temp:\t" . $Output[count($Output)-1] . "\t\t\t$val\t$tpc\r\n\r\n");
     // }else{
	//	echoLog ("$temp:\tlong...\t\t\t$val\t$tpc\r\n\r\n");  
	  //}
      if($val!="") $str[] = $val;
    }
    $Contents = $str;
	if(count($asm1802_Calls) > 0){
		echoLog ("List of all call address's\r\n");
		foreach($asm1802_Calls as $key=>$val) {
            echoLog (fix_spaces("$key:", 26) . fix_zero(dechex($val),4) . "\r\n");
        }
		echoLog ("------------------------------\r\n\r\n");
	}
  }

  function asm1802_run()
  {
	global $Contents, $PC, $Output, $Log;
	$PC=0;
	$no=0;
	foreach($Contents as $key=>$val)
	{
		$callName = findCall($PC);
		if($callName != null){
            echoLog ("\r\n$callName:\r\n");
        }
		$Split1 = explode(" ", $val);
		$vals = array_slice($Split1, 1);
		$vals = asm1802_ValConverter($vals, $Split1[0]); ; 
		$temp = fix_zero(dechex($PC),4);
		$tpc = call_user_func("asm1802_func_". $Split1[0], $vals, $key+1, $val);
		$PC += $tpc;
		if(strlen($Output[count($Output)-1])<32){
		    echoLog (fix_spaces("\t$temp: " . $Output[count($Output)-1], 20) . fix_spaces($val, 20) . "$tpc\r\n");
		}else{
		    echoLog (fix_spaces("\t$temp: long ", 20) . fix_spaces($val, 20) . "$tpc\r\n"); 
		}
	}
  }

  function findCall($addr){
	global $asm1802_Calls;
	foreach($asm1802_Calls as $key=>$val){
		if($addr==$val) return $key;
	}
	return null;
  }
  
  function asm1802_done()
  {
    global $Contents, $Output, $name;

	$out="";

	foreach($Output as $val) $out .= $val;
    $Output = $out;
    //var_dump($out);
}

function asm1802_save_as_vhd($out) {
    global $name;
    $len = (strlen($out)/2)-1;
    $bits = log2Up($len);
    $name_rom = "$name"."_rom";
    $data = "";
    for($i=0;$i<strlen($out)/2;$i++) $data .= "        x\"" . substr($out,$i*2,2) . "\",\r\n";
    $data = substr($data,0,-3);
    $fout = <<<VHDL
library ieee;
use ieee.std_logic_1164.all;
use ieee.numeric_std.all;

entity $name_rom is
    port (
        clk: in std_logic;
        addr: in std_logic_vector($bits downto 0);
        data: out std_logic_vector(7 downto 0)
    );
end entity;

architecture Behavioral of $name_rom is

    type mtype is array(0 to $len) of std_logic_vector(7 downto 0);

    constant rom_data: mtype := (
$data
    );
    signal add_int: integer range 0 to $len;
begin
    add_int <= to_integer(unsigned(addr));
    process(clk) begin
        if rising_edge(clk) then
            if add_int <= $len then
                data <= rom_data(add_int);
            else
                data <= (others => '0');
            end if;
        end if;
    end process;
end Behavioral;
VHDL;
    write_file($fout, $name .".vhd");
}


function asm1802_save_as_cof($out) {
    global $name;
    $padding = 256 - (strlen($out)/2);
    for($i=0;$i<strlen($out)/2;$i++) $fout .= substr($out,$i*2,2) . ",\r\n";
    for($i=0;$i < $padding; $i++) $fout .= "00" . ",\r\n";
    $fout = "memory_initialization_radix=16;\r\nmemory_initialization_vector=\r\n" . $fout;
    $fout = substr($fout,0,-3);
    write_file($fout, $name .".coe");

}

function asm1802_save_as_hex($out){
    global $name;
    for($i=0;$i<strlen($out)/2;$i++) $fout .= substr($out,$i*2,2) . " ";
    write_file($fout,"$name.hex");
}

function asm1802_save_as_bin($out){
    global $name;
    for($i=0;$i<strlen($out)/2;$i++) $fout .= chr(hexdec(substr($out,$i*2,2)));
    write_file($fout, $name . ".bin");
}
//===============================================================================

  function asm1802_func_adc($vals, $line, $val)
  {
    global $Output;
    $Output[] = "74";
    return 1;
  }

  function asm1802_func_adci($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("ADCI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("7C" . $vals[0]);
    return 2;
  }

  function asm1802_func_add($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F4");
    return 1;
  }

  function asm1802_func_adi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("ADI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("FC" . $vals[0]);
    return 2;
  }

  function asm1802_func_and($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F2");
    return 2;
  }

  function asm1802_func_ani($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("ANI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("FA" . $vals[0]);
    return 2;
  }

  function asm1802_func_b1($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("B1 Take One Byte Values On Line: $line\r\n");

    $Output[] = strtoupper("34" . $vals[0]);
    return 2;
  }

  function asm1802_func_b2($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("B2 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("35" . $vals[0]);
    return 2;
  }

  function asm1802_func_b3($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("B3 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("36" . $vals[0]);
    return 2;
  }

  function asm1802_func_b4($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("B4 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("37" . $vals[0]);
    return 2;
  }

  function asm1802_func_bdf($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BDF Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("33" . $vals[0]);
    return 2;
  }

  function asm1802_func_bn1($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BN1 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("3C" . $vals[0]);
    return 2;
  }

  function asm1802_func_bn2($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BN2 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("3D" . $vals[0]);
    return 2;
  }

  function asm1802_func_bn3($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BN3 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("3E" . $vals[0]);
    return 2;
  }

  function asm1802_func_bn4($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BN4 Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("3F" . $vals[0]);
    return 2;
  }

  function asm1802_func_bnf($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BNF Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("3B" . $vals[0]);
    return 2;
  }

  function asm1802_func_bnq($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BNQ Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("39" . $vals[0]);
    return 2;
  }

  function asm1802_func_bnz($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BNZ Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("3A" . $vals[0]);
    return 2;
  }

  function asm1802_func_bq($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BQ Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("31" . $vals[0]);
    return 2;
  }

  function asm1802_func_br($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BR Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("30" . $vals[0]);
    return 2;
  }

  function asm1802_func_bz($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("BZ Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("32" . $vals[0]);
    return 2;
  }

  function asm1802_func_dec($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("DEC Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("2" . $vals[0]);
    return 1;
  }

  function asm1802_func_dis($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("71");
    return 1;
  }

  function asm1802_func_ghi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("GHI Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("9" . $vals[0]);
    return 1;
  }

  function asm1802_func_glo($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("GLO Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("8" . $vals[0]);
    return 1;
  }

  function asm1802_func_idl($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("00");
    return 1;
  }

  function asm1802_func_inc($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("INC Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("1" . $vals[0]);
    return 1;
  }

  function asm1802_func_inp($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("INP Take One Nibble Values On Line: $line\r\n");
	$b = $vals[0];
	$vals[0] = dechex(hexdec($vals[0])+8);

	$Output[] = strtoupper("6" . $vals[0]);
    return 1;
  }

  function asm1802_func_irx($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("60");
    return 1;
  }

  function asm1802_func_lbdf($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBDF Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("C3" . $vals[0]);
    return 3;
  }

  function asm1802_func_lbnf($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBNF Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("CB" . $vals[0]);
    return 3;
  }

  function asm1802_func_lbnq($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBNQ Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("C9" . $vals[0]);
    return 3;
  }

  function asm1802_func_lbnz($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBNZ Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("CA" . $vals[0]);
    return 3;
  }

  function asm1802_func_lbq($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBQ Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("C1" . $vals[0]);
    return 3;
  }

  function asm1802_func_lbr($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBR Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("C0" . $vals[0]);
    return 3;
  }

  function asm1802_func_lbz($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 4) echoLog ("LBZ Take Two Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("C2" . $vals[0]);
    return 3;
  }

  function asm1802_func_lda($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("LDA Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("4" . $vals[0]);
    return 1;
  }

  function asm1802_func_ldi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("LDI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("F8" . $vals[0]);
    return 2;
  }

  function asm1802_func_ldn($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("LDN Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("0" . $vals[0]);
    return 1;
  }

  function asm1802_func_ldx($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F0");
    return 1;
  }

  function asm1802_func_ldxa($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("72");
    return 1;
  }

  function asm1802_func_lsdf($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("CF");
    return 1;
  }

  function asm1802_func_lsie($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("CC");
    return 1;
  }

  function asm1802_func_lskp($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("C8");
    return 1;
  }

  function asm1802_func_lsnf($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("C7");
    return 1;
  }

  function asm1802_func_lsnq($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("C5");
    return 1;
  }

  function asm1802_func_lsnz($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("C6");
    return 1;
  }

  function asm1802_func_lsq($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("CD");
    return 1;
  }

  function asm1802_func_lsz($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("CE");
    return 1;
  }

  function asm1802_func_mark($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("79");
    return 1;
  }

  function asm1802_func_nop($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("C4");
    return 1;
  }

  function asm1802_func_or($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F1");
    return 1;
  }

  function asm1802_func_ori($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("ORI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("F9" . $vals[0]);
    return 2;
  }

  function asm1802_func_out($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("OUT Take One Nibble Values On Line: $line\r\n");
	if(!$vals[1]){
		$Output[] = strtoupper("6" . $vals[0]);
		return 1;
	}else{
		$Output[] = strtoupper("6" . $vals[0] . $vals[1]);
		return 2;
	}
  }

  function asm1802_func_in($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("OUT Take One Nibble Values On Line: $line\r\n");
	if(!$vals[1]){
		$Output[] = strtoupper("6" . dechex(8 + $vals[0]));
		return 1;
	}else{
		$Output[] = strtoupper("6" . $vals[0] . $vals[1]);
		return 2;
	}
  }

  function asm1802_func_idle($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("00");
    return 1;
  } 
  
  function asm1802_func_phi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("PHI Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("B" . $vals[0]);
    return 1;
  }

  function asm1802_func_plo($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("PLO Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("A" . $vals[0]);
    return 1;
  }

  function asm1802_func_req($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("7A");
    return 1;
  }

  function asm1802_func_ret($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("70");
    return 1;
  }

  function asm1802_func_sav($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("78");
    return 1;
  }

  function asm1802_func_sd($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F5");
    return 1;
  }

  function asm1802_func_sdb($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("75");
    return 1;
  }

  function asm1802_func_sdbi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("SDBI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("7D" . $vals[0]);
    return 2;
  }

  function asm1802_func_sdi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("SDI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("FD" . $vals[0]);
    return 2;
  }

  function asm1802_func_sep($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("SEP Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("D" . $vals[0]);
    return 1;
  }

  function asm1802_func_seq($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("7B");
    return 1;
  }

  function asm1802_func_sex($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("SEX Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("E" . $vals[0]);
    return 1;
  }

  function asm1802_func_shl($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("FE");
    return 1;
  }

  function asm1802_func_shlc($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("7E");
    return 1;
  }

  function asm1802_func_shr($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F6");
    return 1;
  }

  function asm1802_func_shrc($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("76");
    return 1;
  }

  function asm1802_func_skp($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("38");
    return 1;
  }

  function asm1802_func_sm($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F7");
    return 1;
  }

  function asm1802_func_smb($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("77");
    return 1;
  }

  function asm1802_func_smbi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("SMBI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("7F" . $vals[0]);
    return 2;
  }

  function asm1802_func_smi($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("SMI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("FF" . $vals[0]);
    return 2;
  }

  function asm1802_func_str($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 1) echoLog ("STR Take One Nibble Values On Line: $line\r\n");
    $Output[] = strtoupper("5" . $vals[0]);
    return 1;
  }

  function asm1802_func_stxd($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("73");
    return 1;
  }

  function asm1802_func_xor($vals, $line, $val)
  {
    global $Output;
    $Output[] = strtoupper("F3");
    return 1;
  }

  function asm1802_func_xri($vals, $line, $val)
  {
    global $Output;
    if(strlen($vals[0]) > 2) echoLog ("XRI Take One Byte Values On Line: $line\r\n");
    $Output[] = strtoupper("FB" . $vals[0]);
    return 2;
  }
//===============================================================================

  function asm1802_func_db($vals, $line, $val)
  {
    global $Output;

	$out="";

	foreach($vals as $val)
	{
		$out .= $val;
	}
    $Output[] = strtoupper($out);
    return (strlen($out)/2);
  }

  function asm1802_func_dbstr($vals, $line, $val)
  {
    global $Output;

	$t = str_chop($val,"dbstr \"","\"");
	$out="";

	for($i=0;$i<strlen($t);$i++)
	{
		$out .= fix_zero(dechex(ord(substr($t, $i, 1))));
	}
	$out .= "00";
	$Output[] = strtoupper($out);
    return (strlen($out)/2);
  }

  function asm1802_func_dbrevstr($vals, $line, $val)
  {
    global $Output;

	$t = str_chop($val,"dbrevstr \"","\"");
    $t = strrev($t);
	$out="";

	for($i=0;$i<strlen($t);$i++)
	{
		$out .= fix_zero(dechex(ord(substr($t, $i, 1))));
	}
	$out .= "00";
	$Output[] = strtoupper($out);
    return (strlen($out)/2);
  }

  function asm1802_func_alloc($vals, $line, $val)
  {
    global $Output;

	$out="";

	for($i=0;$i<hexdec($vals[0]);$i++)
	{
		$out = $out . "00";
	}

    $Output[] = strtoupper($out);
    return (strlen($out)/2);
  }

  function asm1802_func_load($vals, $line, $val)
  {
    global $Output;

	$out = bin2hex(read_file($vals[0]));
    $Output[] = strtoupper($out);
    return (strlen($out)/2);
  }

  function asm1802_func_setpc($vals, $line, $val)
  {
	global $PC;
	$PC = hexdec($vals[0]);
	return 0;
  }

  function asm1802_func_addpc($vals, $line, $val)
  {
	global $PC;
	$PC = $PC + hexdec($vals[0]);
	return 0;
  }

  function asm1802_func_space($vals, $line, $val)
  {
    global $Output, $PC;

	$out = "";

	for($i=0;$i <= hexdec($vals[0]) - ($PC+1); $i++)
	{
		$out .= "00";
	}

    $Output[] = strtoupper($out);

    return (strlen($out)/2);
  }

    function asm1802_func_addspace($vals, $line, $val)
  {
    global $Output, $PC;

	$out = "";

	for($i=0;$i <= hexdec($vals[0]); $i++)
	{
		$out .= "00";
	}

    $Output[] = strtoupper($out);

    return (strlen($out)/2);
  }
//===============================================================================

  function asm1802_ValConverter($vals, $func)
  {
    global $asm1802_Calls, $PC;
    $out = array();
    $last = "";
	$valO = "";

    foreach($vals as $key=>$val)
    {
      if($last == "low" && count($asm1802_Calls)>0 && array_key_exists($val,  $asm1802_Calls))
      {
        foreach($asm1802_Calls as $key1=>$val1)
        {
          if($key1 == $val)
          {
			if(substr($func, 0, 1) == "b" && fix_zero(dechex(($val1 & 0xFF00)/256)) !==  fix_zero(dechex(($PC & 0xFF00)/256))) echoLog ("Branch Error - " . fix_zero(dechex($PC),4) . ":	$func");

			$val = "#".($val1 & 0xFF);
			break;
          }
        }
      }else if($last == "low"){$val = "00";}

      if($last == "high" && count($asm1802_Calls)>0 && array_key_exists($val,  $asm1802_Calls))
      {
        foreach($asm1802_Calls as $key1=>$val1)
        {
          if($key1 == $val)
          {
           $val = "#".(($val1 & 0xFF00)/256);
           break;
          }
        }
      }else if($last == "high"){$val = "00";}

	  if(count($asm1802_Calls)>0 && array_key_exists($val,  $asm1802_Calls)){
		  foreach($asm1802_Calls as $key1=>$val1)
		  {
				if($key1 == $val)
				{
					 $val = "$".($val1);
					 break;
				}
		  }
	  }else

	  if($func == "load" && $key==0)
	  {
		$valO = $val;
	  }

	  if($valO=="")
	  {
		  if($val!=="high" && $val!=="low")$valO = $val;
		  if(strpos($val, "#")>-1) $valO = fix_zero(dechex(intval(str_chop($val,"#",""))));
		  if(strpos($val, "$")>-1) $valO = fix_zero(dechex(intval(str_chop(strtolower($val),"$",""))),4);
		  if(strpos($val, "@")>-1) $valO = bin2hex(str_chop($val,"@",""));
		  if(strpos($val, "%")>-1) $valO = fix_zero(dechex(bindec(str_chop($val,"%",""))));
		  if(strpos($val, "r")>-1) $valO = substr(str_chop($val,"r",""),0,1);
		  if(strpos($val, "n")>-1) $valO = substr(str_chop($val,"n",""),0,1);
		  if(strpos($val, "'")>-1) $valO = fix_zero(dechex(ord(str_chop($val,"'",""))));
	  }
	  //echoLog ("valO:" . $valO . "\r\n");
	  if($func !== "load" && $last == "" && !($val == "low" || $val == "high") && val != "" && !is_hex($valO)){$valO = "0000";}
	  //echoLog ("last:" . $last . ", func:" . $func .", val:" . $val .  ", valO:" . $valO . "\r\n");
      $last=$val;
      if($valO!="") $out[] = $valO;
      $valO = "";
    }

    return $out;
  }

  function asm1802_CleanUp($str)
  {
    global $Modules;

    $strSplit1 = explode("\r\n", $str);
    $str = array();
    foreach($strSplit1 as $val)
    {
        $lower = strtolower($val);

        while(intval(substr_count($val, '  '))>0) $val = str_replace(array("  "), array(" "), $val);

        $val = preg_replace(array("(, )", "(,)", "/^(\s)*/", "/--.*$/", "/;.*$/", "/\t./"), array(" ", " ", "", "", "", ""), $val);

        if($val!="") $str[] = $val;
    }
    return $str;
  }
?>
