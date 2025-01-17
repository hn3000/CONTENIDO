<?php

/**
 * This file contains the category frontend logic class.
 *
 * @package    Plugin
 * @subpackage FrontendLogic
 * @author     Andreas Lindner
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Category frontend logic class.
 *
 * This "plugin" contains but a single class frontendlogic_category which
 * extends the core class FrontendLogic. Neither frontendlogic_category nor
 * FrontendLogic are used in the whole project and seem to be deprecated. Author
 * of frontendlogic_category was Andreas Lindner. The author of FrontendLogic is
 * not known.
 *
 * @package    Plugin
 * @subpackage FrontendLogic
 */
class frontendlogic_category extends FrontendLogic {

    /**
     * @inheritdoc
     */
    public function getFriendlyName() {
        return i18n("Category", "frontendlogic_category");
    }

    /**
     * @inheritdoc
     */
    public function listActions() {
        return [
            "access" => i18n("Access category", "frontendlogic_category")
        ];
    }

    /**
     * @inheritdoc
     * @throws cDbException
     */
    public function listItems() {
        $cfg = cRegistry::getConfig();
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());
        $db = cRegistry::getDb();

        $sSQL = "SELECT
                   b.idcatlang,
                   b.name,
                   c.level
                 FROM
                   " . cRegistry::getDbTableName('cat') . " AS a,
                   " . cRegistry::getDbTableName('cat_lang') . " AS b,
                   " . cRegistry::getDbTableName('cat_tree') . " AS c
                 WHERE
                   a.idcat = b.idcat AND
                   a.idcat = c.idcat AND
                   b.idlang = " . $lang . " AND
                   b.public = 0
                 ORDER BY c.idtree ASC";

        $db->query($sSQL);
        $items = [];
        while ($db->nextRecord()) {
            $items[$db->f("idcatlang")] = '<span style="padding-left: ' . ($db->f("level") * 10) . 'px;">' . htmldecode($db->f("name")) . '</span>';
        }

        return $items;
    }
}
