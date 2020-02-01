<?php
  $Modules = array();

  function praser_LoadDef()
  {
    global $Contents, $Modules, $Offset;
    
    $strSplit1 = explode("\r\n", $Contents);

    foreach($strSplit1 as $val)
    {
        preg_match_all("/[\?|\&]([a-zA-Z]*)=([a-zA-Z0-9]*)/", $val, $matches, PREG_PATTERN_ORDER);

        foreach($matches[0] as $k => $v){
            $Contents = str_replace(array("{$v}"), array(""), $Contents);

            if(strtolower($matches[1][$k]) == "def"){
                include(strtolower($matches[2][$k]) . ".php");
                $Modules[] = strtolower($matches[2][$k]);
			}

            if(strtolower($matches[1][$k]) == "offset"){
                $Offset = hexdec($matches[2][$k]);
            }

		}
    }

  }

  function praser_ModuleStart()
  {
    global $Modules;
    foreach($Modules as $val)
    {
      call_user_func($val."_load");
	  call_user_func($val."_done");
    }  
  } 

?>