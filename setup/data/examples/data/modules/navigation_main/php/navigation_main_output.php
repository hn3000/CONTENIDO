<?php

/**
 * description: main navigation
 *
 * @package Module
 * @subpackage NavigationMain
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get client settings
$rootIdcat = getEffectiveSetting('navigation_main', 'idcat', 1);
$depth = getEffectiveSetting('navigation_main', 'depth', 3);

// get category tree
$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth(cRegistry::getAuth());
$tree = $categoryHelper->getSubCategories($rootIdcat, $depth);

// get path (breadcrumb) of current category
function navigation_main_filter(cApiCategoryLanguage $categoryLanguage) {
    return $categoryLanguage->get('idcat');
}
$path = array_map('navigation_main_filter', $categoryHelper->getCategoryPath(cRegistry::getCategoryId(), 1));

// use template to display navigation
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('ulId', 'navigation');
$tpl->assign('tree', $tree);
$tpl->assign('path', $path);
$tpl->display('get.tpl');

?>