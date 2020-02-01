<?php
  $Output=array(); $P = 0; $X = 0; $Offset = 0x0; $CurrentAdd = 0;
  $Calls=array();
  $DataCalls=array();
  $Regs=array();
  echoLog ("\r\n\r\n-=== Loaded 1802 Disassembler Definition ===-\r\n\r\n");

  function dsm1802_load()
  {
	global $Contents, $Regs, $D, $P, $X, $Offset;
	$Contents = dsm1802_CleanUp($Contents);
	$D=0x0; $P = 0; $X = 0;
	for($i = 0; $i < 17; $i++) $Regs[$i]=0;
//	$Regs[0]=$Offset;
	dsm1802_run();
  }
  
  function dsm1802_run()
  {
    global $Contents, $Output, $Offset, $P, $Regs;
    
	for($key = 0;$key < count($Contents);$key++)
    {
	  $val = $Contents[$key];
	  $n0 = strtolower(substr($val,0,1));
	  $n1 = strtolower(substr($val,1,1));
//	  foreach($Regs as $k => $v) {
//			echoLog(fix_zero(dechex($k), 2) . "=>" .fix_zero(dechex($v), 4) . "\r\n");
//	  }
	  $Regs[$P]++;
	  $CurrentAdd = $key;
      $key += call_user_func("dsm1802_func_n" . $n0, $n1, $Contents[$key+1], $Contents[$key+2], $CurrentAdd + $Offset);
	  if($CurrentAdd == $key-1){
		$bytes = strtoupper($val) . " " . strtoupper($Contents[$CurrentAdd+1]);
	  }else if($CurrentAdd == $key-2){
		$bytes = strtoupper($val) . " " . strtoupper($Contents[$CurrentAdd+1]) . " " .strtoupper($Contents[$CurrentAdd+2]);
	  } else $bytes = strtoupper($val);
	  $Output[$CurrentAdd + $Offset] = fix_spaces($Output[$CurrentAdd + $Offset], 20). "--" . fix_zero(dechex($CurrentAdd + $Offset),4) . " $bytes\r\n";
    }
  }
  
  function dsm1802_done()
  {
    global $Contents, $Output, $name, $Offset, $Calls;

	$fout = "?Def=asm1802\r\n\r\nStart:\r\n";
	$lk = 0;
	foreach($Output as $k => $t){
		foreach($Calls as $ck => $cv) {
			if($ck <= $k && $ck < $Offset){
				$fout .= "setpc ". fix_zero(dechex($ck),4) ."\r\n";
				$fout .= "\r\n$cv:\r\n";
				unset($Calls[$ck]);
			}else if($ck > $lk && $ck <= $k){
				$fout .= "\r\n$cv:\r\n";
				unset($Calls[$ck]);
			}
		}
		if($lk < $Offset){
			$fout .= "setpc " . fix_zero(dechex($Offset),4) . "\r\n";
		}
		$fout .= "\t$t";
		$lk = $k;
	}
	 echoLog ($fout);
    write_file($fout,"{$name}.asm");
  }
  
  function dsm1802_CallSetRegSBranch($callName, $v, $CurrentAdd){
		global $Output, $Contents, $P, $X, $Regs, $Calls;
  		$Regs[$P] = ($CurrentAdd & 0xff00) | hexdec($v);
		$call = $callName . "_call" . count($Calls);
		if($Calls[$Regs[$P]] == Null){
			$Calls[$Regs[$P]] = $call;	
		} else {
			$call = $Calls[$Regs[$P]];
		}
		return $call;
  }

    function dsm1802_CallReg($callName, $CurrentAdd){
		global $Output, $Contents, $P, $X, $Regs, $Calls;
		$call = $callName . "_call" . count($Calls);
		if($Calls[$Regs[$P]] == Null){
			$Calls[$Regs[$P]] = $call;	
		} else {
			$call = $Calls[$Regs[$P]];
		}
		return $call;
  }

      function dsm1802_LoadReg($callName, $n, $CurrentAdd){
		global $Output, $Contents, $P, $X, $Regs, $Calls;
		$call = $callName . "_Load" . count($Calls);
		if($Calls[$Regs[$n]] == Null){
			$Calls[$Regs[$n]] = $call;	
		} else {
			$call = $Calls[$Regs[$n]];
		}
		return $call;
  }

  //----------------------------------------------------------------------------
  function dsm1802_func_n0($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $D;
	if($n1 != "0"){
		$Output[$CurrentAdd] = "ldn r{$n1}";
		dsm1802_LoadReg("ldn{$n1}_".fix_zero(dechex($CurrentAdd + $Offset),4), hexdec($n1), $CurrentAdd);
	}else{
		$Output[$CurrentAdd] = "idl"; 
	}
	return 0;
  }
  
  function dsm1802_func_n1($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $Regs;
	$Output[$CurrentAdd] = "inc r{$n1}"; 
	$Regs[hexdec($n1)] ++; 
	return 0;
  }
  
  function dsm1802_func_n2($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
	$Output[$CurrentAdd] = "dec r{$n1}"; 
	$Regs[hexdec($n1)] --; 
	return 0;
  }
  
  function dsm1802_func_n3($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
	
	switch ($n1) {
    case '0':
        $Output[$CurrentAdd] = "br low " . dsm1802_CallSetRegSBranch("br", $v1, $CurrentAdd);
        break;
    case '1':
        $Output[$CurrentAdd] = "bq low " . dsm1802_CallSetRegSBranch("bq", $v1, $CurrentAdd); 
        break;
    case '2':
        $Output[$CurrentAdd] = "bz low " . dsm1802_CallSetRegSBranch("bz", $v1, $CurrentAdd); 
        break;
	case '3':
        $Output[$CurrentAdd] = "bdf low " . dsm1802_CallSetRegSBranch("bdf", $v1, $CurrentAdd); 
        break;
	case '4':
        $Output[$CurrentAdd] = "b1 low " . dsm1802_CallSetRegSBranch("b1", $v1, $CurrentAdd); 
        break;
	case '5':
        $Output[$CurrentAdd] = "b2 low " . dsm1802_CallSetRegSBranch("b2", $v1, $CurrentAdd); 
        break;
	case '6':
        $Output[$CurrentAdd] = "b3 low " . dsm1802_CallSetRegSBranch("b3", $v1, $CurrentAdd); 
        break;
	case '7':
        $Output[$CurrentAdd] = "b4 low " . dsm1802_CallSetRegSBranch("b4", $v1, $CurrentAdd);; 
        break;
	case '8':
        $Output[$CurrentAdd] = "skp";
		return 1;		
	case '9':
        $Output[$CurrentAdd] = "bnq low " . dsm1802_CallSetRegSBranch("bnq", $v1, $CurrentAdd); 
        break;
	case 'a':
        $Output[$CurrentAdd] = "bnz low " . dsm1802_CallSetRegSBranch("bnz", $v1, $CurrentAdd);
        break;
	case 'b':
        $Output[$CurrentAdd] = "bnf low " . dsm1802_CallSetRegSBranch("bnf", $v1, $CurrentAdd);
        break;
	case 'c':
        $Output[$CurrentAdd] = "bn1 low " . dsm1802_CallSetRegSBranch("bn1", $v1, $CurrentAdd); 
        break;
	case 'd':
        $Output[$CurrentAdd] = "bn2 low " . dsm1802_CallSetRegSBranch("bn2", $v1, $CurrentAdd); 
        break;
	case 'e':
        $Output[$CurrentAdd] = "bn3 low " . dsm1802_CallSetRegSBranch("bn3", $v1, $CurrentAdd);
        break;
	case 'f':
        $Output[$CurrentAdd] = "bn4 low " . dsm1802_CallSetRegSBranch("bn4", $v1, $CurrentAdd); 
        break;
	}
	return 1;
  }
  
  function dsm1802_func_n4($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $Regs;
	$Output[$CurrentAdd] = "lda r{$n1}";
	dsm1802_LoadReg("lda{$n1}_".fix_zero(dechex($CurrentAdd + $Offset),4), hexdec($n1), $CurrentAdd);
	$Regs[hexdec($n1)] ++; 
	return 0;
  }
  
  function dsm1802_func_n5($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
	$Output[$CurrentAdd] = "str r{$n1}"; 
	return 0;
  }
  
  function dsm1802_func_n6($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $Regs;
		switch ($n1) {
    case '0':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "irx {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "irx";
		}
        break;
    case '1':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n1\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n1";
		}
        break;
    case '2':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n2\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n2";
		}
        break;
	case '3':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n3\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n3";
		} 
        break;
	case '4':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n4\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n4";
		} 
        break;
	case '5':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n5\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n5";
		} 
        break;
	case '6':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n6\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n6";
		}
        break;
	case '7':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "out n7\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "out n7";
		}
        break;
	case '8':
        $Output[$CurrentAdd] = "db 68"; 
        break;
	case '9':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n1\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n1";
		}  
        break;
	case 'a':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n2\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n2";
		}  
        break;
	case 'b':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n3\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n3";
		}   
        break;
	case 'c':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n4\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n4";
		}   
        break;
	case 'd':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n5\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n5";
		}   
        break;
	case 'e':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n6\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n6";
		}  
        break;
	case 'f':
        if($P == $X){ 
			$Output[$CurrentAdd] = "inp n7\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "inp n7";
		}   
        break;
	} 
	return 0;
  }
  
  function dsm1802_func_n7($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $Regs;
	
	switch ($n1) {
    case '0':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "ret\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "ret";
		}  
        break;
    case '1':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "dis\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "dis";
		}  
        break;
    case '2':
		$Regs[$X] ++;
        if($P == $X){ 
			$Output[$CurrentAdd] = "ldxa\r\n\tdb {$v1}";
			dsm1802_LoadReg("ldxa_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			return 1;
		}else{
			$Output[$CurrentAdd] = "ldxa";
		} 
        break;
	case '3':
		$Regs[$X] --;
        if($P == $X){ 
			$Output[$CurrentAdd] = "stxd\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "stxd";
		}  
        break;
	case '4':
        $Output[$CurrentAdd] = "adc"; 
        break;
	case '5':
        $Output[$CurrentAdd] = "sdb"; 
        break;
	case '6':
        $Output[$CurrentAdd] = "shrc"; 
        break;
	case '7':
        $Output[$CurrentAdd] = "smb"; 
        break;
	case '8':
        if($P == $X){ 
			$Output[$CurrentAdd] = "sav\r\n\tdb {$v1}";
			return 1;
		}else{
			$Output[$CurrentAdd] = "sav";
		}   
        break;
	case '9':
		$Regs[2] --;
        $Output[$CurrentAdd] = "mark"; 
        break;
	case 'a':
        $Output[$CurrentAdd] = "req"; 
        break;
	case 'b':
        $Output[$CurrentAdd] = "seq"; 
        break;
	case 'c':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "adci {$v1}";
		return 1;
	case 'd':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "sdbi {$v1}";
		return 1;		
	case 'e':
        $Output[$CurrentAdd] = "shlc"; 
        break;
	case 'f':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "smbi {$v1}";
		return 1;
	}
	return 0;
  }
  
  function dsm1802_func_n8($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $D, $Regs;
	$D = ($Regs[hexdec($n1)] & 0Xff);
	$Output[$CurrentAdd] = "glo r{$n1}"; 
	return 0;
  }
  
  function dsm1802_func_n9($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $D, $Regs;
	$D = ($Regs[hexdec($n1)] & 0XFF00) >> 8;
	$Output[$CurrentAdd] = "ghi r{$n1}";
	return 0;
  }
  
  function dsm1802_func_na($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $D, $Regs;
	$Output[$CurrentAdd] = "plo r{$n1}";
	$Regs[hexdec($n1)] = ($Regs[hexdec($n1)] & 0Xff00) | $D;
	return 0;
  }
  
  function dsm1802_func_nb($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $D, $Regs;
	$Output[$CurrentAdd] = "phi r{$n1}";
	$Regs[hexdec($n1)] = ($Regs[hexdec($n1)] & 0Xff) | ($D << 8);
	return 0;
  }
  
  function dsm1802_func_nc($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
	
	switch ($n1) {
    case '0':
        $Output[$CurrentAdd] = "lbr {$v1}{$v2}"; 
        break;
    case '1':
        $Output[$CurrentAdd] = "lbq {$v1}{$v2}"; 
        break;
    case '2':
        $Output[$CurrentAdd] = "lbz {$v1}{$v2}"; 
        break;
	case '3':
        $Output[$CurrentAdd] = "lbdf {$v1}{$v2}"; 
        break;
	case '4':
        $Output[$CurrentAdd] = "nop";
		return 0;
	case '5':
        $Output[$CurrentAdd] = "lsnq";
		return 0;		
	case '6':
        $Output[$CurrentAdd] = "lsnz";
		return 0;
	case '7':
        $Output[$CurrentAdd] = "lsnf";
		return 0;
	case '8':
        $Output[$CurrentAdd] = "lskp";
		return 0;
	case '9':
        $Output[$CurrentAdd] = "lbnq {$v1}{$v2}"; 
        break;
	case 'a':
        $Output[$CurrentAdd] = "lbnz {$v1}{$v2}"; 
        break;
	case 'b':
        $Output[$CurrentAdd] = "lbnf {$v1}{$v2}"; 
        break;
	case 'c':
        $Output[$CurrentAdd] = "lsie"; 
		return 0;
	case 'd':
        $Output[$CurrentAdd] = "lsq";
		return 0;		
	case 'e':
        $Output[$CurrentAdd] = "lsz";
		return 0;
	case 'f':
        $Output[$CurrentAdd] = "lsdf";
		return 0;		
	}
	return 2;
  }
  
  function dsm1802_func_nd($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
	$Output[$CurrentAdd] = "sep r{$n1}";
	$P = hexdec($n1);
	dsm1802_CallReg("SETP{$n1}_".fix_zero(dechex($CurrentAdd + $Offset),4), $CurrentAdd);
	return 0;
  }
  
  function dsm1802_func_ne($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
	$Output[$CurrentAdd] = "sex r{$n1}";
	$X = hexdec($n1);
	return 0;
  }
  
  function dsm1802_func_nf($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X, $D;
	
	switch ($n1) {
    case '0':
        if($P == $X){ 
			$Output[$CurrentAdd] = "ldx\r\n\tdb {$v1}";
			$D = hexdec($v1);
			return 1;
		}else{
			dsm1802_LoadReg("ldx_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "ldx";
		} 
        break;
    case '1':
        if($P == $X){ 
			$Output[$CurrentAdd] = "or\r\n\tdb {$v1}";
			return 1;
		}else{
			dsm1802_LoadReg("or_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "or";
		}  
        break;
    case '2':
        if($P == $X){ 
			$Output[$CurrentAdd] = "and\r\n\tdb {$v1}";
			return 1;
		}else{
			dsm1802_LoadReg("and_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "and";
		}  
        break;
	case '3':
        if($P == $X){ 
			$Output[$CurrentAdd] = "xor\r\n\tdb {$v1}";
			return 1;
		}else{
			dsm1802_LoadReg("xor_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "xor";
		}  
        break;
	case '4':
        if($P == $X){ 
			$Output[$CurrentAdd] = "add\r\n\tdb {$v1}";
			return 1;
		}else{
			dsm1802_LoadReg("add_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "add";
		}  
        break;
	case '5':
        if($P == $X){ 
			$Output[$CurrentAdd] = "sd\r\n\tdb {$v1}";
			return 1;
		}else{
			dsm1802_LoadReg("sd_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "sd";
		}  
        break;
	case '6':
        $Output[$CurrentAdd] = "shr"; 
        break;
	case '7':
        if($P == $X){ 
			$Output[$CurrentAdd] = "sm\r\n\tdb {$v1}";
			return 1;
		}else{
			dsm1802_LoadReg("sm_".fix_zero(dechex($CurrentAdd + $Offset),4), $X, $CurrentAdd);
			$Output[$CurrentAdd] = "sm";
		}  
        break;
	case '8':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "ldi {$v1}";
		$D = hexdec($v1);
		return 1;		
	case '9':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "ori {$v1}";
		$D = hexdec($v1) | $D;
		return 1;
	case 'a':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "ani {$v1}";
		$D = hexdec($v1) & $D;
		return 1;
	case 'b':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "xri {$v1}"; 
		$D = hexdec($v1) ^ $D;
		return 1;
	case 'c':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "adi {$v1}";
		$D += hexdec($v1);
		return 1;
	case 'd':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "sdi {$v1}";
		$D = hexdec($v1) - $D;
		return 1;
	case 'e':
        $Output[$CurrentAdd] = "shl";
		$D  = $D >> 1;
        break;
	case 'f':
		$Regs[$P] ++;
        $Output[$CurrentAdd] = "smi {$v1}";
		$D -= hexdec($v1);
		return 1;
	}
	return 0;
  }
  
  function dsm1802_func_nx($n1,$v1,$v2,$CurrentAdd)
  {
    global $Output, $Contents, $P, $X;
		$Output[$CurrentAdd] = "0{$n1}";  
	return 0;
  }
  //----------------------------------------------------------------------------
  
  function dsm1802_CleanUp($str)
  {
    $str = str_replace(array("\r\n\r\n","\r\n"), array(""," "), $str); 
	$str = str_replace(array("    ","   ","  "), array(" ", " ", " "), $str);
	
    $Split1 = explode(" ", $str);

    $str = array();

    foreach($Split1 as $val)
    {
        $val = strtolower($val);
        
        if($val!="") $str[] = $val;
    }
    return $str;
  }
  
?>