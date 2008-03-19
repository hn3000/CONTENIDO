<?php
/*****************************************
* File      :   $RCSfile: include.mod_translate.php,v $
* Project   :   Contenido
* Descr     :   Module translation editor
* Modified  :   $Date: 2007/07/19 20:32:06 $
*
* � four for business AG, www.4fb.de
*
* $Id: include.mod_translate.php,v 1.13 2007/07/19 20:32:06 bjoern.behrens Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "contenido/class.module.php");
cInclude("classes", "class.xmltree.php");
cInclude("includes","functions.upl.php");

$langobj = new Language;
$langobj->loadByPrimaryKey($lang);

$langstring = $langobj->get("name") . ' ('.$lang.')';

$moduletranslations = new cApiModuleTranslationCollection;
$module = new cApiModule($idmod);

if ($action == "mod_translation_save")
{
	$strans = new cApiModuleTranslation;
	$strans->loadByPrimaryKey($idmodtranslation);

	if ($strans->get("idmod") == $idmod)
	{	
		$module->setTranslatedName($translatedname);

		$strans->set("translation", stripslashes($t_trans));
		$strans->store();
		
		/* Increase idmodtranslation */
		$moduletranslations->select("idmod = '$idmod' AND idlang = '$lang'");
		
		while ($mitem = $moduletranslations->next())
		{
			if ($mitem->get("idmodtranslation") == $idmodtranslation)
			{
				$mitem2 = $moduletranslations->next();
				
				if (is_object($mitem2))
				{
					$idmodtranslation = $mitem2->get("idmodtranslation");
					break;	
				}	
			}	
		}
	}
}

if ($action == "mod_importexport_translation")
{
	if ($mode == "export")
	{
		$sFileName = uplCreateFriendlyName(strtolower($module->get("name") . "_" . $langobj->get("name")));
		
		if ($sFileName != "")
		{
			$moduletranslations->export($idmod, $lang,  $sFileName . ".xml");
		}
	}
	if ($mode == "import")
	{
		if (file_exists($_FILES["upload"]["tmp_name"]))
		{
			$moduletranslations->import($idmod, $lang, $_FILES["upload"]["tmp_name"]);
		}
	}
} 


if (!isset($idmodtranslation))
{
	$idmodtranslation = 0;	
}

$mtrans = new cApiModuleTranslation;
$mtrans->loadByPrimaryKey($idmodtranslation);

if ($mtrans->get("idmod") != $idmod)
{
	$moduletranslations->select("idmod = '$idmod' AND idlang = '$lang'");
	
	$mtrans = $moduletranslations->next();
	
	if (is_object($mtrans))
	{
		$idmodtranslation = $mtrans->get("idmodtranslation");
	} else {
		$mtrans = new cApiModuleTranslation;
	}
}

$strings = $module->parseModuleForStrings();

/* Insert new strings */
foreach ($strings as $string)
{
	$moduletranslations->create($idmod, $lang, $string);
}

$moduletranslations->select("idmod = '$idmod' AND idlang = '$lang'");

while ($d_modtrans = $moduletranslations->next())
{
	if (!in_array($d_modtrans->get("original"), $strings))
	{
		$moduletranslations->delete($d_modtrans->get("idmodtranslation")); 	
	}	
}

$page = new cPage;

$form = new UI_Table_Form("translation");
$form->addHeader(sprintf(i18n("Translate module \"%s\""), $module->get("name"))); 
$form->setVar("area", $area);
$form->setVar("frame", $frame);
$form->setVar("idmod", $idmod);
$form->setVar("idmodtranslation", $idmodtranslation);
$form->setVar("action", "mod_translation_save");

$transmodname = new cHTMLTextbox("translatedname", $module->getTranslatedName(),60);

$form->add(i18n("Translated Name"), $transmodname);

$ilink = new cHTMLLink;
$ilink->setCLink("mod_translate", 5, "");
$ilink->setCustom("idmod", $idmod);
$ilink->setCustom("idmodtranslation", $mtrans->get("idmodtranslation"));
$ilink->setAnchor($mtrans->get("idmodtranslation"));

$iframe = '<iframe frameborder="0" style="border: 1px;border-color: black; border-style: solid;" width="620" src="'.$ilink->getHREF().'"></iframe>';

$table = '<table border="0" width="600" border="0"><tr><td width="50%">'.i18n("Original module string").'</td><td width="50%">'.sprintf(i18n("Translation for %s"), $langstring).'</td><td width="20">&nbsp;</td></tr><tr><td colspan="3">'.$iframe.'</td></tr>';

$original = new cHTMLTextarea("t_orig",htmlspecialchars($mtrans->get("original")));
$original->setStyle("width: 300px;");
$translated = new cHTMLTextarea("t_trans",htmlspecialchars($mtrans->get("translation")));
$translated->setStyle("width: 300px;");

$table .= '<tr><td>'.$original->render().'</td><td>'.$translated->render().'</td><td width="20">&nbsp;</td></tr></table>';
$table .= i18n("Hint: Hit ALT+SHIFT+S to save the translated entry and advance to the next string.");
$form->add(i18n("String list"), $table);

$mark = '<script language="JavaScript">document.translation.t_trans.focus();</script>';


$import = new cHTMLRadiobutton("mode", "import");
$export = new cHTMLRadiobutton("mode", "export");
$export->setLabelText("Export to file");
$import->setLabelText("Import from file");

$import->setEvent("click", "document.getElementById('vupload').style.display = '';");
$export->setEvent("click", "document.getElementById('vupload').style.display = 'none';");
$upload = new cHTMLUpload("upload");

$import->setChecked("checked");
$form2 = new UI_Table_Form("export");
$form2->setVar("action", "mod_importexport_translation");
$form2->addHeader("Import/Export");
$form2->add(i18n("Mode"), array($export, "<br>", $import));
$form2->add(i18n("File"), $upload, "vupload");
$form2->setVar("area", $area);
$form2->setVar("frame", $frame);
$form2->setVar("idmod", $idmod);
$form2->setVar("idmodtranslation", $idmodtranslation);
$form2->custom["submit"]["accesskey"] = '';

$page->setContent($form->render(). $mark ."<br>". $form2->render());
$page->setMarkScript(2);

$clang = new Language;
$clang->loadByPrimaryKey($lang);

$page->setEncoding($clang->get("encoding"));

if (!($action == "mod_importexport_translation" && $mode == "export"))
{
	$page->render();
}

?>