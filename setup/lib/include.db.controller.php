<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Creates/Updates the database tables and fills them with entries (depending on
 * selected options during setup process)
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2.5
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-02-24, Murat Purc, extended mysql extension detection
 *   modified 2011-02-28, Murat Purc, normalized setup startup process and some cleanup/formatting
 *   modified 2011-03-21, Murat Purc, usage of new db connection
 *   modified 2011-05-17, Ortwin Pinke, del sequencetable cfg, has to be set in connect-function
 *   modified 2011-01-11, rusmir jusufovic,
 *       - save input and output and translations strings from moduls in files
 *   modified 2011-06-20, Rusmir Jusufovic , save layout in filesystem
 *
 *   $Id: dbupdate.php 1656 2011-10-31 23:36:53Z xmurrix $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}

global $db;

checkAndInclude($cfg['path']['contenido'] . 'includes/functions.database.php');

$db = getSetupMySQLDBConnection(false);

if (checkMySQLDatabaseCreation($db, $_SESSION['dbname'])) {
    $db = getSetupMySQLDBConnection();
}

$currentStep = (isset($_GET['step']) && (int) $_GET['step'] > 0) ? (int) $_GET['step'] : 0;

if ($currentStep == 0) {
    $currentStep = 1;
}

$count = 0;
$fullCount = 0;

// Count DB Chunks
$file = fopen('data/tables.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == C_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'].'_'.$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->Errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullCount++;
}

// Count DB Chunks (plugins)
$file = fopen('data/tables_pi.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == C_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'].'_'.$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->Errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullCount++;
}

$pluginChunks = array();

$baseChunks = explode("\n", file_get_contents('data/base.txt'));

$clientChunks = explode("\n", file_get_contents('data/client.txt'));

$moduleChunks = explode("\n", file_get_contents('data/standard.txt'));

$contentChunks = explode("\n", file_get_contents('data/examples.txt'));

$sysadminChunk = explode("\n", file_get_contents('data/sysadmin.txt'));

if ($_SESSION['plugin_newsletter'] == 'true') {
    $newsletter = explode("\n", file_get_contents('data/plugin_newsletter.txt'));
    $pluginChunks = array_merge($pluginChunks, $newsletter);
}

if ($_SESSION['plugin_content_allocation'] == 'true') {
    $content_allocation = explode("\n", file_get_contents('data/plugin_content_allocation.txt'));
    $pluginChunks = array_merge($pluginChunks, $content_allocation);
}

if ($_SESSION['plugin_mod_rewrite'] == 'true') {
    $mod_rewrite = explode("\n", file_get_contents('data/plugin_mod_rewrite.txt'));
    $pluginChunks = array_merge($pluginChunks, $mod_rewrite);
}

if ($_SESSION['setuptype'] == 'setup') {
    switch ($_SESSION['clientmode']) {
        case 'CLIENT':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks);
            break;
        case 'CLIENTMODULES':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks, $moduleChunks);
            break;
        case 'CLIENTEXAMPLES':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks, $moduleChunks, $contentChunks);
            break;
        default:
            $fullChunks = array_merge($baseChunks, $sysadminChunk);
            break;
    }
} else {
    $fullChunks = $baseChunks;
}

$fullChunks = array_merge($fullChunks, $pluginChunks);


list($rootPath, $rootHttpPath) = getSystemDirectories();

$totalSteps = ceil($fullCount/C_SETUP_MAX_CHUNKS_PER_STEP) + count($fullChunks) + 1;
foreach ($fullChunks as $fullChunk) {
    $step++;
    if ($step == $currentStep) {
        $failedChunks = array();

        $replacements = array(
            '<!--{contenido_root}-->' => addslashes($rootPath),
            '<!--{contenido_web}-->' => addslashes($rootHttpPath)
        );

        injectSQL($db, $cfg['sql']['sqlprefix'], 'data/' . $fullChunk, $replacements, $failedChunks);

        if (count($failedChunks) > 0) {
            @$fp = fopen(C_FRONTEND_PATH . 'contenido/logs/setuplog.txt', 'w');
            foreach ($failedChunks as $failedChunk) {
                @fwrite($fp, sprintf("Setup was unable to execute SQL. MySQL-Error: %s, MySQL-Message: %s, SQL-Statements:\n%s", $failedChunk['errno'], $failedChunk['error'], $failedChunk['sql']));
            }
            @fclose($fp);

            $_SESSION['install_failedchunks'] = true;
        }
    }
}

$percent = intval((100 / $totalSteps) * ($currentStep));

echo '<script type="text/javascript">parent.updateProgressbar('.$percent.');</script>';

if ($currentStep < $totalSteps) {

    printf('<script type="text/javascript">function nextStep() { window.location.href="index.php?c=db&step=%s"; };</script>', $currentStep + 1);
    if (!C_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Next step</a>';
    }

} else {
    $sql = 'SHOW TABLES';
    $db->query($sql);

    // For import mod_history rows to versioning
    if ($_SESSION['setuptype'] == 'migration' || $_SESSION['setuptype'] == 'upgrade') {
        $cfgClient = array();
        rereadClients();

        $oVersion = new VersionImport($cfg, $cfgClient, $db, $client, $area, $frame);
        $oVersion->CreateHistoryVersion();
    }

    $tables = array();

    while ($db->next_record()) {
        $tables[] = $db->f(0);
    }

    foreach ($tables as $table) {
        dbUpdateSequence($cfg['sql']['sqlprefix'].'_sequence', $table, $db);
    }

    updateContenidoVersion($db, $cfg['tab']['system_prop'], C_SETUP_VERSION);
    updateSystemProperties($db, $cfg['tab']['system_prop']);

    if (isset($_SESSION['sysadminpass']) && $_SESSION['sysadminpass'] != '') {
        updateSysadminPassword($db, $cfg['tab']['phplib_auth_user_md5'], 'sysadmin');
    }

    $db->query('DELETE FROM %s', $cfg['tab']['code']);

    // As con_code has been emptied, force code creation (on update)
    $db->query('UPDATE %s SET createcode=1', $cfg['tab']['cat_art']);

    if ($_SESSION['setuptype'] == 'migration') {
        $aClients = listClients($db, $cfg['tab']['clients']);
        foreach ($aClients as $iIdClient => $aInfo) {
            updateClientPath($db, $cfg['tab']['clients'], $iIdClient, $_SESSION['frontendpath'][$iIdClient], $_SESSION['htmlpath'][$iIdClient]);
        }
    }

    // Set start compatible flag
    $_SESSION['start_compatible'] = false;
    if ($_SESSION['setuptype'] == 'upgrade') {
        $db->query('SELECT is_start FROM %s WHERE is_start=1', $cfg['tab']['cat_art']);
        if ($db->next_record()) {
            $_SESSION['start_compatible'] = true;
        }
    }

    // Update Keys
    $aNothing = array();

    injectSQL($db, $cfg['sql']['sqlprefix'], 'data/indexes.sql', array(), $aNothing);

    // Makes the new concept of moduls (save the moduls to the file) save the translation
    if ($_SESSION['setuptype'] == 'upgrade' || $_SESSION['setuptype'] == 'setup') {

        // @fixme  Get rid of hacks below
        // @fixme  Logic below works only for setup, not for upgrade because of different clients and languages

        global $client, $lang, $cfgClient;  // is used in LayoutInFile below!!!
        $clientBackup = $client;
        $langBackup = $lang;
        $client = 1;
        $lang = 1;

        rereadClients();

        Contenido_Vars::setVar('cfg', $cfg);
        Contenido_Vars::setVar('cfgClient', $cfgClient);
        Contenido_Vars::setVar('client', $client);
        Contenido_Vars::setVar('lang', $lang);
        Contenido_Vars::setVar('encoding', 'ISO-8859-1');
        Contenido_Vars::setVar('fileEncoding', 'UTF-8');
        Contenido_Vars::setVar('db', new DB_Contenido());

        // Save all modules from db-table to the filesystem
        $contenidoUpgradeJob = new Contenido_UpgradeJob();
        $contenidoUpgradeJob->saveAllModulsToTheFile($_SESSION['setuptype'], new DB_Contenido());

        // Save layout from db-table to the file system
        $layoutInFIle = new LayoutInFile(1, '', $cfg, 1);
        $layoutInFIle->upgrade();

        $client = $clientBackup;
        $lang = $langBackup;
        unset($clientBackup, $langBackup);
    }

    echo '
        <script type="text/javascript">
        parent.document.getElementById("installing").style.visibility="hidden";
        parent.document.getElementById("installingdone").style.visibility="visible";
        parent.document.getElementById("next").style.visibility="visible";
        function nextStep() {
            window.location.href="index.php?c=config";
        };
        </script>
    ';

    if (!C_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Last step</a>';
    }

}

?>