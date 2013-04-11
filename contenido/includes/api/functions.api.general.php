<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO General API functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-09-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/* Info:
 * This file contains CONTENIDO General API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 */

/**
 * Includes a file and takes care of all path transformations.
 *
 * Example:
 * cInclude('classes', 'class.backend.php');
 *
 * Currently defined areas:
 *
 * frontend    Path to the *current* frontend
 * classes     Path to the CONTENIDO classes (see NOTE below)
 * cronjobs    Path to the cronjobs
 * external    Path to the external tools
 * includes    Path to the CONTENIDO includes
 * scripts     Path to the CONTENIDO scripts
 * module      Path to module
 *
 * NOTE: Since CONTENIDO (since v 4.9.0) provides autoloading of required
 *       class files, there is no need to load CONTENIDO class files of by using
 *       cInclude().
 *
 * @param   string  $sWhere       The area which should be included
 * @param   string  $sWhat        The filename of the include
 * @param   bool    $bForce       If true, force the file to be included
 * @param   string  $bReturnPath  Flag to return the path instead of including the file
 * @return  void
 */
function cInclude($sWhere, $sWhat, $bForce = false, $bReturnPath = false) {
    $backendPath = cRegistry::getBackendPath();
    global $client, $cfg, $cfgClient, $cCurrentModule;

    // Sanity check for $sWhat
    $sWhat  = trim($sWhat);
    $sWhere = strtolower($sWhere);
    $bError = false;

    switch ($sWhere) {
        case 'module':
            $handler = new cModuleHandler($cCurrentModule);
            $sInclude = $handler->getPhpPath() . $sWhat;
            break;
        case 'frontend':
            $sInclude = cRegistry::getFrontendPath() . $sWhat;
            break;
        case 'wysiwyg':
            $sInclude = $cfg['path']['wysiwyg'] . $sWhat;
            break;
        case 'all_wysiwyg':
            $sInclude = $cfg['path']['all_wysiwyg'] . $sWhat;
            break;
        case 'phplib':
            $sInclude = $cfg['path']['phplib'] . $sWhat;
            break;
        case 'classes':
            if (cAutoload::isAutoloadable($cfg['path'][$sWhere] . $sWhat)) {
                // The class file will be loaded automatically by the autoloader - get out here
                return;
            }
            $sInclude = $backendPath  . $cfg['path'][$sWhere] . $sWhat;
            break;
        default:
            $sInclude = $backendPath  . $cfg['path'][$sWhere] . $sWhat;
            break;
    }

    $sFoundPath = '';

    if ($sWhere === 'pear') {
        // now we check if the file is available in the include path
        $aPaths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($aPaths as $sPath) {
            if (@cFileHandler::exists($sPath . DIRECTORY_SEPARATOR . $sInclude)) {
                $sFoundPath = $sPath;
                break;
            }
        }

        if (!$sFoundPath) {
            $bError = true;
        }

        unset($aPaths, $sPath);
    } else {
        if (!cFileHandler::exists($sInclude) || preg_match('#^\.\./#', $sWhat)) {
            $bError = true;
        }
    }

    // should the path be returned?
    if ($bReturnPath) {
        if ($sFoundPath !== '') {
            $sInclude = $sFoundPath . DIRECTORY_SEPARATOR . $sInclude;
        }

        if (!$bError) {
            return $sInclude;
        } else {
            return false;
        }
    }

    if ($bError) {
        cError("Error: Can't include $sInclude", E_USER_ERROR);
        return false;
    }

    // now include the file
    if ($bForce == true) {
        return include($sInclude);
    } else {
        return include_once($sInclude);
    }

}

/**
 * Includes a file from a plugin and takes care of all path transformations.
 *
 * Example:
 * plugin_include('formedit', 'classes/class.formedit.php');
 *
 * @param   string  $sWhere  The name of the plugin
 * @param   string  $sWhat   The filename of the include
 * @return  void
 */
function plugin_include($sWhere, $sWhat) {
    global $cfg;

    $sInclude = cRegistry::getBackendPath() . $cfg['path']['plugins'] . $sWhere. '/' . $sWhat;

    include_once($sInclude);
}
?>