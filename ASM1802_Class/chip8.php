<?php
    echoLog ("\r\n\r\n-=== Loaded Chip8 Definition ===-\r\n\r\n");
    echoLog (<<< test
        00E0 - CLS
        00EE - RET
        0nnn - SYS addr
        1nnn - JP addr
        2nnn - CALL addr
        3xkk - SE Vx, byte
        4xkk - SNE Vx, byte
        5xy0 - SER Vx, Vy
        6xkk - LD Vx, byte
        7xkk - ADDN Vx, byte
        8xy0 - LDR Vx, Vy
        8xy1 - OR Vx, Vy
        8xy2 - AND Vx, Vy
        8xy3 - XOR Vx, Vy
        8xy4 - ADD Vx, Vy
        8xy5 - SUB Vx, Vy
        8xy6 - SHR Vx {, Vy}
        8xy7 - SUBN Vx, Vy
        8xyE - SHL Vx {, Vy}
        9xy0 - SNER Vx, Vy
        Annn - LDI addr
        Bnnn - JP0 addr
        Cxkk - RND Vx, NN
        Dxyn - DRAW Vx, Vy, n
        Ex9E - SKP Vx
        ExA1 - SKNP Vx
        Fx07 - GETD Vx
        Fx0A - GETK Vx
        Fx15 - LDDT  Vx
        Fx18 - LDST Vx
        Fx1E - ADDI Vx
        Fx29 - FONT Vx
        Fx33 - BCD Vx
        Fx55 - STORE Vx
        Fx65 - LOAD Vx
test
);

    function Chip8_load()
    {
        global $Contents, $Output ;

        $Contents = Chip8_CleanUp($Contents);
        Chip8_GetCalls();
        $Output=array();
        Chip8_run();
    }

    function Chip8_GetCalls()
    {
        global $Contents, $Calls, $PC;

        $PC=0; $str = array();
        foreach($Contents as $key=>$val)
        {
            if(strpos($val, ":")>-1){
                $Calls[str_chop($val,"",":")] = $PC;
                $val = "";
            } elseif (strpos($val, "#define")>-1) {
                $Split1 = explode(" ", $val);
                $vals = array_slice($Split1, 1);
                $vals = Chip8_ValConverter($vals, $Split1[0]);
                $vals1 = array_slice($Split1, 1);
                $Calls[$vals1[0]] = (hexdec($vals[1]));
                $val = "";
            }else{
                $Split1 = explode(" ", $val);
                $vals = array_slice($Split1, 1);
                $vals = Chip8_ValConverter($vals, $Split1[0]);
                //echoLog ("Chip8_func_". strtolower($Split1[0])."\r\n");
                $PC += call_user_func("Chip8_func_". strtolower($Split1[0]), $vals, $key+1, $val);
            }
            if($val!="") $str[] = $val;
        }

        $Contents = $str;
        if(count($Calls) > 0){
            echoLog ("List of all call address's\r\n");
            foreach($Calls as $key=>$val) echoLog ("$key	-	" . fix_zero(dechex($val),4) . "\r\n");
            echoLog ("------------------------------\r\n\r\n");
        }
    }

    function Chip8_run()
    {
        global $Contents, $PC, $Output;
        $PC=0;
        $no=0;
        foreach($Contents as $key=>$val)
        {
            $Split1 = explode(" ", $val);

            $vals = array_slice($Split1, 1);
            $vals = Chip8_ValConverter($vals, $Split1[0]); ;
            $temp = fix_zero(dechex($PC),4);
            //echoLog ("Chip8_func_". strtolower($Split1[0])."\r\n");
            $tpc = call_user_func("Chip8_func_". strtolower($Split1[0]), $vals, $key+1, $val);
            $PC += $tpc;
            //if(strlen($Output[count($Output)-1])<32){
            echoLog ("$temp:\t" . $Output[count($Output)-1] . "\t\t\t$val\t$tpc\r\n");
            //}
        }
    }
    function Chip8_done()
    {
        global $Contents, $Output, $name;

        $out="";

        foreach($Output as $val) $out .= $val;
        var_dump($out);
        Chip8_save_as_bin($out);
    }
    function Chip8_save_as_bin($out){
        global $name;
        for($i=0;$i<strlen($out)/2;$i++) $fout .= chr(hexdec(substr($out,$i*2,2)));
        write_file($fout,"$name.bin");
    }
    //===============================================================================

    function Chip8_func_cls($vals, $line, $val)
    {
        global $Output;
        $Output[] = "00E0";
        return 2;
    }

    function Chip8_func_ret($vals, $line, $val)
    {
        global $Output;
        $Output[] = "00EE";
        return 2;
    }

    function Chip8_func_sys($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) > 3) echoLog ("SYS Takes Three Nibbles On Line: $line\r\n");

        $Output[] = strtoupper("0" . $vals[0]);
        return 2;
    }

    function Chip8_func_jp($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) > 3) echoLog ("JP Takes Three Nibbles On Line: $line\r\n");

        $Output[] = strtoupper("1" . $vals[0]);
        return 2;
    }

    function Chip8_func_call($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) > 3) echoLog ("CALL Takes Three Nibbles On Line: $line\r\n");

        $Output[] = strtoupper("2" . $vals[0]);
        return 2;
    }

    function Chip8_func_se($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 2) echoLog ("SE Takes X and A Byte On Line: $line\r\n");

        $Output[] = strtoupper("3" . $vals[0] . $vals[1]);
        return 2;
    }

    function Chip8_func_sne($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 2) echoLog ("SNE Takes X and A Byte On Line: $line\r\n");

        $Output[] = strtoupper("4" . $vals[0] . $vals[1]);
        return 2;
    }

    function Chip8_func_ser($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("SER Takes X and A Byte On Line: $line\r\n");

        $Output[] = strtoupper("5" . $vals[0] . $vals[1] . "0");
        return 2;
    }

    function Chip8_func_ld($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 2) echoLog ("LD Takes X and A Byte On Line: $line\r\n");

        $Output[] = strtoupper("6" . $vals[0] . $vals[1]);
        return 2;
    }

    function Chip8_func_addn($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 2) echoLog ("Addn Takes X and A Byte On Line: $line\r\n");

        $Output[] = strtoupper("7" . $vals[0] . $vals[1]);
        return 2;
    }

    function Chip8_func_ldr($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("LDR Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "0");
        return 2;
    }

    function Chip8_func_or($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("or Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "1");
        return 2;
    }

    function Chip8_func_and($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("AND Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "2");
        return 2;
    }

    function Chip8_func_xor($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("XOR Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "3");
        return 2;
    }

    function Chip8_func_add($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("ADD Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "4");
        return 2;
    }

    function Chip8_func_sub($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("SUB Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "5");
        return 2;
    }

    function Chip8_func_shr($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1|| strlen($vals[1]) != 1) echoLog ("SHR Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "6");
        return 2;
    }

    function Chip8_func_subn($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("SUBN Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "7");
        return 2;
    }

    function Chip8_func_shl($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1|| strlen($vals[1]) != 1) echoLog ("SHL Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("8" . $vals[0] . $vals[1] . "E");
        return 2;
    }

    function Chip8_func_sner($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1) echoLog ("SNER Takes X and Y On Line: $line\r\n");

        $Output[] = strtoupper("9" . $vals[0] . $vals[1] . "0");
        return 2;
    }

    function Chip8_func_ldi($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 3) echoLog ("ldi Takes Three Nibbles On Line: $line\r\n");

        $Output[] = strtoupper("A" . $vals[0]);
        return 2;
    }

    function Chip8_func_jpo($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 3) echoLog ("JPO Takes Three Nibbles On Line: $line\r\n");

        $Output[] = strtoupper("B" . $vals[0]);
        return 2;
    }

    function Chip8_func_rnd($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 2) echoLog ("RND Takes X and A Byte On Line: $line\r\n");

        $Output[] = strtoupper("C" . $vals[0] . $vals[1]);
        return 2;
    }

    function Chip8_func_draw($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1 || strlen($vals[1]) != 1|| strlen($vals[2]) != 1) echoLog ("DRAW Takes X, Y, and One Nibble On Line: $line\r\n");

        $Output[] = strtoupper("D" . $vals[0] . $vals[1] . $vals[2]);
        return 2;
    }

    function Chip8_func_skp($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("SKP Takes X On Line: $line\r\n");

        $Output[] = strtoupper("E" . $vals[0] . "9E");
        return 2;
    }

    function Chip8_func_sknp($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("SKNP Takes X On Line: $line\r\n");

        $Output[] = strtoupper("E" . $vals[0] . "A1");
        return 2;
    }

    function Chip8_func_getd($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("GETD Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "07");
        return 2;
    }

    function Chip8_func_getk($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("GETK Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "0A");
        return 2;
    }

    function Chip8_func_lddt($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("LDDT Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "15");
        return 2;
    }

    function Chip8_func_ldst($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("LDST Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "18");
        return 2;
    }

    function Chip8_func_addi($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("ADDI Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "1E");
        return 2;
    }

    function Chip8_func_font($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("FONT Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "29");
        return 2;
    }

    function Chip8_func_bcd($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("BCD Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "33");
        return 2;
    }

    function Chip8_func_store($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("STORE Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "55");
        return 2;
    }

    function Chip8_func_load($vals, $line, $val)
    {
        global $Output;
        if(strlen($vals[0]) != 1) echoLog ("LOAD Takes X On Line: $line\r\n");

        $Output[] = strtoupper("F" . $vals[0] . "65");
        return 2;
    }
    //===============================================================================

    function Chip8_func_db($vals, $line, $val)
    {
        global $Output;

        $out="";

        foreach($vals as $val)
            $out .= $val;

        $Output[] = strtoupper($out);
        return (strlen($out)/2);
    }

    function Chip8_func_dbstr($vals, $line, $val)
    {
        global $Output;

        $t = str_chop($val,"dbstr \"","\"");
        $out="";

        for($i=0;$i<strlen($t);$i++)
            $out .= fix_zero(dechex(ord(substr($t, $i, 1))));

        $Output[] = strtoupper($out);
        return (strlen($out)/2);
    }

    function Chip8_func_dbrevstr($vals, $line, $val)
    {
        global $Output;

        $t = str_chop($val,"dbrevstr \"","\"");
        $t = strrev($t);
        $out="";

        for($i=0;$i<strlen($t);$i++)
            $out .= fix_zero(dechex(ord(substr($t, $i, 1))));

        $Output[] = strtoupper($out);
        return (strlen($out)/2);
    }

    function Chip8_func_alloc($vals, $line, $val)
    {
        global $Output;

        $out="";

        for($i=0;$i<hexdec($vals[0]);$i++)
            $out = $out . "00";

        $Output[] = strtoupper($out);
        return (strlen($out)/2);
    }

    function Chip8_func_loadfile($vals, $line, $val)
    {
        global $Output;

        $out = bin2hex(read_file($vals[0]));
        $Output[] = strtoupper($out);
        return (strlen($out)/2);
    }

    function Chip8_func_setpc($vals, $line, $val)
    {
        global $PC;
        $PC = hexdec($vals[0]);
        return 0;
    }

    function Chip8_func_addpc($vals, $line, $val)
    {
        global $PC;
        $PC = $PC + hexdec($vals[0]);
        return 0;
    }

    function Chip8_func_space($vals, $line, $val)
    {
        global $Output, $PC;

        $out = "";

        for($i=0;$i <= hexdec($vals[0]) - ($PC+1); $i++)
            $out .= "00";


        $Output[] = strtoupper($out);

        return (strlen($out)/2);
    }

    function Chip8_func_addspace($vals, $line, $val)
    {
        global $Output, $PC;

        $out = "";

        for($i=0;$i <= hexdec($vals[0]); $i++)
            $out .= "00";


        $Output[] = strtoupper($out);

        return (strlen($out)/2);
    }
    //===============================================================================

    function Chip8_ValConverter($vals, $func)
    {
        global $Calls, $PC;
        $out = array();
        $last = "";
        $valO = "";
        foreach($vals as $key=>$val)
        {
            if($last == "low" && count($Calls)>0 && array_key_exists($val,  $Calls))
            {
                foreach($Calls as $key1=>$val1)
                {
                    if($key1 == $val)
                    {
                        if(substr($func, 0, 1) == "b" && fix_zero(dechex(($val1 & 0xFF00) >> 8)) !==  fix_zero(dechex(($PC & 0xFF00) >> 8))) echoLog ("Branch Error - " . fix_zero(dechex($PC),4) . ":	$func");

                        $val = "#".($val1 & 0xFF);
                        break;
                    }
                }
            }else if($last == "low") $val = "00";

            if($last == "high" && count($Calls)>0 && array_key_exists($val,  $Calls))
            {
                foreach($Calls as $key1=>$val1)
                {
                    if($key1 == $val)
                    {
                        $val = "#".(($val1 & 0xFF00) >> 8);
                        break;
                    }
                }
            }else if($last == "high") $val = "00";

            if(count($Calls) > 0 && array_key_exists($val,  $Calls))
            {
                foreach($Calls as $key1=>$val1)
                {
                    if($key1 == $val)
                    {
                        $val = "$".($val1);
                        break;
                    }
                }
            }else if($func == "load" && $key==0) $valO = $val;

            if($valO=="")
            {
                if($val!=="high" && $val!=="low") $valO = $val;
                if(strpos($val, "?")>-1) $valO = fix_zero(dechex(intval(str_chop($val,"?",""))));
                if(strpos($val, "$")>-1) $valO = fix_zero(dechex(intval(str_chop(strtolower($val),"$",""))),3);
                if(strpos($val, "@")>-1) $valO = bin2hex(str_chop($val,"@",""));
                if(strpos($val, "%")>-1) $valO = fix_zero(dechex(bindec(str_chop($val,"%",""))));
                if(strpos($val, "r")>-1) $valO = substr(str_chop($val,"r",""),0,1);
                if(strpos($val, "n")>-1) $valO = substr(str_chop($val,"n",""),0,1);
                if(strpos($val, "chr")>-1) $valO = fix_zero(dechex(ord(str_chop($val,"chr",""))));
            }

            $last = $val;
            if($valO!="") $out[] = $valO;
            $valO = "";
        }
        return $out;
    }

    function Chip8_CleanUp($str)
    {
        global $Modules;

        $strSplit1 = explode("\r\n", $str);
        $str = array();
        foreach($strSplit1 as $val)
        {
            $lower = strtolower($val);

            while(intval(substr_count($val, '  '))>0) $val = str_replace(array("  "), array(" "), $val);

            $val = preg_replace(array("(, )", "(,)", "/^(\s)*/", "/--.*$/", "/;.*$/"), array(" ", " ", "", "", ""), $val);

            if($val!="") $str[] = $val;
        }
        return $str;
    }
?>
