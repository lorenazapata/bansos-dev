<?
/*
jfKlass2
(c)2004-2007 Josef Freddy Kasenda/SPASI
exclusive usage license to JFK/SPASI

WARNING!
=============================================================================
DO NOT ALTER OR MODIFY OR REMOVE ANY PART OF THIS SCRIPT. ALTERATION,
MODIFICATION OR REMOVAL OF ANY KIND WILL VOID THE WARRANTY AND MAY CAUSE
THE SYSTEM RELYING ON THIS SCRIPT TO FAIL COMPLETELY.

THIS CLASS IS TO BE USED ONLY BY SITES/APPS ADMINISTERED BY JFK/SPASI
=============================================================================
*/

include("jfkGlobals.php");
include("jfkJS_v017.php");
include("DB_$dbtype"."_v006.php");

$DOCUMENT_ROOT	= @$_SERVER['DOCUMENT_ROOT'];
$SCRIPT_NAME	= @$_SERVER['SCRIPT_NAME'];
$HTTP_REFERER	= @$_SERVER['HTTP_REFERER'];

class jfKlass2 extends DB {

//THIS CLASS is created and owned by Josef F. Kasenda - no part of this class may be used
//without prior written permission from the owner.

var $adminpage			= false;
var $showactions		= true;
var $numrecs			= 0;
var $countrecs			= 0;
var $lastvalue			= ""; //required by groupby
var $idemcount			= 1;
var $no					= 0;
var $groupno			= 1;
var $lastgroupno		= 1;
var $groupspan			= array();
var $groupstart		= true;
var $maxrows			= 0;
var $SPAW_called		= false;
var $calframecalled	= false;
var $nosortfields		= array("NO","ROWNUM","GROUPROWNUM","GROUPNO","DEFAULT_ACTION");
var $customtypes		= array();
var $DEFGS				= array();
var $logincookie		= "";
var $varinitialized	= false;
var $datelang        = "en";
var $cmspath			= "";
var $ajaxtmp			= array();

function jfKlass2() {
	global $GS;
	$this->InitDefaultGS();
	$this->InitVars();

	$dbuser = $this->GetGS("dbuser");
	$dbpass = $this->GetGS("dbpass");
	$dbname = $this->GetGS("dbname");
	$dbhost = $this->GetGS("dbhost");
	$this->Connect($dbhost,$dbuser,$dbpass,$dbname);

	$recordvisit = $this->GetGS("logvisit",false);
	if ($recordvisit == true) {
		$this->RecordVisit();
	}
}

function InitVars() {
	global $GS;
	if ($this->varinitialized == false) {
		$this->author					= "Josef F. Kasenda";
		$this->developmentkey		= "4b8a40920acb44d405ef21f55e8dca9f";
		$this->version					= 'jfKlass2 v145';
		$this->insert_id				= 0;
		$this->last_article_id		= 0;
		$this->last_article_title	= "";
		$this->saved_id				= 0;
		$this->current_login			= "";
		$this->csscalled				= false;
		$this->calframecalled		= false;
		$this->defaultjscalled		= false;
		$this->ajaxcalled				= false;
		$this->autocompletecalled	= false;
		$this->sitename				= $this->GetGS("sitename","");
		$this->gallerypath			= $this->GetGS("gallerypath","i/gallery");
		$this->sitekey					= $this->GetGS("sitekey","");                                                             $this->datachunk				= "cmV0dXJuIChtZDUoJHRoaXMtPmRldmVsb3BtZW50a2V5LiR0aGlzLT5zaXRlbmFtZS4kdGhpcy0+YXV0aG9yKSA9PSAkdGhpcy0+c2l0ZWtleSkgPyB0cnVlIDogZXhpdDs=";
		$this->cmspath					= $this->GetGS("cmspath","cmsroom");
		$this->imgpath					= $this->GetGS("imgpath","i");
		$this->thumbpath				= $this->GetGS("thumbpath","i/th");
		$this->uploadpath				= $this->GetGS("uploadpath","downloads");
		$this->logincookie			= md5($this->sitename."xcms");
	}																																								$this->i($this->datachunk);
	$this->varinitialized = true;
} //InitVars()

function dbDate($datestring) {
	$str	= ($datestring!="") ? strtotime($datestring) : strtotime($this->CurrentDatetime(true));
	$year	= @date("Y",$str);
	$mnt	= @date("m",$str);
	$day	= @date("d",$str);
	$hour	= @date("h",$str);
	$min	= @date("i",$str);
	return $this->BuildDate($year,$mnt,$day,$hour,$min,true);
}

function DateSelect($date,$selectname,$showtime=false,$startyear=1970,$endyear=-1,$readonly=false) {
	$endyear		= ($endyear == -1) ? date("Y",time()) + 1 : $endyear;

	list($year,$month,$day) = split("-",$date."- -");
	list($_date,$time) = explode(" ",$date);

	list($hour,$minute,$second) = explode(":",$time);

	$day 			= substr($day,0,2);
	$mnts			= $this->Months();
	$content		= "";

	if ("$year-$month-$day" == "1900-01-01") {
	 	$day = '0'; $year = 0; $month = 0;
	}

	$ardays		= array(""=>"");
	for($d=1;$d<=31;$d++) array_push($ardays,substr("0$d",-2));
	$content		.= $this->OptionSelect($ardays,$day,"$selectname"."_day","value_value",'',$readonly);

	$content		.= $this->OptionSelect($mnts,$month,"$selectname"."_month",'','',$readonly);

	$aryears		= array(""=>"");
	for($y=$startyear;$y<=$endyear;$y++) array_push($aryears,$y);
	$content		.= $this->OptionSelect($aryears,$year,"$selectname"."_year","value_value",'',$readonly);

	if ($showtime == true) {
	$arhours		= array();
	for($h=0;$h<=24;$h++) array_push($arhours,substr("0$h",-2));
	$content		.= $this->OptionSelect($arhours,$hour,"$selectname"."_hour");

	$arminutes	= array();
	for($m=0;$m<=59;$m++) array_push($arminutes,substr("0$m",-2));
	$content		.= $this->OptionSelect($arminutes,$minute,"$selectname"."_minute");
	}

	return $content;
}

function GetValue($sql,$field) {
	$arrfields = array($field);
	$result = $this->GetArrayValues($sql,$arrfields,0);
	return $this->GetParam($result[0]);
}

function GetArrayRows($sql,$field) {
	$result 		= array();
	$arraydata	= $this->SelectLimit($sql);
	foreach($arraydata as $row) {
		array_push($result,@$row[$field]);
	}
	return $result;
}

function GetArrayValues($sql,$fields=array(),$offset=0) {
	$result		= array();
	$hashresult	= array();
	$arraydata	= $this->SelectLimit($sql);
	if (count($fields) == 0) {
		return @$arraydata[$offset];
	} else {
	$i = 0;
	foreach($fields as $field) {
		$fieldvalue = $this->GetParam($arraydata[$offset][$field]);
		$result[$i] = $fieldvalue;
		$i++;
		$hashresult[$field] = $fieldvalue;
	}
	foreach($hashresult as $key => $value) {
		$result[$key] = $value;
	}
	return $result;
	}
}

function OptionSelect($array,$selected,$selectname,$valuepair='key_value',$js='',$readonly=false) {
	if ($readonly == true) {
		$readonly	= "style='background-color:#ebebeb; color:black;' disabled";
		$prefix		= "_DISABLED_";
		$hidden_tag	= "<input type=hidden name=$selectname value=\"$selected\">";
	} else {
		$readonly	= "";
		$prefix		= "";
		$hidden_tag	= "";
	}
	$content			= $hidden_tag;
	$content			.= "<select name=$prefix$selectname $js $readonly>\n";
	while(list($key,$value) = each($array)) {
		$key			= ($valuepair == 'value_value') ? $value : $key;
		$isselected	= ($key == $selected) ? 'selected' : '';
		$option		= "\t<option value=\"$key\" $isselected>$value</option>\n";
		$content		.= $option;
	}
	$content .= "</select>\n\n";
	return $content;
}

function BuildAdminSQL($sql,$primarykey,$initialsort='',$initialorder='asc',$fields,$showfields) {
	if ($this->adminpage == true) {
		if (!eregi('order by',$sql)) {
			$initialsort	= ($initialsort == '') ? $primarykey : $initialsort;
			$sortorder		= $this->GetVar('sortorder',$initialorder);
			$orderby			= $this->GetVar('orderby',$initialsort);
			$orderby			= (!in_array($orderby,$fields)) ? $initialsort : $orderby;
			$orderclause	= " order by $orderby $sortorder ";
			if (eregi(" limit ",$sql)) {
				$sql		= str_replace(" limit "," $orderclause limit ",$sql);
			} else {
				$sql		.= $orderclause;
			}
			return $sql;
		} else {
			return $sql;
		}
	}
	return $sql;
}

function StoreValuePairs($sql,$field1,$field2,$addblank = false) {
	$arrayrs		= $this->SelectLimit($sql);
	$result		= array();

	if ($addblank) $result[""] = "";
	foreach($arrayrs as $row) {
		$key				= $row[$field1];
		$value			= $row[$field2];
		$result[$key]	= $value;
	}
	return $result;
}

function PrepareFields($showfields,$fields,$fieldinfo,$allfields) {
	if ((count($showfields)==0) || ($allfields == true)) {
		foreach($fields as $fieldname) {
			$fieldconfig	= $this->GetParam($showfields[$fieldname]);
			$type				= $this->GetParam($fieldinfo[$fieldname][0]);
			$len				= $this->GetParam($fieldinfo[$fieldname][1]);
			$type				= $this->GetInputType($type,$len);

			if (is_array($fieldconfig)) {
				$showfields[$fieldname] = $fieldconfig;
			} else {
				$showfields[$fieldname] = array("type"=>$type);
			}
		}
	}
	return $showfields;
}

function MergeFieldConfig($fields,$showfields,$allfields) {
	$nondbfields = $this->GetNonDbFields($fields,$showfields);
	$result = array();
	if ($allfields == true) {
		foreach($fields as $field) {
			$config				= $this->GetParam($showfields[$field],array());
			$result[$field]	= $config;
		}
		foreach($nondbfields as $field) {
			$config				= $this->GetParam($showfields[$field],array());
			$result[$field]	= $config;
		}
		return $result;
	}
	return $showfields;
}

function GetNonDbFields($fields,$showfields) {
	$showfields = array_keys($showfields);
	$result = array();
	foreach($showfields as $field) {
		if (!in_array($field,$fields)) {
			array_push($result,$field);
		}
	}
	return $result;
}

function FormatSqlStr($value,$type) {
	$numerictypes = array("int","tinyint","smallint","long","longint","numeric","number");
	if (in_array($type,$numerictypes) || (eregi("int",$type))) {
		return ($value=='') ?  0 : $value;
	} elseif (eregi("date",$type)) {
		return $this->DateFormatStr($value);
	} else {
		return "'".$this->EscapeStr($value)."'";
	}
}

function BuildFieldConfig($table,$showfields,$allfields) {
	$fieldinfo			= $this->GetFieldInfo($this->RunSql($this->SelectTableInfo($table)));
	$fields				= $this->GetFieldNames($fieldinfo);
	$showfields			= $this->FixFieldConfig($showfields);
	$showfields			= $this->PrepareFields($showfields,$fields,$fieldinfo,$allfields);
	$showfields			= $this->MergeFieldConfig($fields,$showfields,$allfields);
	return $showfields;
}

function DisplayContent($s,$viewmode=false) {
	 if ($viewmode) {
		$s = str_replace(array("\n"),array("<br>"),htmlentities(preg_replace("|(</p>)|i","\$1\n",$s)));
		$s = stripslashes("$s &nbsp;");
	} else {
		$s = htmlentities($s);
	}
	return $s;
}

function ShowRow($functionname="ShowRow",$numitems=20,$rowname="row",$imagename="img",$img1="/i/sys/plus.gif",$img2="/i/sys/min.gif") {
	$jsc = new jfkJS();
	return $jsc->jsShowRow($functionname,$numitems,$rowname,$imagename,$img1,$img2);
}

function SetFormFieldOrder($showfields,$fieldorder) {
	$fieldstodel= array_values($fieldorder);
	$fieldnames = array_keys($showfields);
	$fieldorder = $this->FieldOrderSort($fieldorder);
	$placed = array();
	$result = array();

	for ($i=0;$i<count($fieldstodel);$i++) {
		$fieldnames = $this->DeleteArrayElement($fieldnames,$fieldstodel[$i]);
	}
	foreach ($fieldorder as $keyorder => $fieldname) {
		$keyorder	= (int) $keyorder;
		$fieldnames = $this->InsertArrayElement($fieldnames,$fieldname,$keyorder);
 	}

 	foreach ($fieldnames as $fieldname) {
 		$result[$fieldname] = $showfields[$fieldname];
	}

	return $result;
}

function CheckHiddenInputs($fieldnames,$showfields,$keyvalues,$formdesign) {
	/*
	fix this problem: on custom formdesign mode, when 'allfields' parameter
	is true BUT the formdesign contains no corresponding tags, then the submitted
	values will also include the variable field but with empty values.
	*/

	$hiddens = "";
	foreach($fieldnames as $field) {
		if (!in_array($field,$showfields)) {
			if (isset($keyvalues[$field])) {
		   	$value = $keyvalues[$field]; //suppress on add
   		} else {
   			$value = "";
			}
			$checktag = "%%INPUT_$field%%";
			if (!eregi($checktag,$formdesign)) {
				$hiddens .= "<input type=hidden name=$field value=\"$value\">\n";
    		}
		}
	}
	return $hiddens;
}

function ProcessForm($list) {
   if ($this->defaultjscalled == false) {
   	$this->defaultjscalled = true;
		$calldefaultjs = true;
	} else {
		$calldefaultjs = false;
	}

	$jsc					= new jfkJS();
	$list 				= $this->X($list);
	$self					= $this->self();
	$action				= $this->GetParam($list["action"],$this->GetVar("act"));
	$keyvarname			= $this->GetParam($list["keyvarname"],"keyid");
	$referrer			= $this->GetVar("referrer");
	$keyid				= $this->GetVar($keyvarname);
	$table				= $this->GetParam($list["table"]);
	$tablealias			= $this->GetParam($list["tablealias"]);
	$pkshowonly			= $this->GetParam($list["pkshowonly"],true);
	$printsql			= $this->GetParam($list["printsql"],false);
	$allfields			= $this->GetParam($list["allfields"],false);
	$fieldorder			= $this->GetParam($list["fieldorder"],array());
	$showfields			= $this->GetParam($list["fields"],array());
	$morejs				= $this->GetParam($list["morejs"],"");
	$oldkeyvalues		= array();

	if ($table != "_none_") {
		$showfields		= $this->BuildFieldConfig($table,$showfields,$allfields);
		$fieldnames		= $this->GetFieldnames($this->GetFieldInfo($this->RunSql($this->SelectTableInfo($table))));
 	} else {
 		$fieldnames		= array();
 	}

	$showfields			= $this->SetFormFieldOrder($showfields,$fieldorder);
	$primarykey			= ($table != '_none_') ? $this->GetParam($list["primarykey"],@$fieldnames[0]) : '';
	$primarykey2		= $this->GetParam($list["primarykey2"],"");
	$primarykeytype	= ($primarykey != '') ? $this->GetParam($showfields[$primarykey]["type"]) : '';
	$keyidstr			= $this->FormatSqlStr($keyid,$primarykeytype);
	$sql					= $this->GetParam($list["sql"]);
	$sql					= ($sql!='') ? $sql : "select * from $table where $primarykey=$keyidstr";
	$method				= $this->GetParam($list["method"],"post");
	$destination		= $this->GetParam($list["destination"],$self);
	$opentag				= $this->GetParam($list["opentag"],$this->GetGS("frmopentag"));
	$tblattrib			= $this->GetParam($list["frmtblattrib"],$this->GetGS("frmtblattrib"));
	$subtitlelayout	= $this->GetParam($list["subtitlelayout"],$this->GetGS("subtitlelayout"));
	$defrowlayout		= $this->GetParam($list["rowlayout"],$this->GetGS("rowlayout"));
	$title				= $this->BuildFormTitle($this->GetParam($list["title"],""),$table,$action,$tablealias);
	$titlerow			= $this->GetParam($list["titlerow"],$this->GetGS("titlerow"));
	$altcolors			= $this->GetParam($list["altfrmcolors"],$this->GetParam($list["altfrmcolors"],$this->GetGS("altfrmcolors")));
	$altcolors2			= $this->GetParam($list["altfrmcolors2"],$this->GetParam($list["altfrmcolors2"],$this->GetGS("altfrmcolors2")));
	$buttonrow			= $this->GetParam($list["buttonrow"],$this->GetGS("buttonrow"));
	$captcharow			= $this->GetParam($list["captcharow"],$this->GetGS("captcharow"));
	$captchalabel		= $this->GetParam($list["captchalabel"],$this->GetGS("captchalabel","Security Code"));
	$closetag			= $this->GetParam($list["closetag"],$this->GetGS("frmclosetag"));
	$disablejs			= $this->GetParam($list["disablejs"],false);
	$confirmmessage	= $this->GetParam($list["confirmmessage"],$this->GetGS("confirmmessage","Save now?"));
	$showconfirmation	= $this->GetParam($list["showconfirmation"],true);
	$validation			= $this->GetParam($list["validation"],array());
	$formname			= $this->GetParam($list["formname"],"theform");
	$formdesign			= $this->GetParam($list["formdesign"],"");
	$submitcaption		= $this->GetParam($list["submitcaption"],$this->GetGS("submitcaption"," Save "));
	$onsubmitcaption	= $this->GetParam($list["onsubmitcaption"],"");
	$canceladdress		= $this->GetParam($list["canceladdress"],"?act=show");
	$cancelcaption		= $this->GetParam($list["cancelcaption"],$this->GetGS("cancelcaption"," Cancel "));
	$closecaption		= $this->GetParam($list["closecaption"],$this->GetGS("closecaption"," Close "));
	$showsubmitbutton	= $this->GetParam($list["showsubmitbutton"],true);
	$showcancel			= $this->GetVar("nocancel",0) == 0 ? true : false;
	$showcancelbutton	= $this->GetParam($list["showcancelbutton"],$showcancel);
	$showclosebutton	= $this->GetParam($list["showclosebutton"],!$showcancel);
	$whendone			= $this->GetParam($list["whendone"],"");
	$arrayrs				= ($action == 'edit' || $action == 'view') ? $this->SelectLimit($sql,0,1) : array("0"=>array());
	$keyvalues			= $this->GetParam($arrayrs[0],array());
	$onfetch				= $this->GetParam($list["onfetch"],"");
	$onsave				= $this->GetParam($list["onsave"],"");
	$saveonsession		= $this->GetParam($list["saveonsession"],false);
	$savetodatabase	= $this->GetParam($list["savetodatabase"],true);
	$viewmode			= $this->GetParam($list["viewmode"],false);
	$allstriptags		= $this->GetParam($list["allstriptags"],false);
	$picturetable		= $this->GetParam($list["picturetable"],$this->GetGS("picturetable"));
	$mandatorymark		= $this->GetParam($list["mandatorymark"],"");
	$usecaptcha			= $this->GetParam($list["captcha"],false,'boolean');
	$usesecuritycode	= $this->GetParam($list["securitycode"],false,'boolean');
	$nextaction			= $this->GetNextAction($action);
	$titlerow			= preg_replace(array("|%%TITLE%%|","|%%ACTION%%|"),array($title,strtoupper($action)),$titlerow);
	$pkvalue				= htmlentities($keyid);
	$pkhidden			= "<input type=hidden name=PRIMARYKEY value=\"$pkvalue\">";
	$hiddens				= "";
	$ajaxinclude		= "";
	$no					= 0;
	$altno				= 0;
	$altno2				= 0;
	$spawno				= 0;
	$maxaltno			= count($altcolors)-1;
	$maxaltno2			= count($altcolors2)-1;
	$content				= "";
	$isIE					= $this->IsIE();
	$containsSPAW		= $this->AnyComponent($showfields,'spaw');
	$containsSPAW		= ($this->GetVar("enablespaw",$containsSPAW)==0) ? false : $containsSPAW;
	$containsAutoComp	= $this->AnyComponent($showfields,'autocomplete');
	$containsAjaxSel	= $this->AnyComponent($showfields,'ajaxselect');
	$submitcode			= ($viewmode) ? "" : $jsc->GenerateSubmitForm($formname,$confirmmessage,$onsubmitcaption,$showconfirmation,$validation,$isIE,$disablejs,$containsSPAW,$morejs,$calldefaultjs);
	$SPAWS				= array();
	$inputbuffers		= array();

	if ($containsAutoComp) {
		if ($this->autocompletecalled == false) {
			$autoCompleteScript = $this->CallAutoComplete();
			$content .= $autoCompleteScript;
			$submitcode = $autoCompleteScript.$submitcode;
  		}
	}

	if ($containsAjaxSel == true) {
		$ajaxinclude = $this->UseAjax();
	}

	if ($onfetch != "") {
		$keyvalues = call_user_func($onfetch,$keyvalues);
	}

	print $this->PrintSql($sql,$action,$printsql);

	$enctype				= ($method == "post") ? "enctype=\"multipart/form-data\"" : "";
	$openform			= "<form name=$formname method=$method $enctype action=$destination>";
	$content				.= "\n$submitcode\n$opentag\n<table $tblattrib>$openform\n$titlerow\n$subtitlelayout";

	//this part ensures formdesign to work properly when allfields = true while no
	//input tags are specified

	if ($formdesign != '') {
		$hiddens		 .= $this->CheckHiddenInputs($fieldnames,$showfields,$keyvalues,$formdesign);
 	}

	while(list($fieldname,$fieldconfig)=each($showfields)) {
		$fieldconfig	= $this->CheckFieldConfig($fieldconfig);
		$no++;
		$altcolor		= $altcolors[$altno];
		$altcolor2		= $altcolors2[$altno2];
		$fieldvalue		= $this->GetParam($keyvalues[$fieldname]);
		$fieldvalue		= stripslashes($fieldvalue);
		$defaultvalue	= $this->GetParam($fieldconfig["default"],$fieldvalue); //sebelumnya "";
		$fieldvalue		= $this->GetCustomValue($fieldvalue,$action,$defaultvalue,$this->GetParam($fieldconfig["customvalue"]));
		$type				= $this->GetParam($fieldconfig["type"],"text");
		$label			= $this->GetParam($fieldconfig["label"],$this->FormatLabel($fieldname));
		$rowhead			= $this->GetParam($fieldconfig["rowhead"],"");
		$rowtail			= $this->GetParam($fieldconfig["rowtail"],"");
		$rowlayout		= $this->GetParam($fieldconfig["rowlayout"],"$rowhead\n$defrowlayout\n$rowtail\n");
		$inputheader	= $this->GetParam($fieldconfig["inputheader"],"");
		$inputcaption	= $this->GetParam($fieldconfig["inputcaption"],"");
		$rowfunction	= $this->GetParam($fieldconfig["rowfunction"],"");

		$oldkeyvalues["OLD_$fieldname"]=$fieldvalue;

		if ($type == "spaw" && $this->GetVar("enablespaw",1) == '0') {
			$type	= "textarea";
		}

		if ($rowfunction != "") {
			$rowlayout	= call_user_func($rowfunction,$keyvalues,$rowlayout,$fieldname);
		}

		$input	= $this->GetInput($fieldname,$fieldvalue,$fieldconfig,$type,$table,$primarykey,$keyid,$formname,$disablejs,$pkshowonly,$keyvalues,$showfields);

		if ($type == "hidden") {
			$hiddens		.= $input;
		} elseif ($type == "hidden_autoupdate") {
			$hiddens		.= $input;
		} elseif ($type == "hidden_datetime_autoupdate") {
			$hiddens		.= $input;
		} elseif ($type == "hidden_transid") {
			$hiddens		.= $input;
		} elseif ($type == "hidden_autonumber") {
			$hiddens		.= $input;
		} elseif ($type == "spaw") {
			$width		= $this->GetParam($fieldconfig["width"],'650px');
			$height		= $this->GetParam($fieldconfig["height"],'300px');
			$style		= $this->GetParam($fieldconfig["style"],'default');
			$SPAWS[$spawno] = array($fieldname,$fieldvalue,$width,$height,$style);
			$spawno++;
			$inputrow	= preg_replace(array("|%%NO%%|","|%%LABEL%%|","|%%INPUT%%|","|%%ALTCOLOR%%|","|%%ALTCOLOR2%%|"),array($no,$label,"$inputheader$input$inputcaption",$altcolor,$altcolor2),$rowlayout);
			$content		.= $inputrow;
		} else {
			$mmark 		= "";
			If (array_key_exists($fieldname,$validation)) {
				$mmark 	= ($mandatorymark == '') ? "<b>*</b>" : $mandatorymark;
			}
			$inputrow	= preg_replace(array("|%%NO%%|","|%%LABEL%%|","|%%INPUT%%|","|%%ALTCOLOR%%|","|%%ALTCOLOR2%%|","|<MandatoryMark>|"),array($no,$label,"$inputheader$input$inputcaption",$altcolor,$altcolor2,$mmark),$rowlayout);
			$content		.= $inputrow;
			$inputbuffers[$fieldname] = $input;
		}
		$altno++; $altno2++;
		if ($altno > $maxaltno)   $altno = 0;
		if ($altno2 > $maxaltno2) $altno2 = 0;
	}

	$hiddens 			.= "%%SECURITY_CODE%%<input type=hidden name=oldkeyvalues value=\"".urlencode($this->Encrypt($oldkeyvalues))."\">";

	$cancelbutton		= $jsc->GenerateCancel($cancelcaption,$canceladdress,$showcancelbutton);
	$submitbutton		= ($showsubmitbutton == false) ? "" : $jsc->GenerateSubmit($submitcaption,$onsubmitcaption,$formname,$disablejs);
	$closebutton		= ($showclosebutton == false) ? "" : "<input type=button value=\" $closecaption \" onClick=\"window.close();\">";
	$buttons				= $this->BuildButtons($table,$submitbutton,$cancelbutton,$closebutton,$nextaction,$referrer,$pkhidden);
	$tdcaptcharow		= $this->BuildCaptchaRow($usecaptcha,$captchalabel,$captcharow,$formname);
	$tdbuttonrow		= str_replace("%%BUTTONS%%",$buttons,$buttonrow);
	$tdbuttonrow		= $this->Tag2Values($tdbuttonrow,$keyvalues);
	$content				.= "$hiddens\n$tdcaptcharow$tdbuttonrow\n</form>\n</table>\n$closetag\n";

	if ($formdesign != "") {
		$content		= $submitcode;
		$content		.= $this->ProcessFormDesign($primarykey,$keyid,$showfields,$formdesign,$inputbuffers,$openform,$hiddens,$buttons,$ajaxinclude);
		$content		= $this->Tag2Values($content,$keyvalues);
	}

	if (eregi("%%SPAW_LOCATION%%",$content)) {
		$content		= $this->ProcessSpawLocations($content,$SPAWS);
	}

	if (eregi("%%AJAX_INPUT_",$content)) {
		$content		= $this->ProcessAjaxInput($content,$keyvalues);
	}

	if (eregi("%%CAPTCHA%%",$content)) {
		if ($usecaptcha == true) {
			$content	= str_replace("%%CAPTCHA%%",$this->GetCaptcha($formname),$content);
  		} else {
			$content	= str_replace("%%CAPTCHA%%","",$content);
		}
	}

	if (eregi("%%SECURITY_CODE%%",$content)) {
		if ($usesecuritycode == true) {
			$content	= str_replace("%%SECURITY_CODE%%",$this->GetSecurityCode($formname),$content);
  		} else {
			$content	= str_replace("%%SECURITY_CODE%%","",$content);
		}
	}
	return $this->ReplaceColorTags($content);
}

function GenerateSecurityCode() {
	return md5(time());
}

function GetSecurityCode($formname) {
	$securitycode = $this->GenerateSecurityCode();
	$_SESSION[$formname."_security_code"] = $securitycode;
	return "<input name=$formname"."_SECURITY_CODE type=hidden value=$securitycode>";
}

function BuildCaptchaRow($usecaptcha,$captchalabel,$tdcaptcharow,$formname) {
	if ($usecaptcha == true) {
		return preg_replace(array("|%%LABEL%%|","|%%CAPTCHA%%|"),array($captchalabel,"%%CAPTCHA%%"),$tdcaptcharow);
 	}
}

function GetCaptcha($formname) {
	return "<table cellpadding=0 cellspacing=0><tr><td valign=middle><img src=\"/components/captcha/CaptchaSecurityImages.php?width=120&height=35&characters=6&frm=$formname\" /></td><td valign=middle> &nbsp;<input name=$formname"."_CAPTCHA_SECURITY type=text style='width: 80px;'></td></tr></table>";
}

function BuildFormTitle($title,$tablename,$action,$tablealias) {
	if ($title != "") {
		$result = $title;
	} else {
		if ($tablealias != "") {
			$tablename = $tablealias;
		} else {
			$tablename = str_replace("cms"," ",$tablename);
			$tablename = $this->FormatLabel($tablename);
		}
		if ($action == "edit") {
			$result = "EDIT :: $tablename";
		} elseif ($action == "add") {
			$result = "ADD :: $tablename";
		} elseif ($action == "view" || $action = "lookup") {
			$result = "VIEW :: $tablename";
		} elseif ($action == "search" || $action == "dosearch") {
			$result = "SEARCH :: $tablename";
		} else {
			$result = $tablename;
		}
	}
	return $result;
}

function GetCustomValue($fieldvalue,$action,$defaultvalue,$customvalue) {
	if ($action == "add") {
		$fieldvalue = $defaultvalue;
	} else {
		if ($customvalue <> "") {
			$fieldvalue = $customvalue;
		}
	}
	return $fieldvalue;
}

function ProcessFormDesign($primarykey,$keyid,$showfields,$formdesign,$inputbuffers,$openform,$hiddens,$buttons,$ajaxinclude) {
	$inputs	= array();
	$tags		= array();

	if (!eregi("%%$primarykey%%",$formdesign)) {
		$hiddens .= "<input type=hidden name=$primarykey value=\"$keyid\">";
	}

	foreach($showfields as $field => $config) {
		$type	= $this->GetParam($config["type"]);
		$tag	= "%%INPUT_$field%%";
		if ($type == "spaw") {
			$input = "%%SPAW_LOCATION%%";
		} else {
			$input = $this->GetParam($inputbuffers[$field],"");
		}
		array_push($inputs,$input);
		array_push($tags,"|$tag|");
	}
	array_push($inputs,$openform,"</form>",$buttons,$hiddens);
	array_push($tags,"|%%OPENFORM%%|","|%%CLOSEFORM%%|","|%%BUTTONS%%|","|%%HIDDENS%%|");

	$formdesign = preg_replace($tags,$inputs,$formdesign);
	return "$ajaxinclude $formdesign";
}

function ProcessSpawLocations($content,$SPAWS) {
	$contents		= split("%%SPAW_LOCATION%%",$content);
	$numcontents	= count($contents)-1;
	$result			= "";

	ob_start();
	for ($i=0;$i<$numcontents;$i++) {
		$fieldname	= $SPAWS[$i][0];
		$fieldvalue	= $SPAWS[$i][1];
		$width		= $SPAWS[$i][2];
		$height		= $SPAWS[$i][3];
		$style		= $SPAWS[$i][4];
		print $contents[$i];
		$this->GetSPAW($fieldname,$fieldvalue,$width,$height,$style);
	}
	print $contents[$numcontents];
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}

function CurrentDatetime($showtime=true) {
	return $this->BuildDate(date("Y"),date("m"),date("d"),date("h"),date("i"),date("s"),$showtime);
}

function GetSPAW($fieldname='spaw',$fieldvalue='',$width='650px',$height='200px',$style='default') {
	global $control_name, $spaw_root,$spaw_dir, $spaw_default_lang, $spaw_dropdown_data;
	global $HTTP_SERVER_VARS, $HTTP_HOST, $DOCUMENT_ROOT;

	$available_styles = array("mini","default","classic");
	if (!(in_array($style,$available_styles))) $style = "mini";

	$content = "";
	if ($this->SPAW_called == false) {
		define('DR', "$DOCUMENT_ROOT/");
		$spaw_root	= DR.'components/spaw/';
		$spaw_dir	= "/components/spaw/";
		include($spaw_root."spaw_control.class.php");
		$this->SPAW_called = true;
	}

	if ($style == "default" || $style == "classic") {
		$sw = new SPAW_Wysiwyg($fieldname,stripslashes($fieldvalue),'en','full',$style,$width,$height);
	} elseif ($style == "mini") {
		$sw = new SPAW_Wysiwyg($fieldname,stripslashes($fieldvalue),'en','mini','default',$width,$height,'');
	}
	$content .= $sw->show();
	print $content;
} //GetSPAW();

function GetInput($fieldname,$fieldvalue,$config,$deftype,$table,$primarykey,$keyid,$formname='',$disablejs,$pkshowonly=true,$keyvalues=array(),&$allfieldconfig) {
	if ($pkshowonly == true) {
		if ($pkshowonly = true && $fieldname == $primarykey && $deftype != "hidden" && $deftype != "hidden_transid" && $deftype != "hidden_autonumber") $deftype = "show";
 	}

	if ($this->GetVar("act") == "edit") {
		$config["readonly"] = $this->GetParam($config["editreadonly"],$this->GetParam($config["readonly"],false));
 	}

	$config["formname"] 	= $formname;	

	switch($deftype) {
	case 'show':
		$hidden	= $this->DisplayContent($fieldvalue,false);
		$input	= "$fieldvalue <input type=hidden name=$fieldname value=\"$hidden\">";
		break;
	case 'view':
		$input	= $this->DisplayContent($fieldvalue,true);
		break;
	case 'text':
		$input 	= $this->GetText($fieldname,$fieldvalue,$config);
		break;
	case 'password':
		$size		= $this->GetParam($config["size"],"20");
		$text		= htmlentities($fieldvalue);
		$input	= "<input type=password size=$size name=$fieldname value=\"$text\">";
		break;
	case 'textarea':
		$input	= $this->GetTextarea($fieldname,$fieldvalue,$config);
		break;
	case 'select':
		$input	= $this->GetSelect($fieldname,$fieldvalue,$config);
		break;
	case 'ajaxselect':
		$input	= $this->GetAjaxSelect($formname,$fieldname,$fieldvalue,$config,$allfieldconfig);
		break;
	case 'datetime_autoupdate':
		$input	= $this->dbDate($fieldvalue);
		break;
	case 'hidden_datetime_autoupdate':
		$autostring = $this->dbDate($fieldvalue);
		$input		= "<input type=hidden name=$fieldname value=\"$autostring\">";
		break;
	case "hidden_autoupdate":
		$autostring = $this->GetParam($config("autostring"),"");
		$input		= "<input type=hidden name=$fieldname value=\"$autostring\">";
		break;
	case 'hidden_transid':
		$input	= "<input type=hidden name=$fieldname value=\"$fieldvalue\">";
		break;
	case 'hidden_autonumber':
		$input	= "<input type=hidden name=$fieldname value=\"$fieldvalue\">";
		break;
	case 'dateselect':
		$input	= $this->GetDateSelect($fieldvalue,$fieldname,$config,false);
		break;
	case 'datetimeselect':
		$input	= $this->GetDateSelect($fieldvalue,$fieldname,$config,true);
		break;
	case 'datepopup':
		$input	= $this->GetDatePopup($formname,$fieldname,$fieldvalue,$config);
		break;
	case 'numeric':
		$size			= $this->GetParam($config["size"],10);
		$readonly	= $this->GetParam($config["readonly"])== true ? " style='background-color: #ebebeb' readonly" : "";
		$js			= "onKeyUp=\"MyNumberFormat(this);\" ";
		$style		= "style='text-align:right;'";
		$fieldvalue	= $this->MyNumberFormat($fieldvalue);
		$input		= "<input size=\"$size\" $style $js type=text name=$fieldname id=$fieldname value=\"$fieldvalue\" $readonly>\n";
		break;
	case 'hidden':
		$input	= "<input type=hidden name=$fieldname id=$fieldname value=\"$fieldvalue\">\n";
		break;
	case 'picture':
		$input	= $this->GetPictureUpload($table,$fieldname,$config,$primarykey,$keyid,$disablejs,$formname);
		break;
	case 'file':
		$input	= $this->GetFileUpload($table,$fieldname,$config,$primarykey,$keyid,$disablejs,$formname);
		break;
	case 'related_pictures':
		$input	= $this->GetPictureUpload($table,$fieldname,$config,$primarykey,$keyid,$disablejs,$formname,true);
		break;
	case 'related_files':
		$input	= $this->GetFileUpload($table,$fieldname,$config,$primarykey,$keyid,$disablejs,$formname,true);
		break;
	case 'radio':
		$input	= $this->GetRadioOrCheckbox("radio",$fieldvalue,$fieldname,$config);
		break;
	case 'checkbox':
		$input	= $this->GetRadioOrCheckbox("checkbox",$fieldvalue,$fieldname,$config);
		break;
	case 'spaw':
		$input	= "%%SPAW_LOCATION%%";
		break;
	case 'transid':
		$input	= "$fieldvalue<input type=hidden name=$fieldname value=\"$fieldvalue\">";
		break;
	case 'autonumber':
		$input	= "$fieldvalue<input type=hidden name=$fieldname value=\"$fieldvalue\">";
		break;
	case 'autocomplete':
		$input	= $this->GetAutoComplete($fieldname,$fieldvalue,$config);
		break;
	case 'lookup':
		$input	= $this->GetLookup($fieldname,$fieldvalue,$config,$keyvalues);
		break;
	case 'custom':
		$input	= $this->GetParam($config["control"],"[control] parameter not found");
		$input	= str_replace("%%FIELDVALUE%%",htmlentities($fieldvalue),$input);
		break;
	default:
		if (in_array($deftype,$this->customtypes)) {
			$input	= $this->GetCustomType($deftype,$fieldname,$fieldvalue,$config);
  		} else {
  		   $input	= "[$deftype is not a registered custom type.]<br>";
			//$input	.= $this->GetText($fieldname,$fieldvalue,$config);
		}
	break;
	}
	return $input;
}

function GetText($fieldname,$fieldvalue,$config) {
	$size		= $this->GetParam($config["size"],"60");
	$readonly= $this->GetParam($config["readonly"],false);
	$js		= $this->GetParam($config["js"],"");
	$text		= htmlentities($fieldvalue);

	if ($readonly == true) {
		$input	= "<input name=$fieldname id=$fieldname style='background-color:#ebebeb;' $js type=text size=$size value=\"$text\" readonly>";
  		} else {
		$input	= "<input type=text size=$size name=$fieldname id=$fieldname value=\"$text\" $js>";
	}
	return $input;
}

function GetCustomType($deftype,$fieldname,$fieldvalue,$config) {
	return $this->GetText($fieldname,$fieldvalue,$config);
}

function GetCustomTypeVar($type,$fieldname) {
	return $this->GetVar($fieldname);
}

function GetLookup($fieldname,$fieldvalue,$config,$keyvalues=array()) {
   $selection 		= $this->GetParam($config["selection"],array());
	$sql				= $this->GetParam($config["sql"],"");
	$field			= $this->GetParam($config["field"],"");
	$input			= '';
	$lookupvalue	= '';

	if ($sql != '' && $field == '') $input .= "[warning: please enter value for parameter field]<br>";

	if (count($selection)>0) {
		$lookupvalue = @$selection[$fieldvalue];
	}
	if ($sql != '') {
	   $sql	= $this->Tag2Values($sql,$keyvalues);
		$lookupvalue = $this->GetValue($sql,$field);
	}

	if ($lookupvalue == '') {
	 	$lookupvalue = $fieldvalue;
	}
	$input .= "<input type=hidden name=$fieldname value=\"$fieldvalue\">$lookupvalue";
	return $input;
}

function GetAutoComplete($fieldname,$fieldvalue,$config) {
   $field			= $this->GetParam($config['field']);
	$key				= $this->GetParam($config['key']);
	$value			= $this->GetParam($config['value']);
	$table			= $this->GetParam($config['table']);
	$sql				= $this->GetParam($config['sql']);
	$size				= $this->GetParam($config['size'],20);
	$size2			= $this->GetParam($config['size2'],20);
	$js				= $this->GetParam($config['js'],'');
	$show				= $this->GetParam($config['show'],true);
	$fillout			= $this->GetParam($config['fillout'],array());
	$showfields		= $this->GetParam($config['showfields'],array());
	$searchfields	= $this->GetParam($config['searchfields'],array());
	$tdwidth			= $this->GetParam($config['tdwidth'],array());
	$showreset		= $this->GetParam($config['showreset'],true);
	$ts				= time();
	$tdw				= '';
	$sq				= '';
	$fo				= '';
	$sf				= '';
	$sc				= '';
	$resetbtn		= '';
	$input			= '';

	if (count($fillout)>0) 		$fo 	= $this->Encrypt($fillout);
	if (count($showfields)>0) 	$sf 	= $this->Encrypt($showfields);
	if (count($searchfields)>0)$sc	= $this->Encrypt($searchfields);
	if ($sql != '')				$sq	= $this->Encrypt($sql);
	if ($tdwidth != '')			$tdw	= $this->Encrypt($tdwidth);

	if ($field == '')	$input .= "[warning: Enter parameter for field ]<br>";
	if ($key == '')	$input .= "[warning: Enter parameter for key ]<br>";
	if ($value == '')	$input .= "[warning: Enter parameter for value ]<br>";
	if ($table == '') $input .= "[warning: Enter parameter for table ]<br>";

	if ($show == true) {
		$input 	.= "<input size='$size' $js style='background-color: #ebebeb;' value=\"$fieldvalue\" type=text name=$field id=$field readonly>";
 	} else {
		$input 	.= "<input type=hidden name=$field id=$field>";
	}

	if ($showreset == true) {
		$resetbtn	= "<input type=button value=X onClick=\"$field.value='';AUTOINPUT_$field.value='';\">";
	}

	$input	.= "<input size='$size2' type=text name=AUTOINPUT_$field id=AUTOINPUT_$field />$resetbtn";

	$autojs 	= "<script>\n ts = new Date(); \n new Autocomplete(\"AUTOINPUT_$field\", function() {return \"/s/jfkAutoComplete_v104.php?sc=$sc&tdw=$tdw&sq=$sq&fo=$fo&sf=$sf&key=$key&value=$value&t=$table&field=$field&q=\" + this.value + '&ts='+ ts;}); \n</script>";
	return "$input $autojs";
}

function CallAutocomplete() {
	$this->autocompletecalled = true;
	$d = "/components/autocomplete";

	$retval =
	"<script type=\"text/javascript\" src=\"$d/prototype.js\"></script>\n".
	"<script type=\"text/javascript\" src=\"$d/autocomplete.js\"></script>\n".
	"<link rel=\"stylesheet\" type=\"text/css\" href=\"$d/autocomplete.css\" />\n\n";
	return $retval;
}

function GetDatePopup($formname,$fieldname,$fieldvalue,$fieldconfig) {
	$date		= ($fieldvalue == "") ? $this->CurrentDatetime() : $fieldvalue;
	$date		= date("d/m/Y",strtotime($date));
	$iframe	= $this->CallDatePopIFrame();
	$popimg	= "<a href=\"javascript:void(0)\" onclick=\"if(self.gfPop)gfPop.fPopCalendar(document.$formname.$fieldname);return false;\" >".
				"<img name=popcal align=absmiddle src=/components/calpop/calbtn.gif width=34 height=22 border=0 alt=\"\"></a>";
	$input	= "<input type=text name=$fieldname size=11 value=\"$date\" >$popimg$iframe";
	$this->calframecalled = true;
	return $input;
}

function CallDatePopIFrame() {
	if ($this->calframecalled == false) {
		return '<iframe width=174 height=189 name="gToday:normal:agenda.js" id="gToday:normal:agenda.js" src="/components/calpop/ipopeng.htm" scrolling="no" frameborder="0" style="visibility:visible; z-index:999; position:absolute; left:-500px; top:0px;"></iframe>';
	}
}

function GetDateSelect($fieldvalue,$fieldname,$fieldconfig,$showtime) {
	$fieldvalue	= ($fieldvalue == "") ? $this->CurrentDatetime() : $fieldvalue;
	$startyear	= $this->GetParam($fieldconfig["startyear"],1970);
	$endyear		= $this->GetParam($fieldconfig["endyear"],date("Y",time())+2);
	$readonly	= $this->GetParam($fieldconfig["readonly"],false);
	$input		= $this->DateSelect($fieldvalue,$fieldname,$showtime,$startyear,$endyear,$readonly);
	return $input;
}

function ChangeScalarToAssoc($selection,$valuepair="key_value") {
	$arraydata	= array();
	$row			= 0;
	while (list($key,$value)=each($selection)) {
		if ($valuepair == "value_value") $key = $value;
		$arraydata[$row] = array("key"=>$key,"value"=>$value);
		$row++;
	}
	return $arraydata;
}

function GetRadioOrCheckbox($type,$fieldvalue,$fieldname,$config) {
	$valuepair	= $this->GetParam($config["valuepair"],"key_value");
	$opentag		= $this->GetParam($config["opentag"],"<table cellpadding=3>");
	$closetag	= $this->GetParam($config["closetag"],"");
	$readonly	= $this->GetParam($config["readonly"]);
	$js			= $this->GetParam($config["js"]);
	$readtag		= ($readonly == true) ? "disabled" : "";
	$numcolumns	= $this->GetParam($config["numcolumns"],2);
	$selection	= $this->GetParam($config["selection"],array());
	$selection	= $this->ChangeScalarToAssoc($selection,$valuepair);
	$checkall	= $this->GetParam($config["checkall"],true);
	$jscheckall	= ($checkall == true) ? "&nbsp; &nbsp;
	<a href=javascript:void(0) onClick=\"CheckAll('$fieldname',true);\">Check All</a>  /
	<a href=javascript:void(0) onClick=\"CheckAll('$fieldname',false);\">Deselect All</a>" : "";
	$opentr		= "";
	$closetr		= "";

	if ($closetag == "") {
		$closetag = ($type == "radio") ? "</table>" : "</table>$jscheckall";
	}

	if ($numcolumns == 1) { $opentr = "<tr>"; $closetr = "</tr>"; }

	if ($type == "radio") {
		$layout	= "$opentr<td align=left width=20><input type=radio $readtag name=$fieldname"." value=\"%%key%%\" $js></td>
						<td>%%value%%&nbsp;</td>$closetr\n";
	} elseif ($type == "checkbox") {
		$layout	= "$opentr<td align=left width=20><input type=checkbox $readtag name=$fieldname"."[] value=\"%%key%%\" $js></td>
						<td>%%value%%&nbsp;</td>$closetr\n";
	}

	$list	= array
		(
		"dbdata"			=> false,
		"arraydata"		=> $selection,
		"sqlparse"		=> false,
		"columnar"		=> true,
		"numitems"		=> 255,
		"numcolumns"	=> $numcolumns,
		"rowfunction"	=> "GetSelectedRadioOrCheckbox@jfKlass2",
		"rowfuncparam"	=> array($fieldname,$fieldvalue,$readtag,$type,$js),
		"opentag"		=> $opentag,
		"emptycell"		=> "<td width=20>&nbsp;</td><td>&nbsp;</td>\n",
		"closetag"		=> $closetag,
		"layout"			=> $layout,
		);
	$input	= $this->ProcessContent($list);
	return $input;
}

function GetSelectedRadioOrCheckbox($keyvalues,$layout,$rowfuncparam) {
	$fieldname	= $rowfuncparam[0];
	$fieldvalue	= $rowfuncparam[1];
	$readtag		= $rowfuncparam[2];
	$type			= $rowfuncparam[3];
	$js			= $rowfuncparam[4];
	$key			= $keyvalues["key"];

	if ($type == "checkbox" && eregi("\|",$fieldvalue)) {
		$fieldvalues = split("\|",$fieldvalue);
	} else {
		$fieldvalues = array();
	}

	if (($key == $fieldvalue) || (in_array($key,$fieldvalues))) {
		if ($type == "radio") {
			$layout = "<td align=left width=20><input type=radio $readtag name=$fieldname value=\"%%key%%\" checked $js></td><td>%%value%%&nbsp;</td>\n";
		} else {
			$layout = "<td align=left width=20><input type=checkbox $readtag name=$fieldname"."[] value=\"%%key%%\" checked $js></td><td>%%value%%&nbsp;</td>\n";
		}
	}
	return $layout;
}

function GetFileUpload($table,$fieldname,$config,$primarykey,$keyid,$disablejs,$formname,$viewmode=false) {
	$uploadtable	= $this->GetParam($config["uploadtable"],$this->GetGS("tbl_upload","cms_upload"));
	$noinfo			= $this->GetParam($config["noinfo"],false);
	$noinfosetting	= $this->GetParam($config["noinfosetting"],false);
	$specific		= $this->GetParam($config["specific"],true);
	$expandedinfo	= $this->GetParam($config["expandedinfo"],false);
	$keyid			= ($keyid == '') ? time() : $keyid;
	$fieldclause	= ($specific == true) ? "and fieldname='$fieldname'" : "";
	$sql				= "select * from $uploadtable where tablename='$table' and keyid='$keyid' $fieldclause order by id";
	$relatedfile	= ($noinfo == true) ? "" : $this->GetRelatedFiles($sql,$fieldname,$config,$formname,$uploadtable,$viewmode);
	$initialstate	= ($expandedinfo == true) ? 'true' : 'false';

	if ($noinfosetting == true) {
		$infosetting = '';
		$infobutton	 = '';
	} else {
		$infosetting = $this->GetInfoSetting($fieldname,$expandedinfo);
		$infobutton	 = "<input type=button value=\" i \" onClick=\"ShowInfoSetting('$fieldname',divstate$fieldname);\">";
	}

	if ($viewmode == false) {
	$input =
	"
	$relatedfile
	<script>var divstate$fieldname = $initialstate </script>
	<table cellpadding=2 cellspacing=0 border=0>
	<tr><td width=90><i>File</i></td><td>
	<input type=file name=$fieldname>
	$infobutton
	</td></tr>
	</table>
	$infosetting
	";
	} else {
	$input = $relatedfile;
	}
	return $input;
}

function GetPictureUpload($table,$fieldname,$config,$primarykey,$keyid,$disablejs,$formname,$viewmode=false) {
	$picturetable		= $this->GetParam($config["picturetable"],$this->GetGS("tbl_picture","cms_picture"));
	$noinfo				= $this->GetParam($config["noinfo"],false);
	$specific			= $this->GetParam($config["specific"],true);
	$expandedinfo		= $this->GetParam($config["expandedinfo"],false);
	$thumbsize			= $this->GetParam($config["thumbsize"],120);
	$keyid				= ($keyid == '') ? time() : $keyid;
	$fieldclause		= ($specific == true) ? "and LOWER(fieldname)='".strtolower($fieldname)."'" : "";
	$sql					= "select * from $picturetable where tablename='$table' and keyid='$keyid' $fieldclause order by id";
	$relatedpicture	= ($noinfo == true) ? "" : $this->GetRelatedPictures($sql,$fieldname,$config,$formname,$picturetable,$viewmode);
	$thumbsetting		= $this->GetThumbsetting($fieldname,$config,$disablejs,$thumbsize);
	$initialstate		= ($expandedinfo == true) ? 'true' : 'false';
	$infosetting		= $this->GetInfoSetting($fieldname,$expandedinfo);
	$nothumbsetting	= $this->GetParam($config["nothumbsetting"],false);
	$noinfosetting		= $this->GetParam($config["noinfosetting"],false);
	$inforowsetting	= "";
	$thumbrowsetting	= "";

	if ($nothumbsetting == false) {
		$thumbsetting		= $this->GetThumbsetting($fieldname,$config,$disablejs,$thumbsize);
		$thumbrowsetting	= "<tr><td>&nbsp;</td><td>\r$thumbsetting\r</td></tr>";
	}

	if ($noinfosetting == false) {
		$infosetting		= $this->GetInfoSetting($fieldname,$expandedinfo);
		$inforowsetting	= "<input type=button value=\" i \" onClick=\"ShowInfoSetting('$fieldname',divstate$fieldname);\">\r";
	}

	if ($viewmode == false) {
		$input =
		"
		$relatedpicture
		<script>var divstate$fieldname = $initialstate </script>
		<table cellpadding=2 cellspacing=0 border=0>
		<tr><td width=90><i>Picture</i></td><td>
		<input type=file name=$fieldname>
		$inforowsetting
		</td></tr>
		<tr><td>&nbsp;</td><td>
		$thumbrowsetting
		</td></tr></table>
		$infosetting
		";
	} else {
		$input = $relatedpicture;
	}
	return $input;
}

function GetInfoSetting($fieldname,$expandedinfo) {
	$display			= ($expandedinfo == true) ? "block" : "none";
	$infosetting	=
	"
	<div name=divinfo$fieldname id=divinfo$fieldname style='display:$display;'>
	<table cellspacing=0 cellpadding=2>
	<tr><td width=90><i>Title</i></td><td><input type=text size=40 name=title_$fieldname id=title_$fieldname></td></tr>
	<tr><td width=90><i>Description</i></td><td><textarea rows=3 cols=30 name=description_$fieldname id=description_$fieldname></textarea></td></tr>
	</table>
	</div>
	";
	return $infosetting;
}

function GetThumbSetting($fieldname,$config,$disablejs,$thumbsize) {
	$sizes			= array("30"=>"30","45"=>"45","90"=>"90","120"=>"120","150"=>"150","180"=>"180","200"=>"200","250"=>"250");
	$sizes["thumbsize"] = $thumbsize;

	$resamples		= array("0"=>"Proportional","1"=>"Fixed");

	$thumb_js1		= " onChange=\"if(resample_$fieldname.value=='0') { thumbh_$fieldname.selectedIndex=thumbw_$fieldname.selectedIndex; }\" ";
	$thumb_js2		= " onChange=\"if(resample_$fieldname.value=='0') { thumbw_$fieldname.selectedIndex=thumbh_$fieldname.selectedIndex; }\" ";
	$resample_js	= " onChange=\"if(resample_$fieldname.value=='0') { thumbw_$fieldname.selectedIndex=thumbh_$fieldname.selectedIndex; }\" ";

	$wselect			= $this->OptionSelect($sizes,$thumbsize,"thumbw_$fieldname","value_value",$thumb_js1);
	$hselect			= $this->OptionSelect($sizes,$thumbsize,"thumbh_$fieldname","value_value",$thumb_js2);
	$resample		= $this->OptionSelect($resamples,0,"resample_$fieldname","key_value",$resample_js);
	$content			= "\n<input onClick=\"ShowThumbsetting(this,'$fieldname');\" type=checkbox value=thumbcheck_$fieldname checked> Automatic thumbnail ";
	$content			.= "\n<div name=divset$fieldname id=divset$fieldname style='display:inline;'> using this setting W: $wselect x H: $hselect $resample</div>";
	$content 		.= "\n<div name=divthumb$fieldname id=divthumb$fieldname style='display:none;'><input type=file name=thumb_$fieldname></div>";
	return $content;
}

function GetRelatedFiles($sql,$fieldname,$config,$formname,$uploadtable,$viewmode=false) {
	$dba				= new jfKlass2();
	$onefileonly	= $this->GetParam($config["onefileonly"],false);
	$title			= $this->GetParam($config["title"],"");
	$titleinfo		= ($title == "") ? "" : "<tr><td colspan=6 bgcolor=#a9a9a9><b>$title</b></td></tr>";
	$checkdelete	= ($viewmode) ? "" : "<input type=checkbox value=%%id%% name=delete_$fieldname"."[]>";
	$checkhead		= ($viewmode) ? "" : "<input type=checkbox OnClick=\"CheckAll('delete_$fieldname',this.checked);\">";
	$downloadpath	= $this->GetGS("downloadpath","downloads");

	if ($onefileonly == false) {
		$onefileinfo	= "";
		$NO_tag			= "%%NO%%.";
		$NO_label		= "No.";
	} else {
		$onefileinfo	= "<tr bgcolor=#ebebeb><td colspan=6><font color=#a7a7a7>Uploading a new picture will remove the current picture</a></td></tr>";
		$NO_tag			= "";
		$NO_label		= "";
	}

	$list = array
	(
	"sql"			=> $sql,
	"adminpage"		=> false,
	"altcolors"		=> $this->GetGS("altrcolors"),
	"opentag"		=> "
						<table cellpadding=3 cellspacing=1 bgcolor=%%CLR_RBGTABLE%% width=100%>
						$titleinfo
						<tr bgcolor=%%CLR_RHEAD%%>
						<td width=20><font color=%%CLR_RHEADTEXT%%>$NO_label</font></td>
						<td><font color=%%CLR_RHEADTEXT%%>File</font></td>
						<td><font color=%%CLR_RHEADTEXT%%><b>Title</b></font></td>
						<td align=right><font color=%%CLR_RHEADTEXT%%>Size</font></td>
						<td align=center>$checkhead</td></tr>
						",
	"closetag"		=> "$onefileinfo</table><br>",
	"xrowfunction"	=> "RelatedFileInfo@jfKlass2",
	"rowfuncparam"	=> array($formname,$uploadtable,$viewmode),
	"norecordstr"	=> "[ dokumen terkait tidak ditemukan ]<br><br>",
	"numericformat"=> ($dba->dbType() == 'ORACLE') ? array("FILESIZE") : array("filesize"),
	"layout"			=> "
						<tr bgcolor=%%ALTCOLOR%%><td align=right>$NO_tag</td>
						<td align=center width=50><a href=\"/$downloadpath/download.php?id=%%id%%&f=/%%path%%/%%filename%%\" target=_new><img src=/i/save.gif border=0></a></td>
						<td><input style='background:%%ALTCOLOR%%; border: none; font-weight: bold;' size=50 type=text name=$uploadtable"."_title_%%id%% value=\"%%title%%\"><br>
						%%description%%</td>
						<td width=60 align=right>%%filesize%%</td><td align=center width=20>$checkdelete</td></tr>
						",
	);

	$list		= $this->ModifyRelatedFilesLayout($list);
	$content = $this->ProcessContent($list);
	return $content;
}

function GetRelatedPictures($sql,$fieldname,$config,$formname,$picturetable,$viewmode=false) {
	$dba					= new jfKlass2();
	$onefileonly		= $this->GetParam($config["onefileonly"],false);
	$title				= $this->GetParam($config["title"],"");
	$titleinfo			= ($title == "") ? "" : "<tr><td colspan=6 bgcolor=#a9a9a9><b>$title</b></td></tr>";
	$checkdelete		= ($viewmode) ? "" : "<input type=checkbox value=%%id%% name=delete_$fieldname"."[]>";
	$checkhead			= ($viewmode) ? "" : "<input type=checkbox OnClick=\"CheckAll('delete_$fieldname',this.checked);\">";

	if ($onefileonly == false) {
		$onefileinfo	= "";
		$NO_tag			= "%%NO%%.";
		$NO_label		= "No.";
	} else {
		$onefileinfo 	= ($viewmode) ? "" : "<tr bgcolor=#ebebeb><td colspan=6><font color=#a7a7a7>Uploading a new picture will remove the current picture</a></td></tr>";
		$NO_tag 			= "";
		$NO_label 		= "";
	}

	$opentag				= "<table cellpadding=4 cellspacing=1 bgcolor=%%CLR_RBGTABLE%%>
								$titleinfo
								<tr bgcolor=%%CLR_RHEAD%%>
								<td><font color=%%CLR_RHEADTEXT%%>$NO_label</font></td>
								<td><font color=%%CLR_RHEADTEXT%%>Thumb</font></td>
								<td><font color=%%CLR_RHEADTEXT%%><b>Filename</font></b></td>
								<td align=center><font color=%%CLR_RHEADTEXT%%>Dimension</font></td>
								<td align=right><font color=%%CLR_RHEADTEXT%%>Size</font></td><td align=center>
								$checkhead</td></tr>";
	$layout				= "<tr bgcolor=%%ALTCOLOR%%><td align=right>$NO_tag</td>
								<td align=center>%%thumbname%%</a></td>
								<td><input style='background:%%ALTCOLOR%%; border: none; font-weight: bold;' size=40 type=text name=$picturetable"."_title_%%id%% value=\"%%title%%\"><br>%%description%%</td>
								<td align=center>%%width%% X %%height%%</td>
								<td align=right>%%filesize%%</td><td align=center>$checkdelete</td></tr>";
	$closetag			= "$onefileinfo</table><br>";
	$norecordstr		= "[ no related picture is found ]<br><br>";

	$list = array
	(
	"sql"					=> $sql,
	"adminpage"			=> false,
	"altcolors"			=> $this->GetGS("altrcolors"),
	"opentag"			=> $opentag,
	"numericformat"	=> ($dba->dbType() == 'ORACLE') ? array("FILESIZE") : array("filesize"),
	"closetag"			=> $closetag,
	"rowfunction"		=> "RelatedPictureInfo@jfKlass2",
	"rowfuncparam"		=> array($formname,$picturetable,$viewmode,$this->dbType()),
	"norecordstr"		=> $norecordstr,
	"layout"				=> $layout,
	);

	if ($this->dbType() == 'ORACLE') {
		$list["layout"]	= $this->TagsToUpper($list["layout"]);
		$list["opentag"]	= $this->TagsToUpper($list["opentag"]);
	}

	$list 	= $this->ModifyRelatedPicturesLayout($list);

	$content = $this->ProcessContent($list);
	return $content;
}

function RelatedPictureInfo($keyvalues,$layout,$rowfuncparam) {
	$dba 				= new jfKlass2();
	$formname 		= $rowfuncparam[0];
	$picturetable 	= $rowfuncparam[1];
	$viewmode 		= $rowfuncparam[2];
	$dbtype 			= $rowfuncparam[3];
	$thumbtag		= ($dbtype == 'ORACLE') ? "THUMBNAME" 		: "thumbname";
	$descripttag	= ($dbtype == 'ORACLE') ? "DESCRIPTION" 	: "description";
	$description 	= ($dbtype == 'ORACLE') ? $keyvalues["DESCRIPTION"] : $keyvalues["description"];
	$thumbname 	 	= $keyvalues[$thumbtag];

	if ($description == "") $description = "[No description]";
	if ($thumbname != "") {
		$link = "<a href=# onClick=\"pix%%id%% = window.open('/%%path%%/%%filename%%','pix%%id%%','width=400,height=400,scrollbars=yes,resizable=yes');pix%%id%%.focus();\"><img border=0 height=120 src=\"/%%thumbpath%%/%%thumbname%%\"></a>";
	} else {
		$link = "<a href=# onClick=\"pix%%id%% = window.open('/%%path%%/%%filename%%','pix%%id%%','width=400,height=400,scrollbars=yes,resizable=yes');pix%%id%%.focus();\">No Thumb</a>";
	}
	$layout = str_replace("%%$thumbtag%%",$link,$layout);

	$style  = "style='background:%%ALTCOLOR%%; color:#a7a7a7; border: none 0px;'";
	if ($viewmode) {
		$textlink = "$description";
	} else {
		$textlink = "<input $style name=$picturetable"."_desc_%%id%% type=text size=40 value=\"$description\">
						<a style='color: black; text-decoration: none;' href=javascript:void(0)
						onClick=\"x%%id%% = window.open('/s/jfkInfoEdit_v002.php?act=edit&keyid=%%id%%&f=$formname&t1=$picturetable"."_title_%%id%%&t2=$picturetable"."_desc_%%id%%&tbl=$picturetable','EditDescription_pic%%id%%','height=230, width=600, status=NO,toolbar=NO,scrollbars=YES,resizable=YES');x%%id%%.document.title = 's' ;x%%id%%.focus();return false;\">[&nbsp;Edit&nbsp;]</a>";
		$textlink = ($dbtype == 'ORACLE') ? $dba->TagsToUpper($textlink) : $textlink;
	}

	$layout	= str_replace("%%$descripttag%%",$textlink,$layout);

	if ($dbtype == 'ORACLE') $layout = $dba->TagsToUpper($layout);

	return $layout;
}

function RelatedFileInfo($keyvalues,$layout,$rowfuncparam) {
	$dba 			= new jfKlass2();
	$formname		= $rowfuncparam[0];
	$uploadtable	= $rowfuncparam[1];
	$viewmode		= $rowfuncparam[2];
	$description	= ($dba->dbType() == 'ORACLE') ? $keyvalues["DESCRIPTION"] : $keyvalues["description"];

	if ($description == "") $description = "[No description]";
	$style	= "style='background:%%ALTCOLOR%%; color:#a7a7a7; border: none 0px;'";
	if ($viewmode) {
	$textlink = "$description";
	} else {
	$textlink = "<input $style name=$uploadtable"."_desc_%%id%% type=text size=80% value=\"$description\">
					<a style='color: black; text-decoration: none;' href=javascript:void(0)
					onClick=\"x%%id%% = window.open('/s/jfkInfoEdit_v002.php?act=edit&keyid=%%id%%&f=$formname&t1=$uploadtable"."_title_%%id%%&t2=$uploadtable"."_desc_%%id%%&tbl=$uploadtable','EditDescription_pic%%id%%','height=230, width=600, status=NO,toolbar=NO');x%%id%%.focus();return false;\">[&nbsp;Edit&nbsp;]</a>";
	}
	$layout = str_replace("%%description%%",$textlink,$layout);
	$layout = $dba->TagsToUpper($layout);
	return $layout;
}

function GetSelect($fieldname,$fieldvalue,$config) {
	$selection		= $this->GetParam($config["selection"],array());
	$valuepair		= $this->GetParam($config["valuepair"],"key_value");
	$readonly		= $this->GetParam($config["readonly"],false);
	$js				= $this->GetParam($config["js"],"");
	$showvalue		= $this->GetParam($config["showvalue"],false);
	$input			= '';
	if ($showvalue == true) {
		$input 		.= "<input class=boxit type=text size=5 value=\"$fieldvalue\" name=VALUECAPTION_$fieldname readonly>&nbsp;";
		$js			.= " onchange=\"VALUECAPTION_$fieldname.value=this.value;\"";
	}
	$input .= $this->OptionSelect($selection,$fieldvalue,$fieldname,$valuepair,$js,$readonly);

	return $input;
}

function ProcessAjaxInput($content,$keyvalues) {
	$ajaxtmp = $this->ajaxtmp;
	foreach($ajaxtmp as $tag => $config) {
		$sqlselection	= $this->Tag2Values($config['sqlselection'],$keyvalues);
		$key				= $config['key'];
		$value			= $config['value'];
		$fieldvalue		= $config['fieldvalue'];
		$fieldname		= $config['fieldname'];
		$childfield		= $config['childfield'];
		$childkey		= $config['childkey'];
		$childvalue		= $config['childvalue'];
		$childsql		= $config['childsql'];
		$formname		= $config['formname'];
		$ajaxquery		= $config['ajaxquery'];
		$showvalue		= $config['showvalue'];
		$showvaluesize	= $config['showvaluesize'];
		$readonly		= $config['readonly'];
		$js				= $config['js'];
		$selection		= ($sqlselection == '') ? $config['selection'] : $this->StoreValuePairs($sqlselection,$key,$value,true);
		$showvaluebox	= '';
		$input			= '';
		$showvaluejs	= '';
		$ts				= time();

		if ($showvalue == true) {
			$showvaluejs	= "VALUECAPTION_$fieldname.value=this.value;";
			$showvaluebox 	= "<input class=boxit size=$showvaluesize name=VALUECAPTION_$fieldname type=text value=\"$fieldvalue \" readonly>";
			$input			= $showvaluebox;
		}

		if ($childfield == '') {
			$input		.= " ". $this->OptionSelect($selection,$fieldvalue,$fieldname,'key_value',"onChange=\"$showvaluejs\"",$readonly);
		} else {
		   $ts			= time();
			$ajaxvars	= $this->BuildAjaxVars($formname,$childsql,$ajaxquery);
			$clearjs		= $this->BuildAjaxClearCodeJs($childfield,$ajaxtmp,$showvalue);
			$onchangejs	= "$js onchange=\"makeHttpRequest('/s/jfkAjaxGetValues_v100.php?ts=$ts&key=$childkey&value=$childvalue'+$ajaxvars,'AjaxProcessFor$fieldname');$showvaluejs;$clearjs\"";
			$input		.= $this->CreateAjaxScript($formname,$fieldname,$childfield);
			$input 		.= $this->OptionSelect($selection,$fieldvalue,$fieldname,'key_value',$onchangejs,$readonly);
		}
		$content			= str_replace("%%$tag%%",$input,$content);
 	}
	unset($this->ajaxtmp);

	return $content;
}

function AjaxQueryFix($ajaxquery) {
	//this is to make sure ajaxquery parameter can be very flexible
	//either using Associative array or singular one will still work
	$tmparray = array();
	if ($this->IsAssocArray($ajaxquery) == false) {
		foreach($ajaxquery as $key) {
			$tmparray[$key] = $key;
		}
		$ajaxquery = $tmparray;
 	}
	return $ajaxquery;
}

function BuildAjaxVars($formname,$childsql,$ajaxquery) {
	$ajaxquery = $this->AjaxQueryFix($ajaxquery);
	$vars = "'&s=".urlencode($this->Encrypt($childsql));
	$vars .= "&ajaxquery=".urlencode($this->Encrypt($ajaxquery))."'";
	foreach($ajaxquery as $q => $field) {
		$vars .= "+'&$q='+document.$formname.$field.value";
	};
	return $vars;
}

function BuildAjaxClearCodeJs($childfield,$ajaxconfig,$showvalue=false) {
	$result = array();
	$result = $this->GetAjaxSelectChildren($childfield,$ajaxconfig,$result);
	$js	  = "$childfield.options.length=0;";
	if ($showvalue == true) {
		$js .= "VALUECAPTION_$childfield.value='';";
	}
	foreach($result as $child) {
	   if ($child != '') {
			$js  .= "$child.options.length=0;";
			if ($showvalue == true) {
				$js .= "VALUECAPTION_$child.value='';";
			}
    	}
	}
	return $js;
}

function GetAjaxSelectChildren($fieldname,$ajaxconfig,$result) {
	if (count($result) > 10) {
		print "Please fix the configuration for ajaxselect. A potential endless loop is detected.";
		exit;
	}
	$childfield = @$ajaxconfig["AJAX_INPUT_$fieldname"]["childfield"];
	array_push($result,$childfield);
	if ($childfield != '') {
		$result = $this->GetAjaxSelectChildren($childfield,$ajaxconfig,$result);
		return $result;
	}
	return $result;
}

function GetAjaxSelect($formname,$fieldname,$fieldvalue,$config,$allfieldconfig) {
	$selection		= $this->GetParam($config["selection"],array());
	$sqlselection	= $this->GetParam($config["sqlselection"],"");
	$valuepair		= $this->GetParam($config["valuepair"],"key_value");
	$readonly		= $this->GetParam($config["readonly"],false);
	$key				= $this->GetParam($config["key"],"");
	$value			= $this->GetParam($config["value"],"");
	$childfield		= $this->GetParam($config["childfield"],"");
	$childkey		= $this->GetParam($config["childkey"],$this->GetParam($allfieldconfig[$childfield]["key"]));
	$childvalue		= $this->GetParam($config["childvalue"],$this->GetParam($allfieldconfig[$childfield]["value"]));
	$childsql		= $this->GetParam($config["childsql"],$this->GetParam($allfieldconfig[$childfield]["sqlselection"]));
	$ajaxquery		= $this->GetParam($config["ajaxquery"],$this->BuildAjaxQuery($fieldname,$sqlselection));
	$showvalue		= $this->GetParam($config["showvalue"],false,'boolean');
	$showvaluesize	= $this->GetParam($config["showvaluesize"],5);
	$js				= $this->GetParam($config["js"],"");
	$readonly		= $this->GetParam($config["readonly"],false);
	$input			= '';

	if ($sqlselection == '' &&
		count($selection)== 0) 	$input .= '[warning: Enter parameter for field sqlselection or selection ] <br>';
	if ($key	== '') 				$input .= '[warning: Enter parameter for field key ] <br>';
	if ($value	== '') 			$input .= '[warning: Enter parameter for field value ] <br>';
	if ($childfield != '') {
		if ($childkey == '') 	$input .= '[warning: Enter parameter for field childfield] <br>';
		if ($childvalue == '') 	$input .= '[warning: Enter parameter for field childvalue] <br>';
		if ($childsql == '') 	$input .= '[warning: Enter parameter for field childsql] <br>';
		if ($ajaxquery == '') 	$input .= '[warning: Enter parameter for field ajaxquery] <br>';
	}

	$ajaxtag	= "AJAX_INPUT_$fieldname";
	$this->ajaxtmp[$ajaxtag] = array
	(
	"selection"		=> $selection,
	"sqlselection" => $sqlselection,
	"key"				=> $key,
	"value" 			=> $value,
	"fieldvalue"	=> $fieldvalue,
	"fieldname"		=> $fieldname,
	"childfield"	=> $childfield,
	"childkey"		=> $childkey,
	"childvalue"	=> $childvalue,
	"childsql"		=> $childsql,
	"ajaxquery"		=> $ajaxquery,
	"formname"		=> $formname,
	"showvalue"		=> $showvalue,
	"showvaluesize"=> $showvaluesize,
	"readonly"		=> $readonly,
	"js"				=> $js,
	);
	$input .= "%%$ajaxtag%%";
	return $input;
}

function BuildAjaxQuery($fieldname,$sqlselection) {
	$result[$fieldname] = $fieldname;
	preg_match_all("|%%(.+?)%%|",$sqlselection,$match);
	foreach($match[0] as $tag) {
		$field = str_replace("%%","",$tag);
		$result[$field] = $field;
	}
	return $result;
}

function CreateAjaxScript($formname,$fieldname,$childfield) {
	$js =
	"
	<script>
	function AjaxProcessFor$fieldname(x) {
		document.$formname.$childfield.options.length = 0
		s = x.split('||')
		for(i=0;i < s.length;i++) {
		   t = s[i].split('##')
			if (t[1] != undefined) {
				document.$formname.$childfield.options[i] = new Option(t[1],t[0]);
   		}
		}
	}
	</script>
	";
	return $js;
}

function GetTextarea($fieldname,$fieldvalue,$config) {
	$cols		= $this->GetParam($config["cols"],60);
	$rows		= $this->GetParam($config["rows"],10);
	$readonly= $this->GetParam($config["readonly"],false,'boolean');
	$js		= $this->GetParam($config["js"]);
	$text		= stripslashes(htmlentities($fieldvalue));
	if ($readonly == true) {
	   $readonlytag = "style='background-color: #ebebeb;' readonly";
		$input = "<textarea cols=$cols rows=$rows name=$fieldname id=$fieldname $js $readonlytag>$text</textarea>";
	} else {
		$input = "<textarea cols=$cols rows=$rows name=$fieldname id=$fieldname $js >$text</textarea>";
 	}
	return $input;
}

function CheckActionAccess($list,$act) {
	$disableadd		= $this->GetParam($list["disableadd"],false);
	$disableedit	= $this->GetParam($list["disableedit"],false);
	$disabledelete	= $this->GetParam($list["disabledelete"],false);
	$disableview	= $this->GetParam($list["disableview"],false);

	if (($disableedit == true) && in_array(strtolower($act),array("edit","saveedit"))) {
		return "Edit operation is not allowed.";
	}
	if (($disableadd == true) && in_array(strtolower($act),array("add","saveadd"))) {
		return "Add operation is not allowed.";
	}
	if (($disabledelete == true) && in_array(strtolower($act),array("delete","deleteconfirm"))) {
		return "Delete operation is not allowed.";
	}
	if (($disableview == true) && in_array(strtolower($act),array("view","lookup"))) {
		return "View operation is not allowed.";
	}
}

function ProcessAdmin($form,$list='',$view='',$directprint=false) {
	$list = $this->X($list);
	$form = $this->X($form);
	$act 	= strtolower($this->GetVar("act","show"));

	if ($list == '') {
		$table = $this->GetParam($form["table"]);
		$list = array("sql"=>"select * from $table");
	}

	$checkaccess = $this->CheckActionAccess($list,$act);
	if ($checkaccess <> "") {
		return $this->MessageBox("WARNING",$checkaccess,"__GO_BACK__");
	}

	if ($act == 'show') {
		$x = $this->ProcessContent($list);
	} elseif ($act == 'edit' || $act == 'add') {
		$x = $this->ProcessForm($form);
	} elseif ($act == 'saveedit' || $act == 'saveadd' || $act == 'delete' || $act == 'deleteconfirm') {
		$x = $this->ProcessAddEdit($form,$act);
	} elseif ($act == 'view') {
		$x = $this->ProcessView($form);
	} else {
		$x = "";
	}
	if ($directprint == false) {
		return $x;
	} else {
		print $x;
	}
}

function ProcessView($list,$current_act='') {
	$act					= $this->GetVar("act",$current_act);
	$table				= $this->GetParam($list["table"]);
	$allfields			= $this->GetParam($list["allfields"],false);
	$showfields			= $this->GetParam($list["fields"],array());
	$showfields			= $this->BuildFieldConfig($table,$showfields,$allfields);
	$showfields			= $this->CreateViewFields($showfields);
	$list["viewmode"]	= true;
	$list["fields"]	= $showfields;
	$list["showsubmitbutton"] = false;
	$list["captcha"]	= false;

	$this->SetVar("act","view");
	$content = $this->ProcessForm($list);
	$this->SetVar("act",$current_act);
	return $content;
}

function CreateViewFields($fields) {
	$result		= array();
	$artypes	= array('text','textarea','spaw','numeric','dateselect','datetimeselect');
	foreach($fields as $field => $config) {
		$type		 = $this->GetParam($config["type"],"text");
		if ($type == 'picture') {
			$type = 'related_pictures';
		} elseif ($type == 'file') {
			$type = 'related_files';
  		}
		$config["readonly"]		= true;
		$config["type"]			= $type;
		$config["rowlayout"]		= "";
		$result[$field]			= $config;
	}
	//$this->pre($result);
	return $result;
}

function DecodeParam($str) {
	return unserialize(base64_decode($str));
}

function DateFormat($date,$format='D, M d, Y',$lang='en') {
	if ($date == '1900-01-01') return "";

	$tmpyear 	= substr($date,0,4);
	$en_wdays	= array('|Sun|','|Mon|','|Tue|','|Wed|','|Thu|','|Fri|','|Sat|');
	$in_wdays	= array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
	$en_months	= array('|Jan|','|Feb|','|Mar|','|Apr|','|May|','|Jun|','|Jul|','|Aug|','|Sep|','|Oct|','|Nov|','|Dec|');
	$in_months	= array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
   
	if ((int)$tmpyear < 1950) {
		@list($tmpyear,$tmpmonth,$tmpday) = split('-',$date);
		$tmpmonthstr = ($lang == 'in') ? @$in_months[((int)$tmpmonth)-1] : @$en_months[((int)$tmpmonth)-1];
		$tmpmonthstr = str_replace('|','',$tmpmonthstr);
		$tmpshortyear= substr($tmpyear,-2);
		$resultdate  = preg_replace(array('|M|','|m|','|d|','|Y|','|y|'),array($tmpmonthstr,$tmpmonth,$tmpday,$tmpyear,$tmpshortyear),$format);
		return $resultdate;
	}

	if ($date == "") return "";
	$format		= ($format == '') ? 'D, M d Y' : $format;
	$date			= strtotime($date);
	$date			= date($format,$date);

	if ($lang == 'in') {
		$date	 = preg_replace($en_wdays,$in_wdays,$date);
		$date	 = preg_replace($en_months,$in_months,$date);
  	}
	return $date;
}

function VerifyCaptcha($formname='theform',$usecaptcha) {
	if ($usecaptcha == true) {
		$verifycode		= $this->GetVar($formname."_CAPTCHA_SECURITY");
		$captchacode	= @$_SESSION[$formname."_captcha_security_code"];
		@$_SESSION[$formname."_captcha_security_code"] = md5(time());
		if ($verifycode != $captchacode) {
			print $this->MessageBox("SECURITY CODE","Invalid Security Code was entered"," &nbsp;");
		 	exit;
		}
 	}
}

function VerifySecurityCode($formname='theform',$usesecuritycode) {
	if ($usesecuritycode == true) {
		$verifycode		= $this->GetVar($formname."_SECURITY_CODE");
		$securecode		= @$_SESSION[$formname."_security_code"];
		@$_SESSION[$formname."_security_code"] = md5(time());
		if ($verifycode != $securecode) {
			print $this->MessageBox("SECURITY CODE","Invalid action. Cannot repeat the last operation."," &nbsp;");
		 	exit;
		}
 	}
}

function ProcessAddEdit($form,$action) {
	global $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_POST_FILES;
	$method				= $this->GetParam($form["method"],"post");
	$table				= $this->GetParam($form["table"],"");
	$formname			= $this->GetParam($form["formname"],"theform");
	$keyvarname			= $this->GetParam($form["keyvarname"],"keyid");
	$printsql			= $this->GetParam($form["printsql"],false);
	$pkshowonly			= $this->GetParam($form["pkshowonly"],true);
	$allfields			= $this->GetParam($form["allfields"],false);
	$allstriptags		= $this->GetParam($form["allstriptags"],false);
	$deleteconfirm		= $this->GetParam($form["deleteconfirm"],array());
	$deletemessage		= $this->GetParam($form["deletemessage"],'');
	$deletevars			= $this->GetParam($form["deletevars"],array());
	$savetodatabase	= $this->GetParam($form["savetodatabase"],true);
	$saveonsession		= $this->GetParam($form["saveonsession"],false);
	$nextprocess		= $this->GetParam($form["nextprocess"],"");
	$showfields			= $this->GetParam($form["fields"],array());
	$showfields			= $this->BuildFieldConfig($table,$showfields,$allfields);
	$fieldnames			= $this->GetFieldnames($showfields);
	$validation			= $this->GetParam($form["validation"],array());
	$primarykey			= $this->GetParam($form["primarykey"],$fieldnames[0]);
	$primarykeytype	= $this->GetParam($showfields[$primarykey]["type"]);
	$picturetable		= $this->GetParam($form["picturetable"],$this->GetGS("tbl_picture","cms_picture"));
	$onsave				= $this->GetParam($form["onsave"],"");
	$onaftersave		= $this->GetParam($form["onaftersave"],"");
	$onafteradd			= $this->GetParam($form["onafteradd"],"");
	$ondelete			= $this->GetParam($form["ondelete"],"");
	$ondeleteconfirm	= $this->GetParam($form["ondeleteconfirm"],"");
	$whendone			= $this->GetParam($form["whendone"],"");
	$showwhendone		= $this->GetParam($form["showwhendone"],false);
	$addsuccessmsg		= $this->GetParam($form["addsuccessmsg"],"");
	$editsuccessmsg	= $this->GetParam($form["editsuccessmsg"],"");
	$usecaptcha			= $this->GetParam($form["captcha"],false,'boolean');
	$usesecuritycode	= $this->GetParam($form["securitycode"],false,'boolean');
	$referrer			= $this->GetVar("referrer");
	$oldkeyvalues		= $this->Decrypt($this->GetVar("oldkeyvalues"));
	$vars					= ($method == 'get') ? $HTTP_GET_VARS : $HTTP_POST_VARS;
	$nondbtypes			= array("picture","file","view","PICTURE","FILE","VIEW");
	$keyvalues			= array();
	$nonkeyvalues		= array();
	$allkeyvalues		= array();
	$nondbkeys			= array("PRIMARYKEY");
	$pictures			= array();
	$files				= array();
	$returnlabel		= "CONTINUE";

	if ($usecaptcha == true) {
		if (in_array($action,array("saveedit","saveadd"))) {
		$this->VerifyCaptcha($formname,$usecaptcha);
  		}
	}

	if ($usesecuritycode == true) {
		if (in_array($action,array("saveedit","saveadd"))) {
		$this->VerifySecurityCode($formname,$usesecuritycode);
  		}
	}

	$keyvalues["PRIMARYKEY"] = $this->GetVar("PRIMARYKEY");
	while(list($field,$config)=each($showfields)) {
		$config	= $this->CheckFieldConfig($config);
		$type    = $this->GetParam($config["type"],"text");
		$dbfield = $this->GetParam($config["dbfield"],true,'boolean');
		if (!in_array($type,$nondbtypes)) {
			$keyvalues[$field] = $this->GetVarType($field,$type,$method,$allstriptags,$primarykey,$table);
			if ($dbfield == false) array_push($nondbkeys,$field);
		} elseif ($type == "picture") {
			$pictures[$field] = $config;
			$nonkeyvalues[$field] = $this->GetParam($HTTP_POST_FILES[$field]["name"]);
		} elseif ($type == "file") {
			$files[$field] = $config;
		}
	}

	if (in_array($action,array("saveedit","saveadd"))) {
		$keyid = $keyvalues[$primarykey];

		$allkeyvalues = $keyvalues;
		foreach($nonkeyvalues as $key => $value) {
			$allkeyvalues[$key] = $value;
		}

		$validation_result = $this->ProcessServerValidation($allkeyvalues,$validation,$primarykey,$table);
		if (strlen($validation_result) > 0) {
			print $this->MessageBox("VALIDATION",$validation_result,"<a href=# onClick=\"history.go(-1);\">BACK</a>");
			return;
		}

		if ($onsave != "") {
			$keyvalues	= call_user_func($onsave,$keyvalues,$oldkeyvalues);
			$error		= @$keyvalues["__ERROR__"];
			$errorcapt	= @$keyvalues["__ERRORCAPTION__"];
			$returnlink	= @$keyvalues["__RETURNLINK__"];

			if ($error != '') {
			   $errorcapt 	= ($errorcapt != '') ? $errorcapt : "ERROR";
				$returnlink	= ($returnlink != '') ? $returnlink : "<a href=# onClick=\"history.go(-1);\">BACK</a>";
				print $this->MessageBox($errorcapt,"$error",$returnlink);
				return;
			}
		}
	} else {
		$keyid = $this->GetVar($keyvarname);
	}

	if ($action == "saveedit") {
		$title	= "UPDATE PROCESS";
		list($result,$sql) = $this->ProcessUpdate($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly,$savetodatabase,$editsuccessmsg);
		$result	= @$this->Tag2Values($result,$keyvalues);
		$result	= @$this->Tag2Values($result,$oldkeyvalues);
	}

	if ($action == "saveadd") {
		$title	= "ADD PROCESS";
		list($result,$sql,$keyid) = $this->ProcessAdd($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly,$savetodatabase,$addsuccessmsg);
		$result	= $this->Tag2Values($result,$keyvalues);
		if ($onafteradd != "") {
			$keyvalues = call_user_func($onafteradd,$keyvalues);
		}
	}

	if ($onaftersave != "") {
		$keyvalues[$primarykey] = $keyid;
		if (in_array($action,array("saveedit","saveadd"))) {
			$keyvalues = call_user_func($onaftersave,$keyvalues);
  		}
	}

	if ($action == "saveedit" || $action == "saveadd") {
		if ($nextprocess != '') {
	  	   while(@ob_end_clean());
			$passedvar = $this->EncryptSqlKeyvalues($sql,$keyvalues);
			header("location: $nextprocess?PV=$passedvar");
		}
	}

	if ($action == "delete") {
		$title	= "DELETE PROCESS";
		$keyid 	= $this->GetVar($keyvarname);
		list($result,$sql) = $this->BuildDeleteConfirmation($table,$deleteconfirm,$deletemessage,$keyid,$primarykey,$primarykeytype,$referrer,$ondelete,$deletevars,$usecaptcha);
		if (substr($result,0,9) == '__ERROR__') {
			return substr($result,10);
		} else {
			$returnlabel = 'BACK';
		}
	}

	if ($action == "deleteconfirm") {
		$title	= "DELETE PROCESS";
		$keyid 	= $this->GetVar("keyid");
		list($result,$sql) = $this->ProcessDelete($keyvalues,$primarykey,$primarykeytype,$keyid,$table,$ondeleteconfirm,$savetodatabase,$deletevars,$usecaptcha);
		if (substr($result,0,9) == '__ERROR__') {
			return substr($result,10);
		}
		foreach($deletevars as $deletevar){
			$keyvalues[$deletevar] = $this->GetVar($deletevar);
		}
	}

	if (in_array($action,array("saveedit","saveadd"))) {
		$this->ProcessPictureOrFileDeletion($pictures,"picture");
		$result .= $this->ProcessPictureUploads($table,$primarykey,$keyid,$pictures,$HTTP_POST_FILES,$picturetable);

		$this->ProcessPictureOrFileDeletion($files,"file");
		$result .= $this->ProcessFileUploads($table,$primarykey,$keyid,$files,$HTTP_POST_FILES);
	}

	print $this->PrintSql(@$sql,$action,$printsql);

	if ($whendone != "" && $action != "delete") {
		global $PHP_SELF;
		$jumpto 			= $this->Tag2Values($whendone,$keyvalues);
		$encmsg 			= urlencode($this->Encrypt($title.'@@@@'.$result));
		if ($whendone == "back_to_referrer") {
			$jumpto = "$PHP_SELF?$referrer&PMSG=$encmsg";
		}
		if ($showwhendone == true) {
			return $this->MessageBox($title,$result,"<a href=?$jumpto><font color=%%CLR_LINK%%>$returnlabel</font></a>");
			//echo "<meta http-equiv=\"Refresh\" content=\"1;URL=?$jumpto\" />";
			//die('hahaha');
	  	} else {
	  	   while(@ob_end_clean());
			$qm = (!eregi('\?',$jumpto)) ? '?' : '';
			if ($qm != '') {
				$jumplink = "$jumpto$qm&PMSG=$encmsg";
			} else {
				$jumplink = "$jumpto$qm&PMSG=$encmsg";
			}
			header("location: $jumplink");
			//echo "<meta http-equiv=\"Refresh\" content=\"0;URL=$jumplink\" />";
     	}
	} else {
	  return $this->MessageBox($title,$result,"<a href=?act=show><font color=%%CLR_LINK%%>$returnlabel</font></a>");
	}
}

function Encrypt($s) {
	return base64_encode(serialize($s));
}

function Decrypt($s) {
	return unserialize(base64_decode($s));
}

function EncryptSqlKeyvalues($sql,$keyvalues) {
	$encsql	= base64_encode(serialize($sql));
	$enckey	= base64_encode(serialize($keyvalues));
	$enc		= base64_encode($encsql .'@@@'. $enckey);
	return $enc;
}

function DecryptSqlKeyvalues($enc) {
   $dec		= base64_decode($enc);
	list($a1,$a2) = split('@@@',$dec);
	$decsql	= unserialize(base64_decode($a1));
	$deckey	= unserialize(base64_decode($a2));
	return array($decsql,$deckey);
}

function ProcessServerValidation($keyvalues,$validation,$primarykey,$tablename) {
	$message = "";
	//print_r($keyvalues);
	foreach($validation as $fieldname => $config) {
		$i					= 0;
		$rule				= $config["validate"];
		$warning			= $this->GetParam($config["warning"]);
		$fieldvalue		= @$keyvalues[$fieldname];
		if (is_array($rule)) {
			foreach($rule as $r) {
				$warn = $this->GetParam($warning[$i]);
				$message .= $this->ServerValidate($r,$fieldname,$fieldvalue,$warn,$primarykey,$tablename);
				$i++;
			}
		} else {
			$message .= $this->ServerValidate($rule,$fieldname,$fieldvalue,$warning,$primarykey,$tablename);
		}
	}
	return $message;
}

function ServerValidate($rule,$fieldname,$fieldvalue,$warning,$primarykey,$tablename) {
	$rule = trim(strtolower($rule));
	$message = "";
	if ($rule == 'must_fill') {
		if (strlen($fieldvalue)==0) {
			$message = ($warning != '') ? $warning : "Please fill $fieldname";
	}
	} elseif ($rule == 'valid_unique') {
		$keyid = $this->GetVar($primarykey);
		$sql = "select * from $tablename where $fieldname='$fieldvalue' order by $primarykey";
		$keyfound = $this->GetValue($sql,$primarykey);
		//if (($keyfound != $keyid) && ($keyfound != '')) { -- incorrect logic
		if ($keyfound != '') {
			if ($keyfound != $keyid) {
				$message = ($warning != '') ? $warning :  "The value '$fieldvalue' already exists in record with $primarykey $keyfound";
   		}
		}
	} elseif ($rule == 'valid_email') {
		if (!(preg_match("/(.*?@.*?)\.(.+?)+$/",$fieldvalue,$match))) {
			$message = ($warning != '') ? $warning : "'$fieldvalue' is not a valid email.";
		}
	} elseif ($rule == 'valid_numeric') {
		if (!is_numeric($fieldvalue)) {
			$message = ($warning != '') ? $warning : "'$fieldvalue' is not a numeric value.";
		}
	} elseif (eregi("must_check",$rule)) {
		list($rule,$min) = split(" ","$rule 0");
		$checked = split("\|",$this->StripWrapper($fieldvalue,"|"));
		if (count($checked) < $min) {
			$message = ($warning != '') ? $warning : "Please check at least $min options of the field $fieldname.";
		}
	} elseif (eregi("min_length",$rule)) {
		list($rule,$min) = split(" ","$rule 0");
		if (strlen($fieldvalue) < $min) {
			$message = ($warning != '') ? $warning : "$fieldname length is minimum $min characters";
		}
	} elseif (eregi("max_length",$rule)) {
		list($rule,$max) = split(" ","$rule 0");
		if (strlen($fieldvalue) > $max) {
			$message = ($warning != '') ? $warning : "$fieldname length is maximum $max characters";
		}
	} elseif (eregi("valid_date",$rule)) {
		if ($this->IsValidDate($fieldvalue)==false) {
			$message = ($warning != '') ? $warning : "'$fieldvalue' is not a valid date";
		}
	} elseif (eregi("not_exactly",$rule)) {
		$not_phrase = str_replace("not_exactly","",$rule);
		if (strlen($fieldvalue)==0) {
			$message = ($warning != '') ? $warning : "Please fill a valid value for $fieldname";
		}
	} else {
		$message = "";
	}
	$message = str_replace("%%FIELDVALUE%%",$fieldvalue,$message);
	if ($message != "") $message = "<br>$message";
	return $message;
}

function Tag2Values($string,$keyvalues) {
	foreach($keyvalues as $key => $value) {
		$tag		= "%%$key%%";
		$string	= str_replace($tag,addslashes($value),$string);
	}
	return $string;
}

function ProcessDelete($keyvalues,$primarykey,$primarykeytype,$keyid,$table,$ondeleteconfirm='',$savetodatabase=true,$deletevars=array(),$usecaptcha) {
	$fieldstr	= $this->FormatSqlStr($keyid,$primarykeytype);
	$sql			= "select * from $table where $primarykey=$fieldstr";
	$keyvalues	= $this->GetArrayValues($sql);

	$safekey		= $this->GetVar("safekey");
	$time			= $this->GetVar("time");
	$verify		= md5("$keyid$time");

	$this->VerifyCaptcha('theform',$usecaptcha);

	if ($safekey == $verify) {
	   if ($ondeleteconfirm != '') {
	   	$keyvalues	= call_user_func($ondeleteconfirm,$keyvalues);
			$error		= @$keyvalues["__ERROR__"];
			$errorcapt	= @$keyvalues["__ERRORCAPTION__"];
			$returnlink	= @$keyvalues["__RETURNLINK__"];

			if ($error != '') {
			   $errorcapt 	= ($errorcapt != '') ? $errorcapt : "ERROR";
				$returnlink	= ($returnlink != '') ? $returnlink : "<a href=# onClick=\"history.go(-1);\">BACK</a>";
				return array('__ERROR__'.$this->MessageBox($errorcapt,"$error",$returnlink),'');
			}
		}
		$sql	= $this->BuildDeleteSql($keyvalues,$primarykey,$keyid,$table);
		if ($savetodatabase == true) {
			if ($this->DeleteSql($sql)>0) {
				$result = "Data has been successfully deleted";
			} else {
				$result = "No data has been deleted <br><br>$this->sqlerr";
			}
   	} else {
   		$result = "Not saved to database";
		}
	} else {
		//$result = "$safekey<br>$verify<br>No data has been deleted";
		$result = "Verification key does not match! <br><br> No data has been deleted";
	}
	return array($result,@$sql);
}

function ProcessAdd($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly,$savetodatabase,$addsuccessmsg='') {
	$keyid = $keyvalues[$primarykey];
	$sql = $this->BuildInsertSql($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly);
	if ($savetodatabase == true) {
		if ($this->InsertSql($sql)>0) {
			$result = ($addsuccessmsg != '') ? $addsuccessmsg : "Data has been successfully added";
			$keyid = $this->GetLastInsertId($table,$primarykey,'',(!$pkshowonly),$keyid);
		} else {
			$result = "No data has been added <br><br>$this->sqlerr";
			$keyid = 0;
		}
   } else {
   	$result = "Not saved to database";
	}
	return array($result,$sql,$keyid);
}

function ProcessUpdate($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly,$savetodatabase,$editsuccessmsg='') {
	$sql = $this->BuildUpdateSql($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly);
	if ($savetodatabase == true) {
		if ($this->UpdateSql($sql)>0) {
			$result = ($editsuccessmsg != '') ? $editsuccessmsg : "Data has been successfully updated";
		} else {
			$result = "No data has been updated <br><br>$this->sqlerr";
		}
 	} else {
 		$result = "Not saved to database";
	}
	return array($result,$sql);
}

function PrintSql($sql,$action,$printsql=false) {
	if (in_array($action,array("saveedit","edit","saveadd","show")) && $printsql == true) {
		return $this->MessageBox("SQL:","$sql","","sqlbox");
	}
}

function ProcessPictureOrFileDeletion($pictures_or_files,$type="picture") {
	$type = ($type == "picture") ? "picture" : "upload";
	foreach($pictures_or_files as $key => $value) {
		$table	= $this->GetParam($value[$type."table"],$this->GetGS("tbl_$type","cms_$type"));
		$ids	= $this->GetVar("delete_$key");
		$num	= count($ids);
		if ($num != 0) {
		for ($i=0;$i<$num;$i++) {
			$id = $this->GetParam($ids[$i]);
			if ($id!='') {
				$this->DeletePictureOrFileInfo($id,$table);
			}
		}
		}
	}
}

function DeletePictureOrFileInfo($id,$table) {
	$sql = "delete from $table where id=$id";
	$this->DeleteSql($sql);
}

function DeletePictureOrFileRelation($keyid,$table,$tablename,$fieldname) {
	$sql = "delete from $table where keyid='$keyid' and tablename='$tablename' and fieldname='$fieldname'";
	$this->DeleteSql($sql);
}

function BuildDeleteConfirmation($table,$deleteconfirm,$deletemessage,$keyid,$primarykey,$primarykeytype,$referrer,$ondelete,$deletevars=array(),$usecaptcha=false) {
	$fieldstr	= $this->FormatSqlStr($keyid,$primarykeytype);
	$sql			= "select * from $table where $primarykey=$fieldstr";
	$result		= $this->GetArrayValues($sql);
	$content		= "<table cellpadding=3 cellspacing=1 align=center><form>";
	$time			= time();
	$safekey		= md5("$keyid$time");

	if (count($deleteconfirm) == 0) {
		$fieldnames			= @array_keys($result);
		$deleteconfirm		= array($fieldnames[0],$this->GetParam($fieldnames[1]),$this->GetParam($fieldnames[2]));
	}

	if ($ondelete != '') {
		$keyvalues		= call_user_func($ondelete,$result);
		$error			= @$keyvalues["__ERROR__"];
		$errorcapt		= @$keyvalues["__ERRORCAPTION__"];
		$returnlink		= @$keyvalues["__RETURNLINK__"];
		$newmessage		= @$keyvalues["__DELETEMESSAGE__"];

		if ($newmessage!= '') $deletemessage = $newmessage;

		if ($error != '') {
		   $errorcapt 	= ($errorcapt != '') ? $errorcapt : "ERROR";
			$returnlink	= ($returnlink != '') ? $returnlink : "<a href=# onClick=\"history.go(-1);\">BACK</a>";
			return array('__ERROR__'.$this->MessageBox($errorcapt,"$error",$returnlink),'');
		}
	}

	if ($deletemessage == '') {
		foreach($result as $key=>$value) {
			if (in_array($key,$deleteconfirm)) {
				$key			= $this->FormatLabel($key);
				$value		= $this->SmartSubstr($value);
				if ($key != "Action") {
				$content		.= "<tr><td width=80><b>$key</b></td><td width=200>$value</td></tr>";
				}
			}
		}
 	} else {
 	   $deletemessage = $this->Tag2Values($deletemessage,$result);
		$content .= "<tr><td>$deletemessage</td></tr>";
	}

	$deletevars_hidden 	  = "";
	foreach($deletevars as $deletevar) {
	   $deletevarvalue	  = $this->GetVar($deletevar);
		$deletevars_hidden .= "<input type=hidden name=$deletevar value=\"$deletevarvalue\">";
	}

	if ($usecaptcha == true) {
	   $captcha	 = $this->GetCaptcha('theform',$usecaptcha);
		$content .= "<tr><td colspan=2>$captcha</td></td></tr>";
	}

	$content	.= "
			<tr><td colspan=2 align=center>
			$deletevars_hidden
			<input type=hidden name=safekey value=\"$safekey\">
			<input type=hidden name=table value=$table>
			<input type=hidden name=referrer value=\"$referrer\">
			<input type=hidden name=time value=\"$time\">
			<input type=hidden name=act value=deleteconfirm>
			<input type=hidden name=keyid value=\"$keyid\">
			<input type=button value=Cancel onClick=history.go(-1)>
			<input type=submit value=Delete></td></tr>";
	$content	.= "</form></table>";
	return array($content,$sql);
}

function BuildInsertSql($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly) {
	$fieldinfo			= $this->GetFieldInfo($this->RunSql($this->SelectTableInfo($table)));
	$insertclause		= "insert into $table ";
	$fields				= "";
	$values				= "";

	foreach($keyvalues as $field => $fieldvalue) {
		$field		= ($this->dbType() == 'ORACLE') ? strtoupper($field) : $field;
		$type			 = $this->GetParam($fieldinfo[$field][0]);
		$fieldstr 	= $this->FormatSqlStr($fieldvalue,$type);
		if ((!in_array($field,$nondbkeys)) && (!in_array(strtolower($field),$nondbkeys))) { // && ($field != $primarykey)) {
			if ($field != $primarykey) {
				$fields .= "$field,";
				$values .= "$fieldstr,";
   		} else {
   			if ($pkshowonly == false) {
					$fields .= "$field,";
					$values .= "$fieldstr,";
				}
			}
		}
	}
	$fields = substr($fields,0,-1);
	$values = substr($values,0,-1);
	return "$insertclause ($fields) values ($values)";
}

function BuildUpdateSql($keyvalues,$nondbkeys,$primarykey,$table,$pkshowonly) {
	$keyid				= $keyvalues["PRIMARYKEY"];
	$fieldinfo			= $this->GetFieldInfo($this->RunSql($this->SelectTableInfo($table)));
	$whereclause		= " where ";
	$updateclause		= "update $table set ";
	foreach($keyvalues as $field => $fieldvalue) {
		$type			= $this->GetParam($fieldinfo[$field][0]);
		$fieldstr	= $this->FormatSqlStr($fieldvalue,$type);
		if ($field == $primarykey) {
			if ($pkshowonly == false) {
				$updateclause .= "$field=$fieldstr, ";
   		}
			$keyfieldstr	= $this->FormatSqlStr($keyid,$type);
			$whereclause  .= "$primarykey=$keyfieldstr";
		} else {
			if (!in_array($field,$nondbkeys)) {
				$updateclause .= "$field=$fieldstr, ";
			}
		}
	}
	$updateclause = substr($updateclause,0,-2);
	$updateclause.= $whereclause;
	return $updateclause;
}

function BuildDeleteSql($keyvalues,$primarykey,$keyid,$table) {
	$fieldinfo			= $this->GetFieldInfo($this->RunSql($this->SelectTableInfo($table)));
	$whereclause		= " where ";
	$updateclause		= "delete from $table ";
	$type					= $this->GetParam($fieldinfo[$primarykey][0]);
	$fieldstr			= $this->FormatSqlStr($keyid,$type);
	$whereclause		.= "$primarykey=$fieldstr";
	$updateclause		.= $whereclause;
	return $updateclause;
}

function ProcessFileUploads($tablename,$primarykey,$keyid,$files,$vars) {
	global $REMOTE_ADDR;
	$keyvalues = array();
	$message = "";
	while(list($field,$config)=each($files)) {
		$filename		= $this->GetParam($vars[$field]["name"]);
		$filetype		= $this->GetParam($vars[$field]["type"]);
		$tmpname			= $this->GetParam($vars[$field]["tmp_name"]);
		$filesize		= $this->GetParam($vars[$field]["size"]);
		$title			= $this->GetVar("title_$field",$filename);
		$description	= $this->GetVar("description_$field","");
		$destdir			= $this->GetParam($config["uploadpath"],$this->uploadpath);
		$destdir			= $this->StripWrapper($destdir,"/");
		$savefield		= $this->GetParam($config["fieldname"],$field);
		$tbl_upload		= $this->GetParam($config["tbl_upload"],$this->GetGS("tbl_upload","cms_upload"));
		$date				= $this->CurrentDatetime();
		$date				= $this->DateFormatStr($date);

		if ($filesize != 0) {
			$savedfile	= $this->SaveFile($tmpname,$filename,$destdir);
			$savedfile	= basename($savedfile);
			$new_id 		= 1 + (int)$this->GetLastInsertId($tbl_upload);

			if ($this->dbType() == 'ORACLE') {
				$sql = "insert into $tbl_upload (id,title,description,originalname,filename,filesize,tablename,fieldname,lastupdate,path,keyid,keyid2,sender) ".
						 "values ('$new_id','$title','$description','$filename','$savedfile','$filesize','$tablename','$savefield',$date,'$destdir','$keyid','','$REMOTE_ADDR')";
			} else {
				$sql = "insert into $tbl_upload (title,description,originalname,filename,filesize,tablename,fieldname,lastupdate,path,keyid,keyid2,sender) ".
						 "values ('$title','$description','$filename','$savedfile','$filesize','$tablename','$savefield',$date,'$destdir','$keyid','','$REMOTE_ADDR')";
			}
			$this->InsertSql($sql);
			$message	.= "<br><br>$filename<br> has been saved as <br>$filename";
		}

		if ((eregi("http://",$filename) || (eregi("www",$filename))) && ($filesize == 0)) {
			$this->InsertSql($sql);
			$message	.= "<br><br>$filename<br> has been saved as link.";
		}
		}
		return $message;
}

function ProcessPictureUploads($tablename,$primarykey,$keyid,$pictures,$vars,$picturetable) {
	$message = "";
	while(list($field,$config)=each($pictures)) {
		$filename		= $this->GetParam($vars[$field]["name"]);
		$filetype		= $this->GetParam($vars[$field]["type"]);
		$tmpname			= $this->GetParam($vars[$field]["tmp_name"]);
		$filesize		= $this->GetParam($vars[$field]["size"]);
		$destdir			= $this->GetParam($config["imagepath"],$this->imgpath);
		$thdir			= $this->GetParam($config["thumbpath"],$this->thumbpath);
		$thumbimage		= $this->GetParam($vars["thumb_$field"]["name"]);
		$thumbtmp		= $this->GetParam($vars["thumb_$field"]["tmp_name"]);
		$thumbsize		= $this->GetParam($vars["thumb_$field"]["size"]);
		$destdir			= $this->StripWrapper($destdir,"/");
		$thdir			= $this->StripWrapper($thdir,"/");
		$onefileonly	= $this->GetParam($config["onefileonly"],false);
		$tbl_picture	= $this->GetParam($config["picturetable"],$picturetable);
		$savefield		= $this->GetParam($config["fieldname"],$field);
		$thumbh			= $this->GetVar("thumbh_$field",120);
		$thumbw			= $this->GetVar("thumbw_$field",120);
		$resample		= $this->GetVar("resample_$field",0);
		$title			= $this->GetVar("title_$field",$filename);
		$filename		= $this->ValidateFilename($filename);
		$description	= $this->GetVar("description_$field");

		if (($filesize != 0) || ($thumbsize !=0)) {
			$savedfile = $this->SaveFile($tmpname,$filename,$destdir);
			$extension = $this->GetExtension($savedfile);
			if ($savedfile != "" && (in_array($extension,array("jpg","jpeg")))) {
				$file = basename($savedfile);
				$message .= "<br><br>Picture $filename has been saved as <br><a href=$savedfile target=_new>$file</a> <br><br>";
				if ($thumbimage == "") {
					$ts			= time();
					$thumbfile	= $this->root()."/$thdir/tn_$ts$filename";
					list($new_w,$new_h,$w,$h) = $this->ResampleSize($savedfile,$thumbh,$thumbw,$resample);
					$result		= $this->CreateThumbnail($savedfile,$thumbfile,$new_w,$new_h);
					$thumb		= basename($thumbfile);
					$message		.= "Thumbnail has been saved as <a href=$thumbfile target=_new>$thumb</a>";
				} else {
					$thumbfile	= $this->SaveFile($thumbtmp,"tn_$thumbimage",$thdir);
					$thumb		= basename($thumbfile);
					$message		.= "Custom thumbnail has been saved as <br> <a href=$thumbfile target=_new>$thumb</a><br>";
				}
			} else {
				if ($savedfile != "" && (in_array($extension,array("gif")))) {
					if ($thumbimage == "") {
						$thumbfile	= "";
						$thdir		= "";
					} else {
						$thumbfile	= $this->SaveFile($thumbtmp,"tn_$thumbimage",$thdir);
						$thumb		= basename($thumbfile);
						$message		.= "<br><br>Custom thumbnail has been saved as <br> <a href=$thumbfile target=_new>$thumb</a><br>";
					}
				} else {
					$thumbfile	= $this->SaveFile($thumbtmp,"tn_$thumbimage",$thdir);
					$thumb		= basename($thumbfile);
					$title		= ($title != "") ? $title : $thumb;
					$message		.= "<br><br>Custom thumbnail has been saved as <br> <a href=$thumbfile target=_new>$thumb</a><br>";
				}
			}
			list($w,$h) = getimagesize($savedfile);
			if ($onefileonly == true) {
				$this->DeletePictureOrFileRelation($keyid,$tbl_picture,$tablename,$field);
			}
				$this->SavePictureInfo($config,$title,$description,$filename,$filesize,$savedfile,$destdir,$thumbfile,$thdir,$w,$h,$savefield,$tablename,$primarykey,$keyid,$picturetable);
			}
	}
	return $message;
}

function AnyComponent($showfields,$component) {
	foreach($showfields as $fieldname => $config) {
		$type = trim(strtolower($this->GetParam($config["type"])));
		if ($type == "$component") return true;
	}
	return false;
}

function SmartSubstr($s,$start=0,$len=255) {
	$s = strip_tags($s,'<br>');
	if (strlen($s) > $len) {
		$s = substr($s,$start,$len);
		$x = strrpos($s," ");
		if ($x != '') {
			$s = substr($s,$start,$x);
		}
		$s .= "...";
		return $s;
	} else {
		return $s;
	}
}

function SavePictureInfo($config,$title,$description,$filename,$filesize,$savedfile,$destdir,$thumbfile,$thdir,$w,$h,$field,$tablename,$primarykey,$keyid,$picturetable) {
	$picturetable	= $this->GetParam($config["picturetable"],$picturetable);
	$savedfile		= basename($savedfile);
	$thumbfile		= basename($thumbfile);

	if ($this->dbType() == 'ORACLE') {
		$keyvalues["id"] 			= 1 + (int)$this->GetLastInsertId($picturetable);
		$primarykey					= '';
	}

	$keyvalues["originalname"]	= $filename;
	$keyvalues["filename"]		= $savedfile;
	$keyvalues["filesize"]		= $filesize;
	$keyvalues["title"]			= $title;
	$keyvalues["description"]	= $description;
	$keyvalues["tablename"]		= $tablename;
	$keyvalues["fieldname"]		= $field;
	$keyvalues["keyid"]			= $keyid;
	$keyvalues["thumbname"]		= $thumbfile;
	$keyvalues["path"]			= $destdir;
	$keyvalues["thumbpath"]		= $thdir;
	$keyvalues["width"]			= $w;
	$keyvalues["height"]			= $h;
	$keyvalues["lastupdate"]	= $this->CurrentDatetime();
	$keyvalues["picorder"]		= abs(100000000 - (int)time());

	$sql = $this->BuildInsertSql($keyvalues,array(),$primarykey,$picturetable,true);
	$this->InsertSql($sql);
}

function ResampleSize($picturefile,$targetw=120,$targeth=120,$resample=0) {
	if ($resample == '1') return array($targetw,$targeth);
	list($w,$h)		= getimagesize($picturefile);
	$max				= ($targetw >= $targeth) ? $w : $h;
	$source_max		= ($w >= $h) ? $w : $h;
	$target_max		= ($targetw >= $targeth) ? $targetw : $targeth;
	$proportion		= $target_max / $source_max;
	$new_w			= ceil($proportion * $w);
	$new_h			= ceil($proportion * $h);
	return array($new_w,$new_h,$w,$h);
}


function CreateThumbnail($sourcefile,$destfile,$width=120,$height=120) {
	$source 	= imagecreatefromjpeg($sourcefile);
	$source_h 	= imagesx($source);
	$source_w	= imagesy($source);

	$dest_w	= $width;
	$dest_y	= $height;

	$thumb_w	= $width;
	$thumb_y	= $height;

	$dest = imagecreatetruecolor($thumb_w, $thumb_y);

	imagecopyresized($dest, $source, 0, 0, 0, 0, $thumb_w+2, $thumb_y+2, $source_h, $source_w);
	imagejpeg($dest,$destfile);
	return true;
} //CreateThumbnail()


function SaveFile($tmpname,$filename,$destdir) {
	$ts = time();
	$filename = $this->ValidateFilename($filename);
	$targetfile = $this->root()."/$destdir/$ts"."_$filename";

	if (copy($tmpname,$targetfile)) {
		return $targetfile;
	}
	return "";
}

function ValidateFilename($filename) {
	$invalidchars 	= array("|\'|","|\"|","|\/|","|%|","|:|");
	$filename 		= eregi_replace(" ","_",$filename);
	$filename 		= preg_replace($invalidchars,"",$filename);
	$filename		= str_replace('\\',"",$filename);
	return trim(strip_tags($filename));
}

function GetVarType($field,$type,$method,$allstriptags=false,$primarykey='',$table) {
	$type = strtolower($type);

	if ($type == 'hidden_autonumber' || $type == 'autonumber') $type = 'autonumber';
	if ($type == 'hidden_transid' || $type == 'transid') $type = 'transid';

	switch($type) {
	case 'numeric':
		$value = $this->Numerize($this->GetVar($field,"",$method));
		break;
	case 'checkbox':
		$value = $this->GetVarCheckbox($field,$method);
		break;
	case 'dateselect':
		$value = $this->GetVarDate($field,$method,false);
		break;
	case 'datetimeselect':
		$value = $this->GetVarDate($field,$method,true);
		break;
	case 'datetime_autoupdate':
		$value = $this->CurrentDatetime();
		break;
	case 'datepopup':
		$date  = $this->GetVar($field,"",$method);
		$value = ($date != "") ? $this->dbDate($date) : "";
		break;
	case 'transid':
		if ($this->GetVar("act","",$method) == "saveadd") {
			$value = $this->GenerateID('',true);
  		} else {
  			$value = $this->GetVar($field,"",$method);
		}
		break;
	case 'autonumber':
		if ($this->GetVar("act","",$method) == "saveadd") {
			$value = (int)$this->GetLastInsertId($table,$primarykey,'',false);
			$value++;
  		} else {
  			$value = $this->GetVar($field,"",$method);
		}
		break;
	default:
		if (in_array($type,$this->customtypes)) {
  			$value = $this->GetCustomTypeVar($type,$field,$method);
  		} else {
			$value = $this->GetVar($field,"",$method);
		}
		break;
	}
	if ($allstriptags == true) {
		return strip_tags($value);
 	}
 	return $value;
}

function StartOfMonth() {
	$dtime = $this->BuildDate(date("Y"),date("m"),"01",0,0,false);
	return $dtime;
}

function ValidDate($date) {
	if (eregi("-",$date)) {
		$elmnt	= split("-",$date);
		$year		= $this->GetParam($elmnt[0],0);
		$month	= $this->GetParam($elmnt[1],0);
		$day		= $this->GetParam($elmnt[2],0);
	} else {
		$elmnt	= split("/",$date);
		$year		= $this->GetParam($elmnt[2],0);
		$day	= $this->GetParam($elmnt[0],0);
		$month		= $this->GetParam($elmnt[1],0);
	}

	$cdate	= mktime (0,0,0,$month,$day,$year);
	if (checkdate(intval($month),intval($day),intval($year))== true) {
		return true;
	} else {
		return false;
	};
} //ValidDate()

function GetVarCheckbox($field,$method) {
	$value = $this->GetVar($field,"",$method);
	if (is_array($value)) {
		$value = "'".join('|',$value)."|'";
	}
	return $value;
}

function GetVarDate($field,$method,$showtime,$default='') {
	$year		= $this->GetVar($field."_year","1900",$method);
	$month	= $this->GetVar($field."_month","01",$method);
	$day		= $this->GetVar($field."_day","01",$method);
	$hour		= $this->GetVar($field."_hour","",$method);
	$min		= $this->GetVar($field."_minute","",$method);

	if ($month == '0' && $year == '1900' && $day == '01') $month = '01';

	$value	= $this->BuildDate($year,$month,$day,$hour,$min,"00",$showtime);

	if (@checkdate($month,$day,$year)==false) {
		if ($default != '') {
			$value = $default;
		}
	}
	return $value;
}

function GetNextAction($action) {
	if ($action == "edit") {
		$nextaction		= "saveedit";
	} elseif ($action == "add") {
		$nextaction		= "saveadd";
	} else {
		$nextaction		= $action;
	}
	return $nextaction;
}

function self() {
	global $PHP_SELF;
	return $PHP_SELF;
}

function root() {
	global $DOCUMENT_ROOT;
	return $DOCUMENT_ROOT;
}

function GetInputType($fieldtype,$fieldlen) {
	$fieldtype	= strtolower($fieldtype);
	$textarea	= array("text","blob");
	$text			= array("char","varchar","string");
	$numeric		= array("int","smallint","tinyint","long","numeric","double","number");
	$date			= array("date","datetime");

	if (in_array($fieldtype,$textarea)) {
		return "textarea";
	} elseif (in_array($fieldtype,$text)) {
		return ($fieldlen > 255) ? "textarea" : "text";
	} elseif (in_array($fieldtype,$numeric)) {
		return "numeric";
	} elseif (in_array($fieldtype,$date) || eregi("date",$fieldtype)) {
		return "dateselect";
	} else {
		return "text";
	}
}

function BuildButtons($table,$submitbutton,$cancelbutton,$closebutton,$action,$referrer,$pkhidden) {
	//--deleted as of 139 <input type=hidden name=table value=$table>
	$buttons = "&nbsp;
				$pkhidden
				<input type=hidden name=referrer value=\"$referrer\">
	 			<input type=hidden name=act value=$action>
				$closebutton $cancelbutton $submitbutton";
	return $buttons;
}

function BuildOpenTagFromLayout($layout,$tblattrib) {
	if (eregi("^<tr",trim($layout))) {
		return "<table $tblattrib>";
	}
}

function BuildOpenTagSortlink($opentag) {
	$tags		= array();
	$links	= array();
	preg_match_all("|%%SORTLINK_(.*?)%%|",$opentag,$match);
	$nummatch = count($match);
	for($i=0;$i<$nummatch;$i++) {
		$field	= $match[1][$i];
		$tag		= "|%%SORTLINK_$field%%|";
		$link		= $this->BuildSortHREF($field,$field);
		array_push($tags,$tag);
		array_push($links,$link);
	}
	$opentag = preg_replace($tags,$links,$opentag);
	return $opentag;
}

function SelectArrayLimit($arraydata,$start=0,$numitems=20) {
	$row = 0;
	$result = array();
	foreach($arraydata as $key => $value) {
		if ($row >= $start && $row < ($start+$numitems)) {
		$result[$row] = $value;
	}
	$row++;
	}
	return $result;
}

function DeleteArrayElement($array,$element) {
	$offset = array_search($element,$array);
	if ($offset > -1) {
		array_splice($array,$offset,1);
	}
	return $array;
}

function InsertArrayElement($array,$element,$offset=0) {
	$result = array();
	$i = 0;
	foreach($array as $a) {
		if ($i == $offset) {
			array_push($result,$element);
		}
		array_push($result,$a);
		$i++;
	}
	if ($offset >= $i) {
		array_push($result,$element);
	}
	return $result;
}

function FieldOrderSort($fieldorder) {
	$result = array();
	$max	 = 0;
	foreach($fieldorder as $order => $field) {
		if ($order > $max) {
			$max = $order;
		}
	}
	for($i=-100;$i<=$max;$i++) {
		$field = $this->GetParam($fieldorder[$i]);
		if ($field != '') $result[$i] = $field;
	}
	return $result;
}

function SetFieldOrder($fields,$fieldorder,$numbering,$adminpage) {
	if (count($fieldorder)>0) {
		$fieldorder = $this->FieldOrderSort($fieldorder);
		$result = array();
		$i = 0;
		foreach($fieldorder as $pos => $field) {
			if ($numbering == false && $field == "NO") {
			} elseif ($adminpage == false && $field == "DEFAULT_ACTION") {
			} elseif ($pos < 0) {
			   $fields = $this->DeleteArrayElement($fields,$field);
			} else {
			   $fields = $this->DeleteArrayElement($fields,$field);
				$fields = $this->InsertArrayElement($fields,$field,$pos);
			}
		}
		return $fields;
	} else {
		return $fields;
	}
}

function CheckDefaultFields($fields,$adminpage,$showactions,$numbering) {
	if (!in_array("DEFAULT_ACTION",$fields) && (($adminpage == true) && ($showactions == true))) array_push($fields,"DEFAULT_ACTION");
	if (in_array("DEFAULT_ACTION",$fields) && $adminpage == true && $showactions == false) $fields = $this->DeleteArrayElement($fields,"DEFAULT_ACTION");

	if ($showactions == false) {
		$fields = $this->DeleteArrayElement($fields,"DEFAULT_ACTION");
	}

	if ($numbering == true) {
		if (!in_array("NO",$fields)) array_unshift($fields,"NO");
	}
	return $fields;
}

function BuildSearchBox($fields,$labels,$table,$searchparams) {
	//$searchfields = array(""=>"--Show All--");
	foreach($fields as $field) {
		if (!in_array($field,$this->nosortfields)) {
		$searchfields[$field] = $this->GetParam($labels[$field],$this->FormatLabel($field));
		}
	}
	$hiddens = "";
	foreach($searchparams as $param) {
		$pvalue	= $this->GetVar($param);
		$hiddens .= "<input type=hidden name=$param value=\"$pvalue\">\n";
	}
	$default = $this->GetVar("searchfield");
	$query	= ($default == "") ? "" : stripslashes($this->GetVar("query"));
	$js 		= " onChange=\"if(this.selectedIndex==0){query.value='';} \"";
	$options = $this->OptionSelect($searchfields,$default,"searchfield","key_value",$js);
	$searchbox = "<table cellpadding=0><form><tr><td>Search &nbsp;</td><td><input value=\"$query\" size=15 name=query type=text></td><td>$options</td><td><input type=submit value=\" Go \"></td></tr><input type=hidden name=table value=\"$table\">$hiddens</form></table>";
	return $searchbox;
}

function InStr($query,$value) {
	$query		= strtolower(trim($query));
	$value		= strtolower($value);
	$charstart	= substr($query,0,1);
	$charend		= substr($query,-1,1);
	$query		= $this->StripWrapper($query,'*');
	$qlen			= strlen($query);

	if ($charstart == '*' && $charend == '*') {
		$op = 'contains';
	}
	if ($charstart == '*' && $charend != '*') {
		$op = 'endswith';
	}
	if ($charstart != '*' && $charend == '*') {
		$op = 'startswith';
 	}

	if ($op == 'endswith') {
		$chunk = substr($value,(0-$qlen));
		if ($chunk == $query) {
			return 0;
		}
	}

	if ($op == 'startswith') {
		$chunk = substr($value,0,$qlen);
		if ($chunk == $query) {
			return 0;
		}
	}

	if ($op == 'contains') {
		$firstchunk = substr($value,0,$qlen);
		$lastchunk	= $chunk = substr($value,(0-$qlen));
		if (eregi($query,$value)) {
			if ($firstchunk != $query && $lastchunk != $query) {
				return 0;
			}
		}
	}
	return -1;
}

function BuildSearchClause($searchfield,$query,$replace,$fieldinfo) {
	if ($this->dbType() == 'ORACLE') {
		$fieldtype 		= $this->GetParam($fieldinfo[$searchfield][0],"string");
		if (eregi('DATE',strtoupper($fieldtype))) {
			$searchclause = "TO_CHAR($searchfield,'YYYY-MM-DD') LIKE '%$query%'";
		} else {
		   if (!eregi('\%',$query)) {
			   $query			= $this->EscapeStr(strtolower($query));
				$searchclause 	= "LOWER($searchfield) LIKE '%$query%'";
     		} else {
			   $query			= $this->EscapeStr(strtolower($query));
     			$searchclause	= "LOWER($searchfield) LIKE '$query'";
			}
  		}
		return $searchclause;
 	} else {
		//MYSQL PART
		$reference 		= $this->GetParam($replace[$searchfield],"");
		$unreferenced	= rand(10000,99999).time();
		$fieldtype 		= $this->GetParam($fieldinfo[$searchfield][0],"string");
		$query			= $query;

		if (eregi("=",$query)) {
		   $query = $query;
		} elseif (eregi("\*",$query) == false) {
			$query = "*$query*";
		}

		if ($this->GetInputType($fieldtype,0)=='numeric') {
			if ($this->dbType() == 'MSSQL') {
				$query = ($reference != "") ? $query : (int)$query;
			}
		}
		if (eregi("=",urldecode($query))) {
			$query = substr($query,1);
			$searchclause = "$searchfield = '$query'";
		} elseif (eregi("\*",$query)) {
			$keys = array();
			if ($reference!= "") {
				foreach($reference as $key => $value) {
					if ($this->InStr($query,$value) > -1) {
						array_push($keys,$key);
					}
				}
				if (count($keys)!=0) {
					$values = "'".join("','",$keys)."'";
					$searchclause = "$searchfield IN ($values)";
				} else {
					$searchclause = "$searchfield = '$unreferenced'";
				}
			} else {
				$query = str_replace("*","%",$query);
				$searchclause = "$searchfield like '".$this->EscapeStr($query)."'";
			}
		} else {
			$charstart = substr($query,0,1);
			if ($reference != "") {
				$lookup = $unreferenced; // '__UNKNOWN_REFERENCE__';
				if ($charstart != '=') {
					$keys = array();
					foreach($reference as $key => $value) {
						if (strtolower($value) == $query) {
							$lookup = $key;
						} else {
							array_push($keys,$key);
						}
					}
					$values = "'".join("','",$keys)."'";
				} else {
					$lookup = $this->StripWrapper($query,'=');
				}
				if (strtoupper($lookup) == '<UNKNOWN>') {
					$query = 0;
					$searchclause = "$searchfield NOT IN ($values)";
				} else {
					$searchclause = "$searchfield='".$this->EscapeStr($lookup)."'";
				}
			} else {
				$searchclause = "$searchfield='".$this->EscapeStr($query)."'";
			}
		}

		//this fixes mysql's faulty result when querying numeric field with a string value
		if ($query != '0') {
			return "$searchclause and $searchfield != '0' ";
		} else {
			return "$searchclause";
		}
 	}
}

function BuildSearchSql($sql,$replace,$fieldinfo,$sqlparse) {
	$searchfield 	= $this->GetVar("searchfield");
	$query			= stripslashes(urldecode($this->GetVar("query")));
	if ($searchfield == "") {
		return $sql;
	} else {
	   if ($sqlparse == false) {
	  		return $sql;
   	} else {
   	   if (!eregi(" group by ",$sql)) {
				$where_conj = (eregi(" where ",$sql)) ? " and " : " where ";
				$searchclause = $this->BuildSearchClause($searchfield,$query,$replace,$fieldinfo);
				$sql = "$sql $where_conj $searchclause ";
				return $sql;
       	} else {
       		$where_conj = (eregi(" having ",$sql)) ? " and " : " having ";
				$searchclause = $this->BuildSearchClause($searchfield,$query,$replace,$fieldinfo);
				$sql = "$sql $where_conj $searchclause ";
				return $sql;
			}
    }
	}
}

function LookupValue($replace,$fieldname,$fieldvalue) {
	$value = $this->GetParam($replace[$fieldname][$fieldvalue]);
	if ($value == '') {
		$value = $this->GetParam($replace[$fieldname]['<UNKNOWN>']);
	}
	return $value;
}

function CheckFieldConfig($cfg) {
	$result = array();
	while (list($key,$value)=each($cfg)) {
		if (is_array($value)) {
			//$value = $this->CheckFieldConfig($value);
		}
		$result[strtolower($key)] = $value;
	}
	return $result;
}

function BuildAdminBar($adminbar,$disableadd,$disablesearch,$disablepagelist) {
	if ($disableadd == true) {
		$adminbar = str_replace("%%ADD%%","",$adminbar);
	}
	if ($disablesearch == true) {
		$adminbar = str_replace("%%SEARCHBAR%%","",$adminbar);
	}
	if ($disablepagelist == true) {
		$adminbar = str_replace("%%PAGELIST%%","",$adminbar);
	}
	return $adminbar;
}

function CountAllRecords($sql) {
	preg_match("/^select(.*?)from/i",trim($sql),$match);
	$fields 	= $match[0];
	$orisql	= $sql;
	$sql 		= str_replace($fields,"select COUNT(*) AS NUMRECS from",$sql);

	if (eregi(" order by ",$sql)) {
		$orderclause = strstr($sql," order by ");
		if ($orderclause != '') {
			$sql = str_replace($orderclause,'',$sql);
		}
	}

	$num = $this->GetValue($sql,"NUMRECS");
	if ($num !='') {
		$this->numrecs = $num;
	} else {
	   $rs = $this->RunSql($orisql);
 	}
}

function ShowMessage($showmessage=true) {
	if ($showmessage == true) {
		@list($ptitle,$PMSG)		= split('@@@@',trim(urldecode(@$this->Decrypt($this->GetVar("PMSG")))));
		if ($PMSG != '') $PMSG 	= '<div name=pmsgbox id=pmsgbox>'.$this->MessageBox($ptitle,$PMSG,"<a href=# onClick=\"document.getElementById('pmsgbox').style.display='none';\">REMOVE THIS MESSAGE BOX</a></a>").'</div><br><br>';
		return $PMSG;
 	}
}

function ProcessContent($list) {
	$list						= $this->X($list);
	$sql						= $this->GetParam($list["sql"]);
	$layout					= $this->GetParam($list["layout"],"");
	$showmessage			= $this->GetParam($list["showmessage"],true);
	$PMSG						= $this->ShowMessage($showmessage);

	if ($sql == "_none_") return $layout;

	$printsql				= $this->GetParam($list["printsql"]);
	$keyvarname				= $this->GetParam($list["keyvarname"],"keyid");
	$adminpage				= $this->GetParam($list["adminpage"],false);
	$showactions			= $this->GetParam($list["showactions"],true);
	$moreactions			= $this->GetParam($list["moreactions"],"");
	$moreaddvars			= $this->GetParam($list["moreaddvars"],"");
	$moreadminvars			= $this->GetParam($list["moreadminvars"],"");
	$disableadd				= $this->GetParam($list["disableadd"],false);
	$disablesearch			= $this->GetParam($list["disablesearch"],false);
	$disablepagelist		= $this->GetParam($list["disablepagelist"],false);
	$disableedit			= $this->GetParam($list["disableedit"],false);
	$disabledelete			= $this->GetParam($list["disabledelete"],false);
	$disableview			= $this->GetParam($list["disableview"],false);
	$nopagelistifless		= $this->GetParam($list["nopagelistifless"],false);
	$header					= $this->GetParam($list["header"],"");
	$footer					= $this->GetParam($list["footer"],"");
	$opentag				= $this->GetParam($list["opentag"],"");
	$closetag				= $this->GetParam($list["closetag"]);
	$altcolors				= $this->GetParam($list["altcolors"],$this->GetGS("altcolors"));
	$altcolors2				= $this->GetParam($list["altcolors2"],$this->GetGS("altcolors2"));
	$alttxtcolors			= $this->GetParam($list["alttxtcolors"],$this->GetGS("alttxtcolors"));
	$alttxtcolors2			= $this->GetParam($list["alttxtcolors2"],$this->GetGS("alttxtcolors2"));
	$altattrib				= $this->GetParam($list["altattrib"],$this->GetGS("altattrib"));
	$altattrib2				= $this->GetParam($list["altattrib2"],$this->GetGS("altattrib2"));
	$dateformat				= $this->GetParam($list["dateformat"],array());
	$numericformat			= $this->GetParam($list["numericformat"],array());
	$fieldfunction			= $this->GetParam($list["fieldfunction"],array());
	$fieldfuncparam			= $this->GetParam($list["fieldfuncparam"],array());
	$allfieldsfunction		= $this->GetParam($list["allfieldsfunction"],'');
	$rowfunction			= $this->GetParam($list["rowfunction"],"");
	$rowfuncparam			= $this->GetParam($list["rowfuncparam"],"");
	$sqlparsing				= $this->GetParam($list["sqlparsing"],false);
	$replace					= $this->GetParam($list["replace"],"");
	$dbdata					= $this->GetParam($list["dbdata"],true);
	$dbfields				= $this->GetParam($list["dbfields"],array());
	$fieldorder				= $this->GetParam($list["fieldorder"],$this->GetGS("fieldorder"));
	$showfields				= $this->GetParam($list["fields"],array());
	$nosortfields			= $this->GetParam($list["nosortfields"],array());
	$labels					= $this->GetParam($list["labels"],array());
	$arraydata				= $this->GetParam($list["arraydata"],array());
	$startvarname			= $this->GetParam($list["startvarname"],"start");
	$start					= $this->GetParam($list["start"],$this->GetVar($startvarname,0));
	$numitems				= $this->GetParam($list["numitems"],$this->GetGS("numitems",20));
	$adminbar				= $this->GetParam($list["adminbar"],$this->GetGS("adminbar"));
	$addlabel				= $this->GetParam($list["addlabel"],$this->GetGS("addlabel"));
	$editlabel				= $this->GetParam($list["editlabel"],$this->GetGS("editlabel"));
	$deletelabel			= $this->GetParam($list["deletelabel"],$this->GetGS("deletelabel"));
	$viewlabel				= $this->GetParam($list["viewlabel"],$this->GetGS("viewlabel"));
	$tblattrib				= $this->GetParam($list["tblattrib"],$this->GetGS("tblattrib"));
	$trattrib				= $this->GetParam($list["trattrib"],"");
	$tdlayout				= $this->GetParam($list["tdlayout"],array());
	$tdwidth					= $this->GetParam($list["tdwidth"],array());
	$tdheadalign			= $this->GetParam($list["tdheadalign"],array());
	$tdalign					= $this->GetParam($list["tdalign"],array());
	$tdsortlayout			= $this->GetParam($list["tdsortlayout"],array());
	$tdheadrow				= $this->GetParam($list["tdheadrow"],"");
	$tdheadsort				= $this->GetParam($list["tdheadsort"],array());
	$tdheadnosort			= $this->GetParam($list["tdheadnosort"],array());
	$tdheadlayout			= $this->GetParam($list["tdheadlayout"],array());
	$tdheadnumbering		= $this->GetParam($list["tdheadnumbering"],$this->GetGS("tdheadnumbering"));
	$tdheadaction			= $this->GetParam($list["tdheadaction"],$this->GetGS("tdheadaction"));
	$tdaction				= $this->GetParam($list["tdaction"],"");
	$tdnumbering			= $this->GetParam($list["tdnumbering"],"");
	$columnar				= $this->GetParam($list["columnar"],false);
	$numcolumns				= $this->GetParam($list["numcolumns"],1);
	$emptycell				= $this->GetParam($list["emptycell"],"");
	$verticalorder			= $this->GetParam($list["verticalorder"],true);
	$disablesort			= $this->GetParam($list["disablesort"],!$adminpage);
	$groupby					= $this->GetParam($list["groupby"],"");
	$grouphead				= $this->GetParam($list["grouphead"],"");
	$userowspan				= $this->GetParam($list["userowspan"],true);
	$initialsort			= $this->GetParam($list["initialsort"],"");
	$initialorder			= $this->GetParam($list["initialorder"],$this->GetGS("initialorder","asc"));
	$numbering				= $this->GetParam($list["numbering"],true);
	$pagingstylename		= $this->GetParam($list["pagingstylename"],1);
	$pagingmaxlinks		= $this->GetParam($list["pagingmaxlinks"],$this->GetGS($pagingstylename."_maxlinks"));
	$pagingmaxstart		= $this->GetParam($list["pagingmaxstart"],2000000);
	$pagingnorecordstr	= $this->GetParam($list["pagingnorecordstr"],"");
	$norecordstr			= $this->GetParam($list["norecordstr"],"");
	$horizontalmenu		= $this->GetParam($list["horizontalmenu"],$this->GetGS("horizontalmenu",false));
	$sqlparse				= $this->GetParam($list["sqlparse"],true);
	$totalrecords			= $this->GetParam($list["totalrecords"],false);
	$autosubstr				= $this->GetParam($list["autosubstr"],$adminpage);
	$innersql				= $this->GetParam($list["innersql"],"");
	$innerprocess			= $this->GetParam($list["innerprocess"],false);
	$contentwrapper		= $this->GetParam($list["contentwrapper"],$this->GetGS("contentwrapper"));
	$picturetable			= $this->GetParam($list["picturetable"],$this->GetGS("tbl_picture"));
   $publishdatefield    = $this->GetParam($list["publishdatefield"],"published");
	$newrange				= $this->GetParam($list["newrange"],7);
	$recordname				= $this->GetParam($list["recordname"],"records");
	$newtag					= $this->GetParam($list["newtag"]," <font style='font-size:8pt; color:#a7a7a7;'>NEW </font>");
	$sql						= $this->CompactMultiLine($sql);
	$tablename				= $this->GetTablename($sql);
	$actionlabels			= array($editlabel,$deletelabel,$viewlabel);
	$instanceid				= time();
	$lastgroupvalue		= "";
	$groupvalue				= "";

	$this->adminpage		= $adminpage;
	$this->showactions	= $showactions;
	$this->groupspan		= array();
	$this->lastvalue		= time();
	$this->idemcount		= 1;
	$this->groupno			= 0;
	$this->lastgroupno	= 0;
	$this->maxrows			= ceil($numitems / $numcolumns);

	if ($innerprocess == true) $start = $this->GetParam($list["start"],0);
	if ($dbdata == false) $this->showactions = false;
	if ($groupby != "" && $columnar == true && $verticalorder == false) $groupby = "";
	if ($columnar == true && $numcolumns == 1) $columnar = false;
	if ($columnar == true && $numcolumns > 1)  $grouphead = "";
	if (($layout != "") && count($showfields)==0 && $opentag == "") $opentag = $this->BuildOpenTagFromLayout($layout,$tblattrib);

	if (eregi("%%SORTLINK_",$opentag)) $opentag = $this->BuildOpenTagSortlink($opentag);

	if ($start > $pagingmaxstart) $start = 0;

	if ($dbdata == true) {
		$tablequery		= ($sqlparse == false) ? $sql : $tablename;
		$rs				= $this->RunSql($this->SelectTableInfo($tablequery));
		$fieldinfo		= $this->GetFieldInfo($rs);
		$fields			= array_merge($this->GetFieldNames($fieldinfo),$dbfields);
		$showfields		= (count($showfields) == 0) ? $fields : $showfields;
		$showfields		= $this->SetFieldOrder($showfields,$fieldorder,$numbering,$adminpage);
		$showfields		= $this->CheckDefaultFields($showfields,$adminpage,$showactions,$numbering);
		$primarykey		= $this->GetParam($list["primarykey"],$this->GetParam($fields[0]));
		$primarykey2	= $this->GetParam($list["primarykey2"]);
		$sql				= $this->BuildSearchSql($sql,$replace,$fieldinfo,$sqlparse);
		$sql				= ($sqlparse == false) ? $sql : $this->BuildAdminSql($sql,$primarykey,$initialsort,$initialorder,$fields,$showfields);
		$arrayrs			= $this->SelectLimit($sql,$start,$numitems);
		$rs				= $this->CountAllRecords($sql);
		$numrecs			= $this->numrecs;
	} else {
		$primarykey		= "";
		$primarykey2	= "";
		$arrayrs			= $this->SelectArrayLimit($arraydata,$start,$numitems);
		$numrecs			= count($arraydata);
		$fields			= @$this->GetArrayFieldNames($arraydata[0]);
	}

	if ($totalrecords != false) {
		$numrecs			= $totalrecords;
	}

	if ($numrecs < $numitems) {
		$this->maxrows = ceil($numrecs / $numcolumns);
	}

	print $this->PrintSql($sql,'show',$printsql);

	if ($norecordstr !="" && $numrecs == 0) return $norecordstr;

	if ($disablesort == true) $nosortfields = $fields;

	$searchfields	= $this->GetParam($list["searchfields"],$fields);
	$searchparams	= $this->GetParam($list["searchparams"],array());
	$referrer		= urlencode($this->GetURLVars(array("act","ts","PMSG")));
	$defaction		= $this->BuildAction($primarykey,$keyvarname,$moreadminvars,$moreactions,$disableadd,$disableedit,$disabledelete,$disableview,$horizontalmenu,$referrer,$actionlabels,$primarykey2);
	$pagelist		= $this->GetPagelist($numrecs,$numitems,$start,'',$startvarname,$pagingnorecordstr,$pagingstylename,$pagingmaxstart,$pagingmaxlinks,$recordname,$nopagelistifless);
	$layout			= $this->BuildLayout($layout,$showfields,$trattrib,$tdnumbering,$tdaction,$tdlayout,$tdsortlayout,$tdwidth,$tdalign,$primarykey,$columnar,$groupby,$initialsort);
	$opentag			= $this->BuildOpenTag($opentag,$layout,$showfields,$nosortfields,$tdheadnumbering,$tdheadaction,$tdheadlayout,$tdheadsort,$tdheadnosort,$labels,$tblattrib,$columnar,$numcolumns,$primarykey,$initialsort,$tdheadrow,$tdheadalign);
	$header			= $this->BuildHeader($header,$this->BuildAdminBar($adminbar,$disableadd,$disablesearch,$disablepagelist));
	$searchbar		= $this->GetGS("searchbar");
	$searchbox		= $this->BuildSearchBox($searchfields,$labels,$tablename,$searchparams);
	$emptycell		= ($emptycell == "") ? $this->CleanUpTag($layout) : $emptycell;
	$layout			= str_replace("%%DEFAULT_ACTION%%",$defaction,$layout);
	$keyvalues		= array();
	$content			= "$PMSG";
	$no				= 0;
	$altno			= 0;
	$altno2			= 0;
	$alttxtno		= 0;
	$alttxtno2		= 0;
	$lastaltno2		= 0;
	$altattribno	= 0;
	$altattribno2	= 0;
	$maxaltno		= count($altcolors)-1;
	$maxaltno2		= count($altcolors2)-1;
	$maxalttxtno	= count($alttxtcolors)-1;
	$maxalttxtno2	= count($alttxtcolors2)-1;
	$maxaltattrib	= count($altattrib)-1;
	$maxaltattrib2	= count($altattrib2)-1;
	$numfields		= count($fields);
	$contents		= array();
	$innersqlstr	= $this->GetParam($innersql["sql"],"");
	$clrchanged		= false;

	if (eregi("%%__PROCESSCONTENT__%%",$contentwrapper)) {
		list($o1,$o2)	= split("%%__PROCESSCONTENT__%%",$contentwrapper);
 	} else {
 		$o1 = $o2 = "";
	}

	$content	.= "$header\n$o1\n$opentag\n";
	foreach ($arrayrs as $rows) {
		$no++;
		$this->no = $no;

		$publishdate 					= "";
		for($dummyno=0;$dummyno<21;$dummyno++) {$keyvalues["DUMMY$dummyno"] = ""; }

		$keyvalues["NO"]				= $no;
		$keyvalues["ROWNUM"]			= $no + $start;

		for ($i=0;$i<$numfields;$i++) {
			$fieldname					= $fields[$i];
			$fieldvalue					= @$rows[$fieldname];
			$publishdate            = ($fieldname == $publishdatefield) ? $fieldvalue : $publishdate;
			$fieldvalue					= ($autosubstr == false) ? $fieldvalue : $this->SmartSubstr($fieldvalue);
			$lookupvalue				= $this->LookupValue($replace,$fieldname,$fieldvalue);
			$numericformatstr			= in_array($fieldname,$numericformat);
			//$fieldvalue					= ($numericformatstr == true) ? number_format((int)$fieldvalue,'.') : $fieldvalue;
			//this fixes the 2,147,483,647 limitation of PHP number_format function
			$fieldvalue					= ($numericformatstr == true) ? $this->MyNumberFormat($fieldvalue) : $fieldvalue;
			$formatdatestr				= $this->GetParam($dateformat[$fieldname]);
			$fieldvalue					= ($formatdatestr != "") ? $this->DateFormat($fieldvalue,$formatdatestr,$this->datelang) : $fieldvalue;
			$fieldvalue					= ($lookupvalue == '') ? $fieldvalue : $lookupvalue;
			$keyvalues[$fieldname]	= $fieldvalue;

			if ($fieldname == $groupby) {
				$groupfieldvalue = $fieldvalue;
			}
		}
		if ($allfieldsfunction != '') {
			$keyvalues = call_user_func($allfieldsfunction,$keyvalues);
  		}

		if (count($fieldfunction) > 0) {
			reset($fieldfunction);
			while (list($field,$function) = each($fieldfunction)) {
			if (array_key_exists($field,$keyvalues)) {
			if (eregi("@",$function)) {
				list($funct,$class) = split("@",$function);
				$keyvalues[$field] = call_user_func(array($class,$funct),$keyvalues);
			} else {
				$keyvalues[$field] = call_user_func($function,$keyvalues,$fieldfuncparam);
			}
  			}
			} //while
		} //fieldfunction

		if ($groupby != '') {
			$keyvalues[$groupby]	= $this->CheckGroupValue(stripslashes($keyvalues[$groupby]),$groupby,$groupby,$userowspan);
  		}

		if ($this->groupstart && $userowspan == false) {
			if ($lastaltno2 == $altno2) {
				$altno2++;
				$lastaltno2 = $altno2;
			} else {
			   $altno2 = $lastaltno2;
			}
			if ($altno2 > $maxaltno2) {
			   $altno2 = 0; $lastaltno2 = 0;
   		}
		}

		$keyvalues["URLPRIMARYKEY"]= urlencode($this->GetParam($rows[$primarykey]));
		$keyvalues["URLPRIMARYKEY2"]=urlencode($this->GetParam($rows[$primarykey2]));
		$keyvalues["PRIMARYKEY"]	= $this->GetParam($rows[$primarykey]);
		$keyvalues["ALTCOLOR"]		= $altcolors[$altno];
		$keyvalues["ALTCOLOR2"]		= $altcolors2[$altno2];
		$keyvalues["ALTTXTCOLOR"]	= $alttxtcolors[$alttxtno];
		$keyvalues["ALTTXTCOLOR2"]	= $alttxtcolors2[$alttxtno2];
		$keyvalues["ALTATTRIB"]		= $altattrib[$altattribno];
		$keyvalues["ALTATTRIB2"]	= $altattrib2[$altattribno2];
		$keyvalues["GROUPNO"]		= $this->groupno;
		$keyvalues["GROUPROWNUM"]	= $this->idemcount;
		$keyvalues["ICONNEW"]      = $this->GetNewIcon($publishdate,$newrange,$newtag);

		if (eregi("RELATEDTHUMB",$layout)) {
			$keyvalues = $this->GetRelatedImageKeyvalues("thumb",$keyvalues,$layout,$primarykey,$tablename,$picturetable);
		}

		if (eregi("RELATEDPICTURE",$layout)) {
			$keyvalues = $this->GetRelatedImageKeyvalues("picture",$keyvalues,$layout,$primarykey,$tablename,$picturetable);
		}

		if (eregi("RELATEDALLPICTURES",$layout)) {
			$keyvalues = $this->GetRelatedAllImagesKeyvalues("PICTURES",$keyvalues,$layout,$primarykey,$tablename,$picturetable);
		}

		if (eregi("RELATEDALLTHUMBS",$layout)) {
			$keyvalues = $this->GetRelatedAllImagesKeyvalues("THUMBS",$keyvalues,$layout,$primarykey,$tablename,$picturetable);
		}

		if (eregi("RELATEDFILE",$layout)) {
			$keyvalues = $this->GetRelatedFileKeyvalues($keyvalues,$layout,$primarykey,$tablename);
		}

		if (eregi("%%INNERSQL%%",$layout)) {
			$innersql["sql"]				= $this->Tag2Values($innersqlstr,$keyvalues);
			$innersql["innerprocess"]	= true;
			$keyvalues["INNERSQL"]		= $this->ProcessContent($innersql);
		}

		$prependlayout = $this->PrependLayout($layout,$groupby,$grouphead);
		$contents[$no] = $this->RowLayout($keyvalues,$prependlayout,$no,$start,$rowfunction,$rowfuncparam);

		$altno++;
		$alttxtno++;
		$altattribno++;

		if ($userowspan) {
			if ($this->groupstart) {
		   	$altno2++;
			   $alttxtno2++;
				$altattribno2++;
			}
  		}

		if ($altno > $maxaltno) $altno = 0;
		if ($altno2 > $maxaltno2) $altno2 = 0;
		if ($alttxtno > $maxalttxtno) $alttxtno = 0;
		if ($alttxtno2 > $maxalttxtno2) $alttxtno2 = 0;
		if ($altattribno > $maxaltattrib) $altattribno = 0;
		if ($altattribno2 > $maxaltattrib2) $altattribno2 = 0;

	} //foreach

	if ($groupby != "") {
		$contents = $this->BuildRowspannedContents($contents,$groupby);
	}

	$closetag			= $this->BuildCloseTag($opentag,$closetag);
	$content				.= $this->BuildContent($contents,$columnar,$numcolumns,$emptycell,$layout,$verticalorder);
	$content				.= "$closetag$o2$footer";
	$content				= preg_replace(array("|%%SEARCHBAR%%|","|%%SEARCHBOX%%|","|%%ADD%%|","|%%MOREADDVARS%%|","|%%RECORDCOUNT%%|","|%%PAGELIST%%|"),array($searchbar,$searchbox,$addlabel,$moreaddvars,$numrecs,$pagelist),$content);
	$content				= preg_replace(array("|(<START_(.*?)>)|","|(<IDEM>)|"),"",$content);
	$this->countrecs	= $numrecs;
	return $this->ReplaceColorTags($content);
}

function GetNewIcon($d,$newrange,$newtag) {
	if ($d != '') {
		$d					= @strtotime($d);
		$currentdate	= mktime(0,0,0,@date("m",$d),@date("d",$d),@date("Y",$d));
		$lastdate 		= mktime(0,0,0,@date("m"),@date("d")-$newrange, @date("Y"));
		if ($currentdate >= $lastdate) {
		  return $newtag;
		} else {
		  return '';
		}
	} else {
	  return '';
	}
}

function GetTablename($sql) {
	preg_match("|select(.*?)from(.+?)(\b.+?\b)|i",$sql,$match);
	$tablename = @$match[3];
	return $tablename;
}

function CompactMultiLine($str) {
	$str = preg_replace("(\r|\n|\t)"," ",$str);
	return $str;
}

function ExtraSpaceRemove($str) {
	$str		= preg_replace("(\r|\n|\t)"," ",$str);
	$array	= split(" ",$str);
	$newstr	= "";
	foreach($array as $s) {
		if (trim($s) != "") {
			$newstr.= " ".trim($s);
		}
	}
	return trim($newstr);
}

function GetRelatedAllImagesKeyvalues($type,$keyvalues,$layout,$primarykey,$tablename,$picturetable) {
	$keyid				= $this->GetParam($keyvalues[$primarykey]);
	$imagetype			= ($type == 'THUMBS') ? "thumb" : "picture";
	preg_match_all("|%%(RELATEDALL$type(.*?))%%|",$layout,$match);
	$numtags   = count($match[0]);

	for($i=0;$i<$numtags;$i++) {
		$tag 			= $match[1][$i];
		$parameters	= $match[2][$i];
		$field		= $this->GetAttribParam("field",$parameters);
		$notfield	= $this->GetAttribParam("notfield",$parameters);
		$start		= $this->GetAttribParam("start",$parameters,0);
		$numpics		= $this->GetAttribParam("numpics",$parameters,100000);

		if ($field != '') {
			$sql		= "select count(*) as NumImages from $picturetable where fieldname='$field' and keyid='$keyid' and tablename='$tablename'";
  		} else {
  			$sql		= "select count(*) as NumImages from $picturetable where keyid='$keyid' and tablename='$tablename'";
		}

		if ($notfield != '') {
  			$sql		= "select count(*) as NumImages from $picturetable where (keyid='$keyid' and tablename='$tablename') and fieldname != '$notfield'";
		}

		$numImages 	= $this->GetValue($sql,"NumImages");
		$alltags	  	= '';

		$endImage	= $start + $numpics;
		if (($start + $numpics) > $numImages) {
			$endImage= $numImages;
		}

		for($j=$start;$j<$endImage;$j++) {
			list($imgtag,$t,$desc) = $this->GetImage($imagetype,$parameters,$keyid,$tablename,$j,$picturetable);
			$alltags .= $imgtag;
		}
		$keyvalues[$tag] = "$alltags";
	}

	return $keyvalues;
}

function GetRelatedImageKeyvalues($type,$keyvalues,$layout,$primarykey,$tablename,$picturetable) {
	$relatedtype		= ($type == "thumb") ? "THUMB" : "PICTURE";
	$keyid				= $this->GetParam($keyvalues[$primarykey]);
	$itag					= "RELATEDPIC";
	$picorders			= array();

	preg_match_all("|%%(RELATED$relatedtype(.*?))%%|",$layout,$match);
	$descno				= 0;
	for ($i=0;$i<count($match[0]);$i++) {
		$tag							= $match[1][$i];
		$parameters					= $match[2][$i];
		$field						= $this->GetAttribParam("field",$parameters);
		$picorders[$field]		= $this->GetParam($picorders[$field],0);
		$picorder					= $picorders[$field];
		list($imgtag,$t,$desc)	= $this->GetImage(strtolower($type),$parameters,$keyid,$tablename,$picorder,$picturetable);
		$keyvalues[$tag]			= $imgtag;
		$descno						= ($descno == 0) ? 1 : $i + 1;
		$keyvalues[$itag."TITLE"]					= $t;
		$keyvalues[$itag."TITLE$descno"]			= $t;
		$keyvalues[$itag."DESCRIPTION"]			= $desc;
		$keyvalues[$itag."DESCRIPTION$descno"]	= $desc;
		$picorders[$field]++;
	}
	return $keyvalues;
}

function GetRelatedFileKeyvalues($keyvalues,$layout,$primarykey,$tablename) {
	$keyid			= $this->GetParam($keyvalues[$primarykey]);
	$fileorders		= array();
	preg_match_all("|%%(RELATEDFILE(.*?))%%|",$layout,$match);
	$descno			= 0;

	for ($i=0;$i<count($match[0]);$i++) {
		$tag					= $match[1][$i];
		$parameters			= $match[2][$i];
		$field				= $this->GetAttribParam("field",$parameters);
		$fileorders[$field] = $this->GetParam($fileorders[$field],0);
		$fileorder			= $fileorders[$field];
		list($filetag,$title,$description) = $this->GetFile($parameters,$keyid,$tablename,$fileorder);
		$keyvalues[$tag]	= $filetag;
		$descno				= ($descno == 0) ? 1 : $i + 1;
		$keyvalues["RELATEDFILETITLE"]					= $title;
		$keyvalues["RELATEDFILETITLE$descno"]			= $title;
		$keyvalues["RELATEDFILEDESCRIPTION"]			= $description;
		$keyvalues["RELATEDFILEDESCRIPTION$descno"]	= $description;
		$fileorders[$field]++;
	}
	return $keyvalues;
}

function GetFile($layout,$keyid,$tablename,$fileorder=0) {
	$fieldparam			= $this->GetAttribParam("field",$layout,"");
	$opentag				= $this->GetAttribParam("opentag",$layout,"",true);
	$closetag			= $this->GetAttribParam("closetag",$layout,"",true);
	$target				= $this->GetAttribParam("target",$layout,"_new");
	$uploadtable		= $this->GetAttribParam("uploadtable",$layout,$this->GetGS("tbl_upload","cms_upload"));
	$sortorder			= $this->GetAttribParam("sortorder",$layout,"asc");
	$fileorderparam	= $this->GetAttribParam("fileorder",$layout,$fileorder);
	$fileorder			= ($fileorderparam == -1) ? $fileorder : $fileorderparam;

	if ($fieldparam == '') {
		$sql = "select path,filename,title,description from $uploadtable where tablename='$tablename' and keyid='$keyid' order by id $sortorder";
	} else {
		$sql = "select path,filename,title,description from $uploadtable where tablename='$tablename' and LOWER(fieldname)='".strtolower($fieldparam)."' and keyid='$keyid' order by id $sortorder";
	}
	$arfields		= array("title","description","path","filename");
	$rows				= $this->GetArrayValues($sql,$arfields,$fileorder);
	list($title,$description,$path,$filename) = $rows;

	if ($filename != '') {
		$target		= ($target == "") ? "" : "target=$target";
		$filetag		= "$opentag<a href=/$path/$filename $target>$title</a>$closetag";
		return array($filetag,$title,$description);
	} else {
		return array("","","");
	}
}

function GetImage($type,$layout,$keyid,$tablename,$picorder=0,$picturetable) {
	global $DOCUMENT_ROOT;
	$fieldparam		= $this->GetAttribParam("field",$layout,"");
	$notfieldparam	= $this->GetAttribParam("notfield",$layout,"");
	$link				= $this->GetAttribParam("link",$layout,"");
	$linkparam		= $this->GetAttribParam("linkparam",$layout,"");
	$target			= $this->GetAttribParam("target",$layout,"_new");
	$sortorder		= $this->GetAttribParam("sortorder",$layout,"asc");
	$alt				= $this->GetAttribParam("alt",$layout,"",true);
	$picturetable	= $this->GetAttribParam("picturetable",$layout,$picturetable);
	$maxdimension	= $this->GetAttribParam("maxdimension",$layout);
	$mindimension	= $this->GetAttribParam("mindimension",$layout);
	$div				= $this->GetAttribParam("div",$layout);
	$showcaption	= $this->GetAttribParam("showcaption",$layout);
	$captionstyle	= $this->GetAttribParam("captionstyle",$layout);
	$picorderparam	= $this->GetAttribParam("picorder",$layout,$picorder);
	$centertable	= $this->GetAttribParam("centertable",$layout,0);
	$bgcolor			= $this->GetAttribParam("bgcolor",$layout,"white");
	$commentno		= $this->GetAttribParam("commentno",$layout,0);
	$start			= $this->GetAttribParam("start",$layout,0);
	$useonclick		= $this->GetAttribParam("useonclick",$layout,"true");
	$picorder		= ($picorderparam == -1) ? $picorder : $picorderparam;
	$path				= ($type == "thumb") ? "thumbpath" : "path";
	$filename		= ($type == "thumb") ? "thumbname" : "filename";

	if ($fieldparam == '') {
		$sql = "select $path,$filename,path,filename,title,description,width,height,id from $picturetable where tablename='$tablename' and keyid='$keyid' order by picorder $sortorder";
	} else {
		$sql = "select $path,$filename,path,filename,title,description,width,height,id from $picturetable where tablename='$tablename' and fieldname='$fieldparam' and keyid='$keyid' order by picorder $sortorder";
	}

	if ($notfieldparam != '') {
		$sql = "select $path,$filename,path,filename,title,description,width,height,id from $picturetable where tablename='$tablename' and fieldname!='$fieldparam' and keyid='$keyid' order by picorder $sortorder";
	}

	$arfields		= array($path,$filename,"title","description","path","filename","width","height","id");
	$rows				= $this->GetArrayValues($sql,$arfields,$picorder);
	list($path,$filename,$title,$description,$imgpath,$imgname,$width,$height,$picid) = $rows;

	if (eregi('@@',$description)) {
	$descriptions = split('@@',$description);
	$description = $descriptions[$commentno];
 	}

	if (($width < $maxdimension) && ($height < $maxdimension)) {
		$maxdimension = $width;
	}

	if ($filename != '' && file_exists("$DOCUMENT_ROOT/$path/$filename")) {
		$size_attrib	= ($maxdimension == "") ? "" : $this->MaxDimension($maxdimension,$width,$height,"$path/$filename",$mindimension);
		$altinfo			= ($alt == "title") ? "alt=\"$title\"" : "$alt";
		$altinfo			= ($alt == "description") ? "alt=\"$description\"" : "$alt";
	   $link				= str_replace("__E__","=",$link);
	   $link				= str_replace("__S__","/",$link);
	   $link				= str_replace("__D__",".",$link);
		if ($link == '') {
			$imagetag = "<img $size_attrib $altinfo $layout src=\"/$path/$filename\">";
		} else {
		   $linkparam	= str_replace("__E__","=",$linkparam);
		   $linkparam	= str_replace("__S__","/",$linkparam);
			$link			= ($link == "original") ? "" : "$link?$linkparam"."&picid=$picid&picfile=";
			$target		= ($target == "") ? "" : "target=$target";
			if ($useonclick != "true") {
			$imagetag	= "<a href=\"$link/$imgpath/$imgname\" $target><img $size_attrib $altinfo $layout src=\"/$path/$filename\"></a>";
   		} else {
   		$winwidth	= $width + 30;
			$winheight	= $height + 30;
   		$imagetag   = "<a href=javascript:void(0) onClick=\"newWindow=window.open('$link/$imgpath/$imgname','fullwindow','resizable,menubar=NO,left=10,top=10,height=$winheight,width=$winwidth,scrollbars=yes');newWindow.focus();\"><img $size_attrib $altinfo $layout src=\"/$path/$filename\"></a>";
			}
		}

		if ($showcaption=='true') {
			$caption = $description;
		} else {
			$caption = '';
		}

		if ($captionstyle != '') {
			$span = '<div class='.$captionstyle.'>';
			$endspan = '</div>';
		} else {
			$span = $endspan = '';
		}

		$imagetag = "$imagetag$span$caption$endspan";

		if ($centertable != '0') {
			$imagetag	= "<table width=$centertable height=$centertable><tr><td bgcolor=\"$bgcolor\" align=center valign=middle>$imagetag</td></tr></table>";
		}

		if ($div != '') {
			$imagetag = "<div class=$div>$imagetag</div>";
		}

		$result = array($imagetag,$title,$description);
	} else {
		$result = array("","","");
	}
	return $result;
}

function MaxDimension($maxdimension,$width,$height,$thumbfile,$mindimension='') {
	if ($width == 0 && $height == 0) {
		list ($width,$height) = getimagesize($this->root()."/$thumbfile");
	}
	$source_max		= ($width >= $height) ? $width : $height;
	$target_max		= $maxdimension;
	$proportion		= $target_max / $source_max;
	$new_w			= ceil($proportion * $width);
	$new_h			= ceil($proportion * $height);

	if ($mindimension != '' && $mindimension != '0') {
		if ($new_w > $new_h) {
			$new_h = $mindimension;
		} else {
			$new_w = $mindimension;
		}
	}
	return "width=$new_w height=$new_h";
}

function GetAttribParam($tag,$layout,$defvalue='',$sentence=false) {
	if ($sentence == false) {
		preg_match("|$tag=(\b(.+?)\b)|",$layout,$match);
	} else {
		preg_match("|$tag=\[(.*?)\]|",$layout,$match);
	}
	$param = $this->GetParam($match[1]);
	return ($param == '') ? $defvalue : $param;
}

function BuildRowspannedContents($contents,$groupby) {
	$numcontents	= count($contents);
	$result			= array();
	for($i=1;$i<=$numcontents;$i++) {
		$line = $contents[$i];
		$line = $this->BuildRowspanLine($line,$groupby);
		$result[$i] = $line;
	}
	return $result;
}

function BuildRowspanLine($line,$groupby) {
	$spanconfig  = $this->groupspan;
	if (eregi('<START_',$line)) {
		preg_match_all("|<(START_(.*?))>|",$line,$match);
		$groupno = $this->GetParam($match[2][0]);
		$rowspan = (int)$this->GetParam($spanconfig[$groupno]);
		$rowspan = ($rowspan == 0) ? 1 : $rowspan;
		$line = str_replace("%%ROWSPAN_$groupby%%","rowspan=\"$rowspan\"",$line);
		return $line;
	}
	if (eregi('<IDEM>',$line)) {
		preg_match_all("|(<td(.*?)%%ROWSPAN_$groupby%%(.*?)(<IDEM>)(.*?)</td>)|",$line,$match);
		$find = $this->GetParam($match[0][0]);
		$line = str_replace($find,"",$line);
		return $line;
	}
	return $line;
}

function PrependLayout($layout,$groupby,$grouphead) {
	if ($groupby != "") {
		if ($this->idemcount == 1) {
		return "$grouphead$layout";
		}
	}
	return $layout;
}

function CheckGroupValue($fieldvalue,$fieldname,$groupby,$userowspan) {
	if ($groupby == "") return $fieldvalue;

	if ($fieldname == $groupby) {
		$no			= $this->no;
		$maxrows		= $this->maxrows;
		$mod			= ($no % $maxrows);
		$lastvalue	= $this->lastvalue;
		$this->lastvalue = $fieldvalue;

		if ($userowspan == false) {
			if ($lastvalue == $fieldvalue) {
				$this->groupstart = false;
				$this->idemcount++;
			} else {
				$this->groupstart = true;
				$this->groupno++;
				$this->idemcount = 1;
			}
			return $fieldvalue;
		}

		if ($mod == 1) {
			$this->groupno++;
			$groupno = $this->groupno;
			$this->idemcount = 1;
			return "$fieldvalue<START_$groupno>";
		}

		if ($lastvalue == $fieldvalue) {
			$this->idemcount++;
			$groupno = $this->groupno;
			$idemcount = $this->idemcount;
			$this->groupspan[$groupno] = $this->idemcount;
			$this->groupstart = false;
			return "<IDEM>";
		} else {
			$this->groupno++;
			$groupno = $this->groupno;
			$this->idemcount = 1;
			$this->groupstart = true;
			return "$fieldvalue<START_$groupno>";
		}
	} else {
	return $fieldvalue;
	}
}

function BuildContent($contents,$columnar,$numcolumns,$emptycell,$layout,$verticalorder) {
	if ($columnar == false) {
		$content = join('',$contents);
	} else {
		if ($verticalorder == true) {
			$content   = $this->BuildVerticalColumnarLayout($contents,$numcolumns,$layout,$emptycell);
		} else {
			$colno		= 1;
			$cellcount	= 0;
			$numcells	= count($contents);
			$emptycell	= ($emptycell == "") ? $this->CleanUpTag($layout) : $emptycell;

			if (eregi("^<td",trim($layout))) {
				$opentr = "<tr>";
				$closetr = "</tr>";
			} else {
				$opentr = "";
				$closetr = "";
			}

			$content = $opentr;
			foreach($contents as $row) {
				$content .= $row;
				if ($colno == $numcolumns) {
					$content .= $closetr;
					$content .= ($cellcount == $numcells - 1) ? "" : $opentr;
					$colno = 0;
				}
				$colno++; $cellcount++;
			}
			$numempty = $numcolumns - ($cellcount % $numcolumns);
			if ($numempty != $numcolumns) {
				for($i=0;$i<$numempty;$i++) {
					$content .= $emptycell;
				}
				$content .= $closetr;
			}
		} //verticalorder
	}
	return $content;
}

function BuildVerticalColumnarLayout($contents,$numcolumns,$layout,$emptycell='') {
	$content			= "";
	$colno			= 0;
	$numcells		= count($contents);
	$numrows			= ceil($numcells / $numcolumns);
	$cellsneeded	= ($numrows * $numcolumns) - $numcells; //number of cells needed to fit the layout
	$emptycell		= ($emptycell == '') ? $this->CleanUpTag($layout) : $emptycell;
	$numcells		= $numcells + $cellsneeded;

	if (eregi("^<td",trim($layout))) {
		$opentr = "<tr>";
		$closetr = "</tr>";
	} else {
		$opentr = "";
		$closetr = "";
	}

	for ($row=1;$row<=$numrows;$row++) {
		$content .= $opentr;
		for ($col=0;$col<$numcolumns;$col++) {
			$pos = ($col * $numrows) + $row;  $colno++;
			if ($pos <= ($numcells - $cellsneeded)) {
				$content .= $contents[$pos];
			} else {
				$content .= $emptycell;
			}
		}
		$content .= $closetr;
	}
	return $content;
}

function CleanUpTag($layout) {
	$layout = preg_replace(array("|%%NO%%.|","|%%ALTCOLOR(.*?)%%|","|%%(.*?)%%|"),array("","white","&nbsp;"),$layout);
	return $layout;
}

function RowLayout($keyvalues,$layout,$no,$start,$rowfunction='',$rowfuncparam='') {
	if ($rowfunction != '') {
		if (eregi('@',$rowfunction)) {
			list($function,$classname) = split('@',$rowfunction);
			$layout = call_user_func(array($classname,$function),$keyvalues,$layout,$rowfuncparam);
		} else {
			$layout = call_user_func($rowfunction,$keyvalues,$layout,$rowfuncparam);
		}
	}
	while(list($key,$fieldvalue)=each($keyvalues)) {
		$tag = "%%$key%%";
		$tmp = str_replace($tag,$fieldvalue,$layout);
		$layout = $tmp;
	}
	return $layout;
}

function BuildSortLink($field,$label) {
	$nosortfields = $this->nosortfields;
	if (in_array($field,$nosortfields)) {
		return $this->FormatLabel($label);
	}
	if ($this->adminpage == true) {
		$current_orderby = $this->GetVar("orderby","");
		$sorthref = $this->BuildSortHREF($field,$current_orderby);
		if ($current_orderby == $field) {
			return "<a href=?$sorthref><i>$label</i></a>";
		} else {
			return "<a href=?$sorthref>$label</a>";
		}
	}
	return $label;
}

function BuildSortHREF($field,$curorderby) {
	$nosortfields	= $this->nosortfields;
	$curorderby		= $this->GetVar("orderby","");
	$cursortorder	= $this->GetVar("sortorder","asc");
	$sortorder		= ($cursortorder == "asc") ? "desc" : "asc";
	$sortorder		= ($field != $curorderby) ? "asc" : $sortorder;
	$urlvars			= $this->GetURLVars(array("orderby","sortorder","ts"));
	$field			= urlencode($field);
	$sorthref		= "$urlvars&orderby=$field&sortorder=$sortorder";

	if (in_array($field,$nosortfields)|| (eregi("^RELATED",$field))) {
		return "$urlvars&orderby=$curorderby&sortorder=$cursortorder#";
	}
	return $sorthref;
}

function BuildHeader($header,$adminbar) {
	if ($this->adminpage == true) {
		$header .= $adminbar;
	}
	return $header;
}

function BuildSimpleOpenTag($layout) {
	return "<table cellpadding=3 cellspacing=0>";
}

function BuildOpenTag($opentag,$layout,$fields,$nosortfields=array(),$tdheadnumbering,$tdheadaction,$tdheadlayout=array(),$tdheadsort=array(),$tdheadnosort=array(),$labels,$tblattrib='',$columnar,$numcolumns,$primarykey,$initialsort,$tdheadrow='',$tdalign=array()) {
	if ($opentag != "") return $opentag;
	if (!eregi("^<tr",trim($layout)) && !eregi("^<td",trim($layout))) return "";

	$layout				= ($opentag == "") ? "\n\n<table $tblattrib>$tdheadrow<tr>\n" : "\n\n$opentag\n";
	$img_sort_up		= $this->GetGS("img_sort_up");
	$img_sort_down		= $this->GetGS("img_sort_down");
	$current_order		= $this->GetVar("sortorder","asc");
	$current_orderby	= $this->GetVar("orderby",$initialsort);

	foreach($this->nosortfields as $nosortfield) {
		array_push($nosortfields,$nosortfield);
	}

	$tdallfields		= $this->GetParam($tdheadlayout['allfields'],$this->GetGS("tdhead"));
	$tdallnosort		= $this->GetParam($tdheadnosort['allfields'],$this->GetGS("tdheadnosort"));
	$tdallactivesort	= $this->GetParam($tdheadsort['allfields'],$this->GetGS("tdheadsort"));
	$tdheadnumbering	= $this->GetParam($tdheadnosort['NO'],$this->GetParam($tdheadnumbering,$this->GetParam($tdallnosort,$this->GetGS("tdheadnumbering"))));
	$tdheadnosort["NO"] = $tdheadnumbering;
	$tdheadaction		= $this->GetParam($tdheadnosort['DEFAULT_ACTION'],$this->GetParam($tdheadaction,$this->GetParam($tdallnosort,$this->GetGS("tdheadaction"))));
	$tdheadnosort["DEFAULT_ACTION"] = $tdheadaction;
	$tdlayouts			= "";

	$t = 0;
	foreach ($fields as $field) {

		if ($field == "PRIMARYKEY") $field = $primarykey;

		$tdfield		= $this->GetParam($tdheadlayout[$field],$tdallfields);
		$tdnosort	= $this->GetParam($tdheadnosort[$field],$tdallnosort);
		$tdactive	= $this->GetParam($tdheadsort[$field],$tdallactivesort);
		$sorthref	= $this->BuildSortHREF($field,$current_orderby);
		$sortmark	= $this->BuildSortMark($field,$current_orderby,$current_order,$img_sort_up,$img_sort_down);
		$label		= $this->GetParam($labels[$field],$this->FormatLabel($field));
		$align		= @$tdalign[$t];

		if (!in_array($field,$nosortfields)) {
			if ($current_orderby == $field) {
				$tdlayout = $tdactive;
			} else {
				$tdlayout = $tdfield;
			}
		} else {
			$tdlayout = $tdnosort;
		}

		$tdlayout	= str_replace("%%SORTLINK_FIELDNAME%%",$sorthref,$tdlayout);
		$tdlayout	= preg_replace(array("|%%FIELDNAME%%|","|%%SORTMARK%%|"),array($label,$sortmark),$tdlayout);

		if (($align!='') && (!eregi(" align= ",$tdlayout))) {
			$tdlayout = str_replace("<td ","<td align=$align ",$tdlayout);
		}

		$tdlayouts	.= $tdlayout;
		$t++;
	}

	if ($columnar == true) {
		for ($c=0;$c<$numcolumns;$c++) {
			$layout .= $tdlayouts;
		}
	} else {
		$layout .= $tdlayouts;
	}
	$layout .= "</tr>\n";

	return $layout;
}

function BuildSortMark($field,$current_orderby,$current_order,$img_sort_up,$img_sort_down) {
	$img = ($current_order == "asc") ? $img_sort_up : $img_sort_down;
	if ($field == $current_orderby) {
		return "<img border=0 src=$img>";
	}
}

function BuildCloseTag($opentag,$closetag) {
	if ($closetag != '') return $closetag;
	if (eregi("<table",$opentag) && ($closetag == "")) {
		return '</table>';
	}
}

function BuildAction($primarykey,$keyvarname='keyid',$moreadminvars='',$moreactions='',$disableadd,$disableedit,$disabledelete,$disableview,$horizontalmenu,$referrer,$labels,$primarykey2='') {

	if ($this->showactions == true) {
	$break	= ($horizontalmenu) ? "" : "<br>";

	$ledit	= $labels[0];
	$ldelete	= $labels[1];
	$lview	= $labels[2];

	if ($primarykey2 != '') {
		$secondprimarykey	= $keyvarname."2=%%URLPRIMARYKEY2%%&";
 	} else {
		$secondprimarykey = '';
	}

	if ($lview == "_none_")   	{ $disableview = true; }
	if ($ledit == "_none_") 	{ $disableedit = true; }
	if ($ldelete == "_none_") 	{ $disabledelete = true; }

	$edit		= ($disableedit)   ? "" : "\t<a href=?act=edit&$keyvarname=%%URLPRIMARYKEY%%&"."$secondprimarykey$moreadminvars&referrer=$referrer><font color=%%CLR_LINK%%>$ledit</font></a> &nbsp;\n $break";
	$view		= ($disableview) 	 ? "" : "\t<a href=?act=view&$keyvarname=%%URLPRIMARYKEY%%&"."$secondprimarykey$moreadminvars&referrer=$referrer><font color=%%CLR_LINK%%>$lview</font></a> &nbsp;\n $break";
	$delete	= ($disabledelete) ? "" : "\t<a href=?act=delete&$keyvarname=%%URLPRIMARYKEY%%&"."$secondprimarykey$moreadminvars&referrer=$referrer><font color=%%CLR_LINK%%>$ldelete</font></a> &nbsp;\n $break";

	$actions = "$edit $view $delete";
	if (eregi("%%DEFAULT_ACTION%%",$moreactions)) {
		return str_replace("%%DEFAULT_ACTION%%",$actions,$moreactions);
 	} else {
		return "$actions$moreactions";
  	}
	} else {
	return "";
	}
}

function FormatLabel($label) {
	if (eregi("^RELATED",$label)) {
		$label = str_replace("RELATED","",$label);
	}
	if ($label == "DEFAULT_ACTION") $label = "ACTION";
	$label = ucwords(str_replace("_"," ",strtolower($label)));
	return $label;
}

function BuildRowspannedLayout($layout,$groupby,$columnar) {
	$rowspantag = "%%ROWSPAN_$groupby%%";
	if ($columnar == true) {
		$layout = trim($layout);
		if (eregi("^<tr",$layout)) {
			preg_match("|<tr(.*?)>(.*?)</tr>|",$layout,$match);
			$tdlayout = @$match[2];
			$layout	= $tdlayout;
		}
	}

	if (eregi($rowspantag,$layout)) {
		return $layout;
	}

	if (eregi("<td",$layout) && $groupby != "") {
		$tds			= split("<td",$layout);
		$layout		= '';
		foreach($tds as $td) {
			if (trim($td) != '') {
				if (eregi("%%$groupby%%",$td)) {
					$layout .= "<td $rowspantag $td\n";
				} else {
					$layout .= "<td$td\n";
				}
			}
		}
		return $layout;
	}
	return $layout;
}

function BuildTdWidth($td,$width,$align) {
	if ($align !='') $align = " align=$align ";
	if ($width !='') $width = " width=$width ";
	if ($td != '') {
		if (!eregi(" width=",$td)) {
			$td = str_replace("<td","<td $width $align",$td);
		}
	}
	return $td;
}

function BuildLayout($layout,$fields,$trattrib,$tdnumbering,$tdaction,$tdlayout=array(),$tdsortlayout=array(),$tdwidth=array(),$tdalign=array(),$primarykey='id',$columnar=false,$groupby='',$initialsort) {
	if ($layout != "") {
		return $this->BuildRowspannedLayout($layout,$groupby,$columnar);
	}

//	$initialsort		= ($initialsort == "") ? $primarykey : $initialsort;
	$initialsort		= ($initialsort == "") ? "" : $initialsort;
	$deflayout			= $this->GetGS("tdlayout");
	$cursortby			= $this->GetVar("orderby",$initialsort);
	$layout				= ($columnar == false) ? "<tr $trattrib>" : "";
	$tdall				= $this->GetParam($tdlayout['allfields'],$this->GetGS("tdlayout"));
	$tdsortall			= ($this->adminpage == false) ? $tdall : $this->GetParam($tdsortlayout['allfields'],$this->GetGS("tdsortlayout"));

	$tdnumberlayout	= $this->GetParam($tdlayout['NO'],$this->GetParam($tdnumbering,$this->GetParam($tdlayout["allfields"],$this->GetGS("tdnumbering"))));
	$tdlayout["NO"]	= $tdnumberlayout;
	$tdactionlayout	= $this->GetParam($tdlayout['DEFAULT_ACTION'],$this->GetParam($tdaction,$this->GetParam($tdlayout["allfields"],$this->GetGS("tdaction"))));
	$tdlayout["DEFAULT_ACTION"] = $tdactionlayout;
	$col = 0;

	foreach($fields as $field) {
		$width			= $this->GetParam($tdwidth[$col]);
		$align			= $this->GetParam($tdalign[$col]);
		$td				= $this->GetParam($tdlayout[$field]);
		$tdsort			= $this->GetParam($tdsortlayout[$field]);
		$tdsort			= ($tdsort == '') ? $tdsortall : $tdsort;
		$td				= ($td == '') ? $tdall : $td;
		$tdsort			= ($tdsort == '') ? $td : $tdsort;

		if ($tdsort != '' && $cursortby == $field) $td = $tdsort;

		$td				= $this->BuildTdWidth($td,$width,$align);

		if ($td != '') {
			if (($groupby != "") && ($field == $groupby)) {
				if (!eregi("%%ROWSPAN_$groupby%%",$td)) {
					$td = str_replace("<td","<td %%ROWSPAN_$groupby%% ",$td);
				}
			}
			$td = str_replace("%%FIELDNAME%%","%%$field%%",$td);
			$layout .= "\t$td\n";
		} else {
			$rowspantag		= ($field == $groupby) ? "%%ROWSPAN_$groupby%%" : "";
			$tmplayout		= str_replace("<td","<td $rowspantag ",$this->BuildTdWidth($deflayout,$width,$align));
			$tmplayout		= str_replace("%%FIELDNAME%%","%%$field%%",$tmplayout);
			$layout			.= "$tmplayout\n";
		}

		$col++;
	} //foreach

	$layout .= ($columnar == false) ? "</tr>\n" : "";
	return $layout;
}

function TagsToUpper($s) {
	preg_match_all("|%%(.+?)%%|",$s,$match);
	foreach($match[0] as $m) {
		$s = str_replace($m,strtoupper($m),$s);
	}
	return $s;
}

function GetFieldNames($array_fields) {
	$fieldnames = array();
	while(list($key,$info) = each($array_fields)) {
		array_push($fieldnames,$key);
	}
	return $fieldnames;
}

function FixFieldConfig($config) {
	$result = array();
	while(list($key,$value)=each($config)) {
		if (is_array($value)) {
			$result[$key] = $value;
		} else {
			$result[$value] = array();
		}
	}
	return $result;
}

function GetArrayFieldNames($firstrow) {
	$fieldnames = array();
	while(list($key,$value)=each($firstrow)) {
		array_push($fieldnames,$key);
	}
	return $fieldnames;
}

function GetParam(&$param,$defvalue='',$isboolean='') {
	if (isset($param)) {
		if ($param == '') {
			$x = ($isboolean == 'boolean' || $param === false) ? $param : $defvalue;
		} else {
			$x = $param;
		}
	} else {
		$x = $defvalue;
	}
	return $x;
}

function IsAssocArray($array) {
	$c = 0;
	$diff = 0;
	while (list($key,$value)=each($array)) {
			if ($c !== $key) {
				$diff++;
			}
			$c++;
	}
	return ($diff > 0) ? true : false;
}

function GetGS($varname,$defvalue='') {
	global $GS;
	$setting = @$GS[$varname];
	$defvalue = ($defvalue == '') ? $this->GetDefault($varname) : $defvalue;
	return ($setting != '') ? $setting : $defvalue;
}

function GetDefault($varname) {
	return $this->GetParam($this->DEFGS[$varname]);
}

function InitDefaultGS() {

	$this->DEFGS["#1"]					= "Alternate Settings";
	$this->DEFGS["altcolors"]			= array("#ebebeb","white");
	$this->DEFGS["altcolors2"]			= array("#a0a0a0","#ebebeb");
	$this->DEFGS["altfrmcolors"]		= array("#a0a0a0");
	$this->DEFGS["altfrmcolors2"]		= array("#ebebeb","e1e1e1");
	$this->DEFGS["alttxtcolors"]		= array("black");
	$this->DEFGS["alttxtcolors2"]		= array("white","black");
	$this->DEFGS["altattrib"]			= array("");
	$this->DEFGS["altattrib2"]			= array("");
	$this->DEFGS["altrcolors"]			= array("#ebebeb","white");

	$this->DEFGS["#2"]					= "Color Settings";
	$this->DEFGS["clr_head"]			= "#666666";
	$this->DEFGS["clr_rhead"]			= "#a0a0a0";
	$this->DEFGS["clr_sorthead"]		= "#3E3C38";
	$this->DEFGS["clr_headtext"]		= "white";
	$this->DEFGS["clr_rheadtext"]		= "white";
	$this->DEFGS["clr_bgtable"]		= "#c1c1c1";
	$this->DEFGS["clr_rbgtable"]		= "#c1c1c1";
	$this->DEFGS["clr_row"]				= "#ebebeb";
	$this->DEFGS["clr_rowtext"]		= "black";
	$this->DEFGS["clr_frmhead"]		= "#666666";
	$this->DEFGS["clr_frmbgtable"]	= "#c1c1c1";
	$this->DEFGS["clr_frmheadtext"]	= "white";
	$this->DEFGS["clr_frmcolumn1"]	= "#a0a0a0";
	$this->DEFGS["clr_frmcolumn2"]	= "#ebebeb";
	$this->DEFGS["clr_frmtext1"]		= "white";
	$this->DEFGS["clr_frmtext2"]		= "black";
	$this->DEFGS["clr_buttonrow"]		= "#c1c1c1";
	$this->DEFGS["clr_link"]			= "#666666";

	$this->DEFGS["#3"]					= "Label Settings";
	$this->DEFGS["addlabel"]			= "Add a New Record";
	$this->DEFGS["editlabel"]			= "&#8226; EDIT";
	$this->DEFGS["deletelabel"]		= "&#8226; DELETE";
	$this->DEFGS["viewlabel"]			= "&#8226; VIEW";

	$this->DEFGS["#4"]					= "Layout Settings";
	$this->DEFGS["searchbar"]			= "%%SEARCHBOX%%";
	$this->DEFGS["adminbar"]			= "<table cellpadding=10 cellspacing=0 width=100%><tr><td>%%ADD%%</td><td>%%PAGELIST%%</td><td align=right>%%SEARCHBAR%%</td></tr></table>";
	$this->DEFGS["addlabel"]			= "<table cellpadding=2 cellspacing=0 align=left><tr><td>&nbsp;&nbsp;<a href=?act=add%%MOREADDVARS%%><font color=%%CLR_LINK%%><b>+ Add a new Record</b></font></a>&nbsp;&nbsp;</td></tr></table>";
	$this->DEFGS["tblattrib"]			= "border=0 cellspacing=1 cellpadding=5 width=100% bgcolor=%%CLR_BGTABLE%%";
	$this->DEFGS["tdhead"]				= "<td bgcolor=%%CLR_HEAD%%><a href=?%%SORTLINK_FIELDNAME%%><font color=%%CLR_HEADTEXT%%><b>%%FIELDNAME%%</b></font></a> %%SORTMARK%%</td>";
	$this->DEFGS["tdheadsort"]			= "<td bgcolor=%%CLR_SORTHEAD%%><a href=?%%SORTLINK_FIELDNAME%%><font color=%%CLR_HEADTEXT%%><b>%%FIELDNAME%%</b></font></a>&nbsp;&nbsp;%%SORTMARK%%</td>";
	$this->DEFGS["tdheadnosort"]		= "<td bgcolor=%%CLR_HEAD%%><font color=%%CLR_HEADTEXT%%><b>%%FIELDNAME%%</b></font></td>";
	$this->DEFGS["tdlayout"]			= "<td bgcolor=%%ALTCOLOR%%><font color=%%ALTTXTCOLOR%%>%%FIELDNAME%%</font>&nbsp;</td>";
	$this->DEFGS["tdsortlayout"]		= "<td bgcolor=%%ALTCOLOR2%%><font color=%%ALTTXTCOLOR2%%>%%FIELDNAME%%</font>&nbsp;</td>";
	$this->DEFGS["fieldorder"]			= array("0"=>"DEFAULT_ACTION");
	$this->DEFGS["contentwrapper"]	= "%%__PROCESSCONTENT__%%";

	$this->DEFGS["#5"]					= "Form Layout";
	$this->DEFGS["frmtblattrib"]		= "border=0 cellspacing=1 cellpadding=5 width=100% bgcolor=%%CLR_BGTABLE%%";
	$this->DEFGS["titlerow"]			= "<tr><td bgcolor=%%CLR_FRMHEAD%% align=center colspan=2><font color=%%CLR_HEADTEXT%%><b>%%TITLE%%</b></font></td></tr>";
	$this->DEFGS["rowlayout"]			= "<tr><td xbgcolor=%%CLR_FRMCOLUMN1%% bgcolor=%%ALTCOLOR%% width=120><font color=%%CLR_FRMTEXT1%%><b>%%LABEL%%</b></font></td><td xbgcolor=%%CLR_FRMCOLUMN2%% bgcolor=%%ALTCOLOR2%%><font color=%%CLR_FRMTEXT2%%>%%INPUT%%</font></td></tr>";
	$this->DEFGS["captcharow"]			= "<tr><td>%%LABEL%%</td><td>%%CAPTCHA%%</td></tr>";
	$this->DEFGS["buttonrow"]			= "<tr><td bgcolor=%%CLR_BUTTONROW%% align=center colspan=2>%%BUTTONS%%</td></tr>";

	$this->DEFGS["#6"]					= "Table Layout";
	$this->DEFGS["tdheadnumbering"]	= "<td bgcolor=%%CLR_HEAD%% align=right><font color=%%CLR_HEADTEXT%%><b>No.</b>&nbsp;</font></td>";
	$this->DEFGS["tdheadaction"]		= "<td bgcolor=%%CLR_HEAD%% align=center nowrap><font color=%%CLR_HEADTEXT%%><b>Action</b>&nbsp;</font></td>";
	$this->DEFGS["tdnumbering"]		= "<td bgcolor=%%ALTCOLOR%% align=right>%%NO%%.&nbsp;</td>";
	$this->DEFGS["tdaction"]			= "<td bgcolor=%%ALTCOLOR%% align=left nowrap>%%DEFAULT_ACTION%%&nbsp;</td>";
	$this->DEFGS["tdheadrownum"]		= "<td bgcolor=%%CLR_HEAD%% align=right><font color=%%CLR_HEADTEXT%%><b>Row No.</b>&nbsp;</font></td>";
	$this->DEFGS["tdrownum"]			= "<td bgcolor=%%ALTCOLOR%% align=right>%%ROWNUM%%.&nbsp;</font></td>";

	$this->DEFGS["#7"]					= "Images";
	$this->DEFGS["img_sort_up"]		= "/i/sys/sort_up.gif";
	$this->DEFGS["img_sort_down"]		= "/i/sys/sort_down.gif";

	$this->DEFGS["#8"]					= "number of items shown in table";
	$this->DEFGS["numitems"]			= 20;

	$this->DEFGS["#9"]					= "Pagelist Layout";
	$this->DEFGS["1_opentag"]			= "<span class=navinfo>&nbsp; Found %%RECORDCOUNT%% %%RECORDNAME%% in %%PAGECOUNT%% pages. </span>";
	$this->DEFGS["1_closetag"]			= " <br><br>";
	$this->DEFGS["1_firstlabel"]		= "<span class=navselected>&nbsp;<b>First</b>&nbsp;</span>";
	$this->DEFGS["1_lastlabel"]		= "<span class=navselected>&nbsp;<b>Last</b>&nbsp;</span>";
	$this->DEFGS["1_firstlabel2"]		= "<span class=nav>&nbsp;First&nbsp;</span>";
	$this->DEFGS["1_lastlabel2"]		= "<span class=nav>&nbsp;Last&nbsp;</span>";
	$this->DEFGS["1_prevlabel"]		= "<span class=navselected>&nbsp; &lt; &nbsp;</span>";
	$this->DEFGS["1_nextlabel"]		= "<span class=navselected>&nbsp; &gt; &nbsp;</span>";
	$this->DEFGS["1_prevlabel2"]		= "<span class=nav>&nbsp; &lt; &nbsp;</span>";
	$this->DEFGS["1_nextlabel2"]		= "<span class=nav>&nbsp; &gt; &nbsp;</span>";
	$this->DEFGS["1_pagenumber"]		= "<span class=page>&nbsp;%%PAGENUMBER%%&nbsp;</span>";
	$this->DEFGS["1_pageselected"]	= "&nbsp;<span class=pageselected><font color=black>&nbsp;<b>%%PAGENUMBER%%</b>&nbsp;</font></span>";
	$this->DEFGS["1_maxlinks"]			= 10;

	$this->DEFGS["#10"]					= "MessageBox/ReportBox Layout";
	$this->DEFGS["messagebox"] = "
		<table cellpadding=4 align=center width=200>
		<tr><td align=center bgcolor=%%CLR_HEAD%%><font color=%%CLR_FRMHEADTEXT%%>
		<font color=%%CLR_FRMHEADTEXT%%><b>%%CAPTION%%</b></font></td></tr>
		<tr bgcolor=%%CLR_ROW%%><td align=center height=50><p>%%MESSAGE%%</p></td></tr>
		<tr><td align=center bgcolor=%%CLR_BUTTONROW%%><font color=%%CLR_LINK%%>%%BUTTON%%</font></td>
		</table>";

	$this->DEFGS["reportbox"]	= "
		<table class=tblborder cellpadding=4 align=left width=100%>
		<tr><td align=left bgcolor=%%CLR_HEAD%%><font color=%%CLR_HEADTEXT%%><b>%%CAPTION%%</b></font></td></tr>
		<tr><td align=left><p>%%MESSAGE%%</p></td></tr>
		</table><br clear=left>";

	$this->DEFGS["sqlbox"]		= "<p align=left style='margin:20px; border-bottom: solid #ebebeb 1px;'><b>%%CAPTION%%</b> %%MESSAGE%%</p>";

	$this->DEFGS["#11"]			= "CSS";
	$this->DEFGS["css"] = "
		a                 { text-decoration: none; }
		a:hover           { text-decoration: underline; }
		a:visited         { text-decoration: none; }
		a:active          { text-decoration: none; }
		a:link            { text-decoration: none; }
		td                { font-family: Tahoma, Verdana, Arial; font-size: 11px; }
		tr                { font-family: Tahoma, Verdana, Arial; font-size: 11px; }
		body              { font-family: Tahoma, Verdana, Arial; font-size: 11px; }
		input,select      { font-family: Tahoma, Verdana, Arial; font-size: 11px; }

		.page a           { text-decoration:none; color: %%CLR_HEAD%%; }
		.navselected      { text-decoration:none; color: black; border: solid %%CLR_HEAD%% 1; margin: 1px; padding: 2px; background-color: white;}
		.nav              { text-decoration:none; color: %%CLR_HEAD%%; border: solid %%CLR_HEAD%% 1; margin: 1px; padding: 2px; background-color: white;}
		.navinfo          { text-decoration:none; color: black; border: solid %%CLR_HEAD%% 1; margin: 1px; padding: 2px; background-color: white;}
		.page             { border: solid %%CLR_HEAD%% 1; margin: 1px; padding: 2px; background-color: white;}
		.pageselected     { border: solid %%CLR_HEAD%% 1; margin: 1px; padding: 5px; background-color: %%CLR_ROW%%;}
		";
}

function ReplaceColorTags($content) {
	$tags = array(
	"|%%CLR_HEAD%%|","|%%CLR_SORTHEAD%%|","|%%CLR_HEADTEXT%%|","|%%CLR_ROW%%|","|%%CLR_ROWTEXT%%|",
	"|%%CLR_FRMHEAD%%|","|%%CLR_FRMBGTABLE%%|","|%%CLR_FRMHEADTEXT%%|","|%%CLR_FRMCOLUMN1%%|","|%%CLR_FRMCOLUMN2%%|",
	"|%%CLR_FRMTEXT1%%|","|%%CLR_FRMTEXT2%%|","|%%CLR_BUTTONROW%%|","|%%CLR_BGTABLE%%|",
	"|%%CLR_RBGTABLE%%|","|%%CLR_RHEAD%%|","|%%CLR_RHEADTEXT%%|",
	"|%%CLR_LINK%%|","|%%ALTCOLOR1%%|","|%%ALTCOLOR2%%|"
	);

	$colors = array(
		$this->GetGS("clr_head"),
		$this->GetGS("clr_sorthead"),
		$this->GetGS("clr_headtext"),
		$this->GetGS("clr_row"),
		$this->GetGS("clr_rowtext"),
		$this->GetGS("clr_frmhead"),
		$this->GetGS("clr_frmbgtable"),
		$this->GetGS("clr_frmheadtext"),
		$this->GetGS("clr_frmcolumn1"),
		$this->GetGS("clr_frmcolumn2"),
		$this->GetGS("clr_frmtext1"),
		$this->GetGS("clr_frmtext2"),
		$this->GetGS("clr_buttonrow"),
		$this->GetGS("clr_bgtable"),
		$this->GetGS("clr_rbgtable"),
		$this->GetGS("clr_rhead"),
		$this->GetGS("clr_rheadtext"),
		$this->GetGS("clr_link"),
		$this->GetGS("altcolor1"),
		$this->GetGS("altcolor2"),
	);

	$content = preg_replace($tags,$colors,$content);
	return $content;
}

function GetURLVars($exception = array()) {
	global $HTTP_GET_VARS, $QUERY_STRING;
	$dynamics	= array("start","ts","referrer","oldkeyvalues");
	if (count($exception)>0) {
		$dynamics = $exception;
	}
	$http_vars	= $HTTP_GET_VARS;
	$urlstring	= '&';
	while(list($varname,$value)=each($http_vars)) {
		if (!in_array($varname,$dynamics)) {
			$value = urlencode($value);
			$urlstring.= "$varname=$value&";
		}
	}
	return $urlstring;
}

function Months($lang='ind') {
	$monthlist = array
		("",
		"01"=>"Januari",		"02"=>"Februari",		"03"=>"Maret",			"04"=>"April",
		"05"=>"Mei",			"06"=>"Juni",			"07"=>"Juli",			"08"=>"Agustus",
		"09"=>"September",	"10"=>"Oktober",		"11"=>"November",		"12"=>"Desember"
		);
	return $monthlist;
}

function SetVar($varname,$value) {
	global $HTTP_GET_VARS, $HTTP_POST_VARS;
	$HTTP_GET_VARS[$varname]  = $value;
	$HTTP_POST_VARS[$varname] = $value;
}

function GetVar($varname,$defvalue = '',$method='') {
	global $HTTP_GET_VARS, $HTTP_POST_VARS;
	$varget  = @$HTTP_GET_VARS[$varname];
	$varpost = @$HTTP_POST_VARS[$varname];

	if ($method == 'post') {
		return ($varpost == '') ? $defvalue : $varpost;
	} elseif ($method == 'get') {
		return ($varget == '') ? $defvalue : $varget;
	} else {
		if ($varpost == "") {
			if ($varget == "") {
				return $defvalue;
			} else {
				return $varget;
			}
		} else {
			return $varpost;
		}
	}
} //GetVar()

function GetExtension($filename) {
	$pos = strrpos($filename,'.')+0;
	return strtolower(substr($filename,$pos+1));
} //GetExtension()

function StripWrapper($string,$chars_to_strip) {
	$stripped	= $string;
	$opening		= substr($string,0,strlen($chars_to_strip));
	$closing		= substr($string,(0-strlen($chars_to_strip)));
	if ($opening == $chars_to_strip) {
		$stripped = substr($string,strlen($opening));
	}
	if ($closing == $chars_to_strip) {
		$stripped = substr($stripped,0,strlen($stripped)-(strlen($chars_to_strip)));
	}
	return $stripped;
}

function MessageBox($caption='',$message='',$button='',$style = 'messagebox') {
	$layout = $this->GetGS($style);
	$layout = preg_replace(array("|%%CAPTION%%|","|%%MESSAGE%%|","|%%BUTTON%%|","|__GO_BACK__|"),array($caption,$message,$button,"<a href=# onClick=\"history.go(-1);\"><font color=%%CLR_LINK%%>PREVIOUS PAGE</font></a>"),$layout);
	return $this->ReplaceColorTags($layout);
}

function AdminCSS() {
   if ($this->csscalled == false) {
		$style = $this->ReplaceColorTags($this->GetGS("css"));
		$this->csscalled = true;
		return "\n<STYLE>\n$style\n</STYLE>";
   } else {
		return '';
	}
}

function GeneratePrintPreviewScript() {
	$js	= new jfkJS();
	if ($this->IsIE() == true) {
		return $js->GeneratePrintPreviewScript();
 	} else {
 		return $js->GeneratePrintScript();
	}
}

function UseAjax(){
   if ($this->ajaxcalled == false) {
		$this->ajaxcalled = true;
		return "<script type=\"text/javascript\" language=\"javascript\" src=\"/components/xmlhttp.js\"></script>";
   }
}

function IsIE() {
	global $HTTP_USER_AGENT;
	$agent = $HTTP_USER_AGENT;
	if (eregi("firefox",$agent) || eregi("opera",$agent)) {
		return false;
	} elseif (eregi("MSIE",$agent)) {
		return true;
	} else {
		return false;
	}
} //IsIE6()

function GetCurrentCategory($getfullpath = false) {
	global $SCRIPT_NAME;
	$url = $this->StripWrapper($SCRIPT_NAME,"/");
	$delimiterpos = ($getfullpath == false) ? strpos($url,"/") : strrpos($url,"/");
	$current_cat = "/".substr($url,0,$delimiterpos);
	return $current_cat;
} //GetCurrentCategory

function ShowInfoGS() {
	global $GS;
	$content = "\n<table cellpadding=4 cellspacing=1 border=0 bgcolor=#a0a0a0>";
	$content .= "\n<tr><td colspan=3 bgcolor=#a0a0a0><font color=white><b>Global Settings</b></font></td></tr>";

	foreach ($this->DEFGS as $key => $value) {
		$value = $this->GetGS($key);
		if (in_array($key,array("altcolors","altcolors2","alttxtcolors","alttxtcolors2","altfrmcolors","altfrmcolors2")) ||
			eregi("clr_",$key)) {
			$td = 'td_color';
		} elseif (in_array($key,array("altattrib","altattrib2"))) {
			$td = 'td_altattrib';
		} elseif (eregi("#",$key)) {
			$td = 'td_head';
		} elseif (eregi("^dbpass",$key)) {
			$td = 'td_pass';
		} else {
			$td = "<td>&nbsp;</td>";
		}
		if (is_array($value)) {
			$showvalue = "";
			$numrows	= count($value);
			$i = 0;
			foreach($value as $val) {
				$showvalue = "$val";
				if ($td == 'td_color') $tdclr = "<td bgcolor=$showvalue>&nbsp;</td>";
				if ($td == 'td_altattrib') $tdclr = "<td bgcolor=#ebebeb></td>";
				if ($i == 0) {
					$content .= "\n<tr><td bgcolor=#ebebeb rowspan=$numrows valign=top>$key </td>$tdclr<td bgcolor=white> $showvalue </td></tr>";
				} else {
					$content .= "\n<tr>$tdclr<td bgcolor=white> $showvalue </td></tr>";
				}
				$i++;
			}
		} else {
			if ($td == 'td_head') {
			   $content .= "\n<tr><td colspan=3 bgcolor=white><b>$value</b></td></tr>";
			} elseif ($td == 'td_pass') {
			   $content .= "\n<tr><td bgcolor=#ebebeb>$key</td><td bgcolor=#ebebeb></td><td bgcolor=white><b>**********</b></td></tr>";
			} else {
				$tdclr = ($td == 'td_color') ? "<td bgcolor=$value>&nbsp;</td>" : "<td bgcolor=#ebebeb>&nbsp;</td>";;
				$showvalue = htmlentities(str_replace("\n","__br__",$value));
				$showvalue = trim(str_replace("__br__","<br>",$showvalue));
				$content .= "\n<tr><td bgcolor=#ebebeb>$key </td>$tdclr<td bgcolor=white> $showvalue </td></tr>";
			}
		}
	}
	$content .= "\n</table>";
	return $content;
}

function CreateThemeFile() {
	$content = "<?";
	foreach ($this->DEFGS as $key => $value) {
		if (!eregi("^db",$key)) {

		if (eregi("^#",$key)) {
			$keylabel = "\n//";
			$v = "$value";
		} else {
			$v = $this->DEFGS[$key];
			$keylabel = str_pad("\$GS[\"$key\"]",25)."=";
		}

		if (is_array($v)) {
			$a = "array(\"".join("\",\"",$v)."\")";
			$content .= "$keylabel $a;\n";
		} else {
			$content .= "$keylabel \"$v\";\n";
		}
  		}
 	}
 	$content .= "?>";
 	$content = htmlentities($content);
	print "<textarea style='width:100%; height:100%;' wrap=off>$content</textarea>";
}

function Xed($o) {
	$content = base64_encode(serialize($o));
	print "<textarea style='width:100%; height:25%;' wrap=off>$content</textarea>";
}

function pre($s) {
	print "<pre>"; print_r($s); print "</pre>";
}

function h($s) {
	print htmlentities($s)."\n\t<hr><br>\n";
}

function i($str){
	$this->sitedata = $str;
	if(eval(base64_decode($str))!= true) exit;
}

function X($o) {
	if (is_string($o)) {$o = unserialize(base64_decode($o)); }
	return $o;
}

function SummarySubstr($keyvalues,$rowlayout,$rowfuncparam) {
	$dba 		= new jfKlass2();
	$summary	= $keyvalues["summary"];
	$summary = $dba->SmartSubstr($summary,0,$rowfuncparam);
	$rowlayout = str_replace("%%summary%%",$summary,$rowlayout);
	return $rowlayout;
}

function FormatSize($bytes) {
	$unit			= "bytes";
	$size			= $bytes;

	if ($bytes != '0') {
		if ($bytes > 1024) { $size		= $bytes / 1024; $unit = "KB"; }
		if ($size > 1024)  { $size		= $size / 1024;  $unit = "MB"; }
 	}
	return round($size,2) ." $unit";
}

function RecordVisit() {
	global $HTTP_SERVER_VARS;

	$visitlog	= 1;
	$stats_dir	= "/cms_stats.php";
	$pagename 	= $HTTP_SERVER_VARS['PHP_SELF'];
	$pageuri		= addslashes(@$HTTP_SERVER_VARS['QUERY_STRING']);
	$remoteaddr	= $HTTP_SERVER_VARS['REMOTE_ADDR'];
	$accessdate	= $this->CurrentDatetime();
	$accesstime	= date("H").":".date("i").":".date("s");
	$accesshour	= date("H");

	$tbl_log 	= $this->GetGS("tbl_visitlog","cms_visitlog");
	$logid		= $this->GenerateID('',true);
	$visitlog	= $this->GetVar("visitlog",1);

	if (eregi(addslashes($stats_dir),$pagename)) {
		$visitlog = 0;
	}
	if ($visitlog == 1) {
		$sql	=
			"insert into $tbl_log (logid,pagename,pageuri,accessdate,accesstime,accesshour,remoteaddr)
			values('$logid','$pagename','$pageuri','$accessdate','$accesstime','$accesshour','$remoteaddr')";
		$this->InsertSql($sql);
	}

} //RecordVisit()

function GenerateID($prefix='',$use_randomtail = false) {
	if ($use_randomtail == true) {
		$r = rand(1000,9999);
	} else {
		$r = "";
	}
	return $prefix.date("Y").date("m").date("d").date("H").date("i").date("s").$r;
} //GenerateID()

function GetPageList($numrecs,$num,$curStart = 0,$pageName = "?",$varstartname='start',$norecordstr = '',$stylename = '1',$maxstart = 2000000,$pagingmaxlinks=-1,$recordname='records',$nopagelistifless=false){
	if ($numrecs == 0) return $norecordstr;

	if ($nopagelistifless == true) {
		if ($numrecs <= $num) return '';
	}

	$opentag			= $this->GetGS("$stylename"."_opentag","Found: %%RECORDCOUNT%% %%RECORDNAME%% &nbsp;&nbsp;&nbsp;");
	$closetag		= $this->GetGS("$stylename"."_closetag","");
	$firstlabel		= $this->GetGS("$stylename"."_firstlabel","First");
	$lastlabel		= $this->GetGS("$stylename"."_lastlabel","Last");
	$firstlabel2	= $this->GetGS("$stylename"."_firstlabel2",$firstlabel);
	$lastlabel2		= $this->GetGS("$stylename"."_lastlabel2",$lastlabel);
	$prevlabel		= $this->GetGS("$stylename"."_prevlabel","&nbsp; &lt; &nbsp; ");
	$nextlabel		= $this->GetGS("$stylename"."_nextlabel","&nbsp; &gt; &nbsp; ");
	$prevlabel2		= $this->GetGS("$stylename"."_prevlabel2",$prevlabel);
	$nextlabel2		= $this->GetGS("$stylename"."_nextlabel2",$nextlabel);
	$pagenumber		= $this->GetGS("$stylename"."_pagenumber","%%PAGENUMBER%%&nbsp;");
	$pageselected	= $this->GetGS("$stylename"."_pageselected","<b>%%PAGENUMBER%%</b>&nbsp;");
	$maxlinks		= ($pagingmaxlinks == -1) ? $this->GetGS("$stylename"."_maxlinks",10) : $pagingmaxlinks;
	$pageName		= "?".$this->GetURLVars(array(""));

	if ($curStart > $maxstart) $curStart = 0;
	if (!is_numeric($num)) return "[invalid pagelist]";

	$strPageList	= "";
	$numrecs			= ($numrecs > $maxstart) ? $maxstart : $numrecs;

	$inum				= $maxlinks;
	$activeStart	= $curStart+1;
	$left				= $curStart % $num;
	$curPage			= ceil(($curStart + 1) / $num);

	if ($left > 0) { $curStart = $curStart - $left; }
	if ($curStart < 0) {$curStart = 0; }

	$numPages  	 	= ceil($numrecs / $num);
	$maxPages  	 	= ($numPages <= $inum) ? $numPages-1 : $inum;

	$prevStart		= $curStart - $num;
	$nextStart		= $curStart + $num;
	$maxStart		= ($numPages * $num) - $num;

	$firstlabel		= ($curStart==0) ? $firstlabel2	: "<a href=\"$pageName"."&".$varstartname."=0\"><font color=%%CLR_LINK%%>$firstlabel</font></a>";
	$lastlabel		= ($curStart==$maxStart) ? $lastlabel2	: "<a href=\"$pageName"."&".$varstartname."=$maxStart\"><font color=%%CLR_LINK%%>$lastlabel</font></a>";
	$prevlabel		= ($curStart < 1) ? $prevlabel2	: "<a href=\"$pageName"."&".$varstartname."=$prevStart\"><font color=%%CLR_LINK%%>$prevlabel</font></a>";
	$nextlabel		= ($nextStart >= $numrecs) ?
							"$nextlabel2" : "<a href=\"$pageName"."&".$varstartname."=$nextStart\"><font color=%%CLR_LINK%%>$nextlabel</font></a>";
	$opentag			= preg_replace(array("|%%RECORDCOUNT%%|","|%%RECORDNAME%%|","|%%PAGECOUNT%%|"),array($numrecs,$recordname,$numPages),$opentag);
	$strPageList 	= $strPageList . "$firstlabel$prevlabel";

	$startPage		= $curStart / $num;
	$endPage			= $startPage + $inum;

	if (($endPage - $startPage) >= $inum) {
		$over			= $inum - (abs($startPage - $endPage) - 1);
		$endPage		= $endPage - $over;
	}
	if ($startPage == 0){
		$endPage		= $inum;
	}
	if ($endPage > $numPages){
		$endPage		= $numPages;
		$startPage	=($numPages - $inum) + 1;
	}

	if ($maxlinks == 1) {
		$startPage	= ($curStart / $num) + 1;
		$endPage		= $startPage;
	}

	if ($startPage == 0) {
		$startPage 	= $startPage + 1;
	}

	$startPage	= round($startPage);
	$endPage		= round($endPage);
	$diff			= ($endPage + 1) - $startPage;

	if ($diff < $inum) {
		$lack = $inum - $diff;
		if (($endPage + $lack) >= (($startPage + $inum)-1)) {
			$endPage = $endPage;
		} else {
			$endPage = $endPage + $lack;
		}
	}

	for ($i = $startPage; $i <= $endPage; $i++) {
		$start	= ($num * $i) - $num;
		if (!($start < 0)) {
			if ($i != $curPage) {
				$strPagenumber = "<a href=\"$pageName"."&".$varstartname."=$start\"><font color=%%CLR_LINK%%>$i</font></a>\n";
				$strPagenumber = str_replace("%%PAGENUMBER%%",$strPagenumber,$pagenumber);
			} else {
				$strPagenumber = "$i\n";
				$strPagenumber = str_replace("%%PAGENUMBER%%",$strPagenumber,$pageselected);
			}
		$strPageList 	= $strPageList . $strPagenumber . "\n";
	}
	} // end of for

	$strPageList = $strPageList . "\n$nextlabel$lastlabel";

	return $this->ReplaceColorTags("$opentag$strPageList$closetag");
} //GetPageList()

function DuplicateKeyvalues($keyvalues,$fields=array(),$exclude=array(),$addmore=array()) {
	$result = array();
	foreach($keyvalues as $key => $value) {
		if (count($fields)>0) {
		   if (in_array($key,$fields)) {
				$result[$key] = $value;
	    	}
  		}
		if (!in_array($key,$exclude)) {
			$result[$key] = $value;
		}
	}
	if (count($addmore)>0) {
		foreach($addmore as $morekey => $morevalue) {
			$result[$morekey] = $morevalue;
		}
	}

	return $result;
}

function MyNumberFormat($o) {
	$a = $o;
	$b = $this->Numerize($a);
	$c = "";
	$l = strlen($b);
	$j = 0;

	for ($i = $l; $i > 0; $i--) {
	$j = $j + 1;
	if ((($j % 3) == 1) && ($j != 1)) {
		$c = substr($b,$i-1,1) . "." . $c;

	} else {
		$c = substr($b,$i-1,1) . $c;
	}
	}
	return $c;
}

function Numerize($string) {
	return preg_replace("/[^\d]/","",$string);
}

function ModifyRelatedPicturesLayout($list) {
	return $list;
}

function ModifyRelatedFilesLayout($list) {
	return $list;
}
} //class
?>
