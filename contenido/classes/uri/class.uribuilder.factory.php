<?php

/**
 * This file contains the uri builder factory class.
 *
 * @package    Core
 * @subpackage Frontend_URI
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Factory for retrieving required cUriBuilder object.
 *
 * @package    Core
 * @subpackage Frontend_URI
 */
class cUriBuilderFactory {

    /**
     * Returns desired cUriBuilder object.
     *
     * @param string $sBuilder
     *         For now, those are valid: front_content, custom, custom_path
     *         or a Userdefined cUriBuilder name.
     *         The name must be a subpart of the cUriBuilder class,
     *         e.g. 'MyUriBuilder' for cUriBuilderMyUriBuilder.
     *         The classfile must be named like class.uribuilder.myuribuilder.php
     *         and it must be reside in /contenido/classes/uri/ folder.
     * @return cUriBuilder
     * @throws cInvalidArgumentException
     *         In case unknown type of builder is requested you'll get an Exception
     */
    public static function getUriBuilder($sBuilder) {
        switch ($sBuilder) {
            case 'front_content':
                return cUriBuilderFrontcontent::getInstance();
                break;
            case 'custom':
                return cUriBuilderCustom::getInstance();
                break;
            case 'custom_path':
                return cUriBuilderCustomPath::getInstance();
                break;
            default:
                if ((string) $sBuilder !== '') {
                    $sClassName = 'cUriBuilder' . $sBuilder;

                    if (!class_exists($sClassName)) {
                        throw new cInvalidArgumentException('The classfile of cUriBuilder couldn\'t included by cUriBuilderFactory: ' . $sBuilder . '!');
                    }

                    return call_user_func([$sClassName, 'getInstance']);
                }

                throw new cInvalidArgumentException('Invalid/Empty cUriBuilder passed to cUriBuilderFactory: ' . $sBuilder . '!');
                break;
        }
    }

}
