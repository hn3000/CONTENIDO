<?php

/**
 * This file contains the backend page for managing module script files.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $idmod, $tpl, $notification;

$client = cRegistry::getClientId();
$perm = cRegistry::getPerm();
$auth = cRegistry::getAuth();
$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();
$belang = cRegistry::getBackendLanguage();
$frame = cRegistry::getFrame();

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

$readOnly = (getEffectiveSetting('client', 'readonly', 'false') === 'true');
if ($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$moduleHandler = new cModuleHandler($idmod);
$sFileType = 'js';

$sActionCreate = 'js_create';
$sActionEdit = 'js_edit';

$file = $moduleHandler->getJsFileName();
$tmpFile = $moduleHandler->getJsFileName();
$sFilename = '';

if (empty($action)) {
    $actionRequest = $sActionEdit;
} else {
    $actionRequest = $action;
}

$permCreate = false;
if (!$moduleHandler->existFile('js', $moduleHandler->getJsFileName())) {
    if (!$perm->have_perm_area_action('js', $sActionCreate)) {
        $permCreate = true;
    }
}

$page = new cGuiPage("mod_script");

$tpl->reset();

if (!$perm->have_perm_area_action('js', $actionRequest) || $permCreate) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    return;
}

// display critical error if no valid client is selected
if ((int) $client < 1) {
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

$path = $moduleHandler->getJsPath(); // $cfgClient[$client]['js']['path'];

// ERROR MESSAGE
if (!$moduleHandler->moduleWriteable('js')) {
    $page->displayCriticalError(i18n('No write permissions in folder js for this module!'));
    $page->render();
    return;
}

$sTempFilename = stripslashes($tmpFile);
$sOrigFileName = $sTempFilename;

if (cFileHandler::getExtension($file) != $sFileType && cString::getStringLength(stripslashes(trim($file))) > 0) {
    $sFilename .= stripslashes($file) . '.' . $sFileType;
} else {
    $sFilename .= stripslashes($file);
}

if (stripslashes($file)) {
    $page->reloadLeftBottomFrame(['file' => $sFilename]);
}

if (true === cFileHandler::exists($path . $sFilename)
    && false === cFileHandler::writeable($path . $sFilename)) {
    $page->displayWarning(i18n("You have no write permissions for this file"));
}

$fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

$requestStatus = $_REQUEST['status'] ?? '';
$requestCode = $_REQUEST['code'] ?? '';

// Create new file
$bEdit = false;
if ((!$readOnly) && $actionRequest == $sActionCreate && $requestStatus == 'send') {
    $sTempFilename = $sFilename;

    if (true === cFileHandler::validateFilename($sFilename)) {
        cFileHandler::create($path . $sFilename);
        $moduleHandler->createModuleFile('js', $sFilename, $requestCode);
        $bEdit = cFileHandler::read($path . $sFilename);
    }

    if (false !== $bEdit) {
        // trigger a code cache rebuild if changes were saved
        $oApiModule = new cApiModule($idmod);
        $oApiModule->store();
    }

    $page->reloadRightTopFrame(['file' => $sTempFilename]);

    // Show message for user
    if ($bEdit === true) {
        $page->displayOk(i18n('Created new javascript file successfully'));
    } else {
        $page->displayError(i18n('Could not create a new javascript file!'));
    }
}

// Edit selected file
if ((!$readOnly) && $actionRequest == $sActionEdit && $requestStatus == 'send') {
    if ($sFilename != $sTempFilename) {
        try {
            if (true !== cFileHandler::validateFilename($sFilename)) {
                throw new cInvalidArgumentException('The file ' . $sFilename . ' could not be validated.');
            }

            if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
                $sTempFilename = $sFilename;
            } else {
                throw new cInvalidArgumentException('The file ' . $sFilename . ' could not be renamed.');
            }
        } catch (Exception $e) {
            $notification->displayNotification("error", sprintf(i18n("Can not rename file %s"), $path . $sTempFilename));
        }

        $page->reloadRightTopFrame(['file' => $sTempFilename]);
    } else {
        $sTempFilename = $sFilename;
    }

    $bEdit = false;
    if (true === cFileHandler::validateFilename($sFilename)) {
        $moduleHandler->createModuleFile('js', $sFilename, $requestCode);
        $bEdit = cFileHandler::read($path . $sFilename);
    }

    if (false !== $bEdit) {
        // trigger a code cache rebuild if changes were saved
        $oApiModule = new cApiModule($idmod);
        $oApiModule->store();
    }

    // Show message for user
    if ($sFilename != $sTempFilename) {
        $page->displayOk(i18n('Renamed and saved changes successfully!'));
    } else {
        $page->displayOk(i18n('Saved changes successfully!'));
    }
}

// Generate edit form
$fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
$sAction = ($bEdit) ? $sActionEdit : $actionRequest;
$module = new cApiModule($idmod);

if ($actionRequest == $sActionEdit
    && cFileHandler::exists($path . $sFilename)) {
    $sCode = cFileHandler::read($path . $sFilename);
    if ($sCode === false) {
        exit;
    }
    $sCode = cString::recodeString($sCode, $fileEncoding, cModuleHandler::getEncoding());
} else {
    // stripslashes is required here in case of creating a new file
    $sCode = stripslashes($requestCode);
}

$form = new cGuiTableForm('file_editor');
$form->setTableID('mod_javascript');
$form->addTableClass('col_flx_m_50p col_first_100');
$form->setHeader(i18n('Edit file') . " &quot;". conHtmlSpecialChars($module->get('name')). "&quot;");
$form->setVar('area', $area);
$form->setVar('action', $sAction);
$form->setVar('frame', $frame);
$form->setVar('status', 'send');
$form->setVar('tmp_file', $sTempFilename);
$form->setVar('idmod', $idmod);
$tb_name = new cHTMLLabel($sFilename, ''); // new cHTMLTextbox('file', $sFilename, 60);
$ta_code = new cHTMLTextarea('code', conHtmlSpecialChars($sCode), 100, 35, 'code');
//$descr     = new cHTMLTextarea('description', conHtmlSpecialChars($aFileInfo['description']), 100, 5);

$ta_code->setStyle('font-family:monospace;width:100%;');
//$descr->setStyle('font-family:monospace;width:100%;');
$ta_code->updateAttributes(['wrap' => getEffectiveSetting('script_editor', 'wrap', 'off')]);

$form->add(i18n('Name'), $tb_name);
$form->add(i18n('Code'), $ta_code);

$oCodeMirror = new CodeMirror('code', 'js', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg);
if ($readOnly) {
    $oCodeMirror->setProperty("readOnly", "true");
    $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
}

$page->setContent([$form]);
$page->addScript($oCodeMirror->renderScript());

//$page->addScript('reload', $sReloadScript);
$page->render();
