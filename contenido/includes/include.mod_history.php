<?php

/**
 * This file contains the backend page for module history.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Bilal Arslan
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $idmod, $bInUse;

$perm = cRegistry::getPerm();
$client = cSecurity::toInteger(cRegistry::getClientId());
$area = cRegistry::getArea();

$oPage = new cGuiPage('mod_history');

if (!$perm->have_perm_area_action($area, 'mod_history_manage')) {
    $oPage->displayError(i18n('Permission denied'));
    $oPage->abortRendering();
    $oPage->render();
    return;
} elseif (!$client > 0) {
    $oPage->abortRendering();
    $oPage->render();
    return;
} elseif (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
    $oPage->displayWarning(i18n('Versioning is not activated'));
    $oPage->abortRendering();
    $oPage->render();
    return;
}

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.mod.php');

$cfgClient = cRegistry::getClientConfig();
$db = cRegistry::getDb();
$cfg = cRegistry::getConfig();
$frame = cRegistry::getFrame();
$sess = cRegistry::getSession();
$belang = cRegistry::getBackendLanguage();

$readOnly = (getEffectiveSetting('client', 'readonly', 'false') === 'true');
if ($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

if ($idmod == '') {
    $idmod = $_REQUEST['idmod'];
}

$module = new cApiModule($idmod);

$bDeleteFile = false;

$requestModSend = isset($_POST['mod_send']);
$requestCodeOut = $_POST['CodeOut'] ?? '';
$requestCodeIn = $_POST['CodeIn'] ?? '';
$requestAction = $_POST['action'] ?? '';
$requestIdModHistory = $_POST['idmodhistory'] ?? '';

// Truncate history action
if ((!$readOnly) && $requestAction === 'history_truncate') {
    $oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    $bDeleteFile = $oVersion->deleteFile();
    unset($oVersion);
}

// Save action
if ((!$readOnly) && $requestModSend == true && ($requestCodeOut != '' || $requestCodeIn != '')) {
    $oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    $sName = $_POST['modname'];
    $sCodeInput = $_POST['CodeIn'];
    $sCodeOutput = $_POST['CodeOut'];
    $description = $_POST['moddesc'];

    // Save and make a new revision
    $oPage->addScript($oVersion->renderReloadScript('mod', $idmod, $sess));
    modEditModule($idmod, $sName, $description, $sCodeInput, $sCodeOutput, $oVersion->sTemplate, $oVersion->sModType);
    unset($oVersion);
}

$oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);

// Init form variables of select box
$oVersion->setVarForm('action', '');
$oVersion->setVarForm('area', $area);
$oVersion->setVarForm('frame', $frame);
$oVersion->setVarForm('idmod', $idmod);

// Create and output the select box
$sSelectBox = $oVersion->buildSelectBox(
    'mod_history', i18n('Module History'),
    i18n('Show history entry'), 'idmodhistory', $readOnly
);

// Generate form
$oForm = new cGuiTableForm('mod_display');
$oForm->addTableClass('col_flx_m_50p');
$oForm->setTableID('mod_history');
$oForm->setHeader(i18n('Edit module') . ' &quot;'. conHtmlSpecialChars($module->get('name')). '&quot;');
$oForm->setVar('area', 'mod_history');
$oForm->setVar('frame', $frame);
$oForm->setVar('idmod', $idmod);
$oForm->setVar('mod_send', 1);

// if send form refresh
if ($requestIdModHistory != '') {
    $sRevision = $requestIdModHistory;
} else {
    $sRevision = $oVersion->getLastRevision();
}

if ($sRevision != '' && ($requestAction != 'history_truncate' || $readOnly)) {
    // File Path
    $sPath = $oVersion->getFilePath() . $sRevision;

    // Read XML nodes and get an array
    $aNodes = [];
    $aNodes = $oVersion->initXmlReader($sPath);

    if (count($aNodes) > 1) {
        //    if choose xml file read value an set it
        $sName = $oVersion->getTextBox('modname', cString::stripSlashes(conHtmlentities(conHtmlSpecialChars($aNodes['name']))), 60, $readOnly);
        $description = $oVersion->getTextarea('moddesc', cString::stripSlashes(conHtmlSpecialChars($aNodes['desc'])), 100, 10, '', $readOnly);
        $sCodeInput = $oVersion->getTextarea('CodeIn', $aNodes['code_input'], 100, 30, 'IdCodeIn');
        $sCodeOutput = $oVersion->getTextarea('CodeOut', $aNodes['code_output'], 100, 30, 'IdCodeOut');
    }
}

if ($sSelectBox != '') {
    // Add new elements of form
    $oForm->add(i18n('Name'), $sName);
    $oForm->add(i18n('Description'), $description);
    $oForm->add(i18n('Code input'), $sCodeInput);
    $oForm->add(i18n('Code output'), $sCodeOutput);
    $oForm->setActionButton('apply', 'images/but_ok' . ($readOnly ? '_off' : '') . '.gif', i18n('Copy to current'), 'c'/* , 'mod_history_takeover' */);
    $oForm->unsetActionButton('submit');

    // Render and handle history area
    $oCodeMirrorIn = new CodeMirror('IdCodeIn', 'php', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$bInUse);
    $oCodeMirrorOutput = new CodeMirror('IdCodeOut', 'php', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), false, $cfg, !$bInUse);
    if ($readOnly) {
        $oCodeMirrorIn->setProperty('readOnly', 'true');
        $oCodeMirrorOutput->setProperty('readOnly', 'true');
    }

    $oPage->addScript($oCodeMirrorIn->renderScript());
    $oPage->addScript($oCodeMirrorOutput->renderScript());

    $oPage->set('s', 'FORM', $sSelectBox . $oForm->render());
} else {
    if ($bDeleteFile) {
        $oPage->displayOk(i18n('Version history was cleared'));
    } else {
        $oPage->displayWarning(i18n('No module history available'));
    }

    $oPage->abortRendering();
}

$oPage->render();
