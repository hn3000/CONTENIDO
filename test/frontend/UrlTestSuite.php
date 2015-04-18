<?php
/**
 * This file contains the TestSuite for uri.
 *
 * @package          Testing
 * @subpackage       Test_Url
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

require_once(dirname(dirname(__FILE__)) . '/bootstrap.php');
require_once(dirname(__FILE__) . '/Url/Contenido_Url.php');

/**
 * Testsuite for Contenido_Url related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit UrlTestSuite
 *
 * @package          Testing
 * @subpackage       Test_Url
 */
class ContenidoUrlAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Contenido Url');
        $suite->addTestSuite('cUriTest');
        return $suite;
    }

}
