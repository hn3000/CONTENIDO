<?php
/**
 * This file contains the upgrade job 8.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 8.
 * Copies the content of the con_plugins."path" column to the "folder" column
 * and deletes the "path" column afterwards.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0008 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    public function _execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            // check if the column "path" still exists
            $db->query("SHOW COLUMNS FROM `{$cfg[tab][plugins]}`;");

            $columns = array();
            while ($db->nextRecord()) {
                $columns[] = $db->f('Field');
            }

            if (in_array('path', $columns)) {
                // copy path to folder
                $db->query("UPDATE `{$cfg[tab][plugins]}` SET folder=path;");
                // drop column "path"
                $db->query("ALTER TABLE `{$cfg[tab][plugins]}` DROP `path`;");
            }
        }
    }

}