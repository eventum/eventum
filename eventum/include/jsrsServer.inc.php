<?php

//
// jsrsServer.php - javascript remote scripting server include
//
// Orginal Author:  Brent Ashley [jsrs@megahuge.com]
// PHP version   :  Sébastien Cramatte [sebastien@webeclaireur.com] 
//		    Pierre Cailleux [cailleux@noos.fr] 
// Date		 :  May 2001 
// 
// see jsrsClient.js for version info
//
//  see license.txt for copyright and license info

function jsrsDispatch($validFuncs ){
  $func = jsrsBuildFunc($validFuncs);
  
  if ($func != ""){
    @$retval;
    
    eval("\$retval =  " . $func . ";");
    
    if (strlen($retval)>0){
      jsrsReturn($retval."");
    } else {
      jsrsReturn("");
    } 
  } else {
    jsrsReturnError("function builds as empty string");
  }
}

function jsrsReturn($payload) {
  global $C;
  if(!isset($C)) $C = (isset($_REQUEST['C']) ? $_REQUEST['C'] : "");

  Print (
      "<html><head></head><body onload=\"p=document.layers?parentLayer:window.parent;p.jsrsLoaded('" 
    . $C . "');\">jsrsPayload:<br>" 
    . "<form name=\"jsrs_Form\"><textarea name=\"jsrs_Payload\" id=\"jsrs_Payload\">"
    . jsrsEscape($payload) . "</textarea></form></body></html>");
    exit();
}

function jsrsEscape($str){
  // escape ampersands so special chars aren't interpreted
  $tmp = ereg_replace( "&", "&amp;", $str );
  // escape slashes  with whacks so end tags don't interfere with return html
  return ereg_replace( "\/" , "\\/",$tmp); 
}

/////////////////////////////
//
// user functions


function jsrsReturnError($str){
  global $C;
  if(!isset($C)) $C = (isset($_REQUEST['C']) ? $_REQUEST['C'] : "");
  
  // escape quotes
  $cleanStr = ereg_replace("\'","\\'",$str);
  
  // !!!! --- Warning -- !!!
  $cleanStr = "jsrsError: " . ereg_replace("\"", "\\\"", $cleanStr); 
  print ("<html><head></head><body " 
         . "onload=\"p=document.layers?parentLayer:window.parent;p.jsrsError('" . $C . "','" . urlencode($str) . "');\">"
         . $cleanStr . "</body></html>" );
  exit();
}

function jsrsArrayToString( $a, $delim ){
  // user function to flatten 1-dim array to string for return to client
  $d = "~";
  if (!isset($delim)) $d = $delim;
  return implode($a,$d); 
}


function jsrsBuildFunc($validFuncs) {
 global $F;
 if(!isset($F)) $F = (isset($_REQUEST['F']) ? $_REQUEST['F'] : "");
 
 $func = ""; 
 
 if ($F != "") {
  $func = $F;
  
      
  // make sure it's in the dispatch list
  if (strpos(strtoupper($validFuncs),strtoupper($func))===false)
     jsrsReturnError($func . " is not a valid function" );
   
   $func .= "(";
   $i = 0;
    
   //--- To optimize ! --- 
   eval("global \$P$i;");
   eval("if(!isset(\$P$i)) \$P$i = (isset(\$_REQUEST['P$i']) ? \$_REQUEST['P$i']:'');");
   $Ptmp = "P". $i;
     
   while ($$Ptmp!="") {
    $parm = $$Ptmp;
    $parm = substr($parm,1,strlen($parm)-2);
    $func .= "\"" . $parm . "\",";
    $i++;
    eval("global \$P$i;");
    eval("if(!isset(\$P$i)) \$P$i = (isset(\$_REQUEST['P$i']) ? \$_REQUEST['P$i']:'');");
    $Ptmp = "P". $i;
   }
   
   if (substr($func,strlen($func)-1,1)==",")  
    $func = substr($func,0,strlen($func)-1);

    $func .= ")";
  } 
 
 return $func;
}

function jsrsEvalEscape($thing) {
 $tmp = ereg_replace($thing,"\r\n","\n");
 return $tmp;
}

function jsrsVBArrayToString($a,$delim) {
 // --- not use in PHP see jsrsArrayToString method
 return jsrsArrayToString($a,$delim);
}


?>



