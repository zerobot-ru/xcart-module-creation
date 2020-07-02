<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * LiteCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to licensing@litecommerce.com so we can send you a copy immediately.
 *
 * PHP version 5.3.0
 *
 * @category  LiteCommerce
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2011-2012 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.litecommerce.com/
 */

define('MACRO_START_DIR', getcwd());

if (PHP_SAPI != 'cli') {
    macro_error('Server API must be CLI!');
}

if (!defined('MACRO_NO_XCN_CORE')) {
    $dir = MACRO_START_DIR;
    do {
	    if (file_exists($dir . DIRECTORY_SEPARATOR . 'top.inc.php')) {
		    define('XCN_MACROS_ROOT', $dir);
    		break;

        } elseif (file_exists($dir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'top.inc.php')) {
            define('XCN_MACROS_ROOT', $dir . DIRECTORY_SEPARATOR . 'src');
            break;

	    } else {
    		$tmp = realpath($dir . DIRECTORY_SEPARATOR . '..');
	    	$dir = $tmp == $dir ? null : $tmp;
    	}

    } while ($dir);

    if (!defined('XCN_MACROS_ROOT')) {
	    print 'top.inc.php not found!' . PHP_EOL;
    	die(1);
    }

    require_once XCN_MACROS_ROOT . DIRECTORY_SEPARATOR . 'top.inc.php';
    if (file_exists(XCN_MACROS_ROOT . DIRECTORY_SEPARATOR . 'top.inc.additional.php')) {
        require_once XCN_MACROS_ROOT . DIRECTORY_SEPARATOR . 'top.inc.additional.php';
    }
}

array_shift($_SERVER['argv']);

$options = getopt('h', array('help::'));

if (isset($options['h']) || isset($options['help'])) {
    echo macro_help() . PHP_EOL;
    die(0);
}

/**
 * Functions
 */

/**
 * Display error
 *
 * @param string $msg Error message
 *
 * @return void
 */
function macro_error($msg)
{
    echo 'Error: ' . $msg . PHP_EOL;
    die(1);
}

/**
 * Get script argument by name
 *
 * @param string $name Name
 *
 * @return string
 */
function macro_get_named_argument($name)
{
    $data = getopt('', array($name . '::'));

    return isset($data[$name]) ? $data[$name] : null;
}

/**
 * Get script argument by index
 *
 * @param integer $number Index
 *
 * @return string
 */
function macro_get_plain_argument($number)
{
    return isset($_SERVER['argv'][$number]) ? $_SERVER['argv'][$number] : null;
}

/**
 * Convert path to class name
 *
 * @param string $path Path
 *
 * @return string
 */
function macro_convert_path_to_class_name($path)
{
    if (!file_exists($path)) {
        if (!file_exists(dirname($path))) {
            \Includes\Utils\FileManager::mkdirRecursive(dirname($path));
        }
        file_put_contents($path, '');
    }

    return str_replace(DIRECTORY_SEPARATOR, '\\', substr(realpath($path), strlen(LC_DIR_CLASSES), -4));
}

/**
 * Convert class name to path
 *
 * @param string $class Class name
 *
 * @return string
 */
function macro_convert_class_name_to_path($class)
{
    return LC_DIR_CLASSES . str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')) . '.php';
}

/**
 * Safe write to file
 *
 * @param string $path Path
 * @param string $data File content
 *
 * @return void
 */
function macro_file_put_contents($path, $data)
{
    if (!\Includes\Utils\FileManager::mkdirRecursive(dirname($path))) {
        macro_error('Directory \'' . $path . '\' write-protected!');
    }

    if (!@file_put_contents($path, $data)) {
        macro_error('File \'' . $path . '\' write-protected!');
    }
}

/**
 * Chec path - path is entity class or not
 *
 * @param string $path path
 *
 * @return boolean
 */
function macro_is_entity($path)
{
    return preg_match('/XLite.Model.|XLite.Module.\w+.\w+.Model./Ss', $path);
}

/**
 * Assemble full class name
 *
 * @param string $suffix       Class short name
 * @param string $moduleAuthor Module author OPTIONAL
 * @param string $moduleName   Module name OPTIONAL
 *
 * @return string
 */
function macro_assemble_class_name($suffix, $moduleAuthor = null, $moduleName = null)
{
    return $moduleAuthor
        ? 'XLite\Module\\' . $moduleAuthor . '\\' . $moduleName . '\\' . $suffix
        : 'XLite\\' . $suffix;
}

/**
 * Assemble tempalte name
 *
 * @param string $suffix       Template short name
 * @param string $moduleAuthor Module author OPTIONAL
 * @param string $moduleName   Module name OPTIONAL
 *
 * @return string
 */
function macro_assemble_tpl_name($suffix, $moduleAuthor = null, $moduleName = null)
{
    return $moduleAuthor
        ? 'modules/' . $moduleAuthor . '/' . $moduleName . '/' . $suffix
        : $suffix;
}

/**
 * Get class short name
 *
 * @param string $class Class full name
 *
 * @return string
 */
function macro_get_class_short_name($class)
{
    $parts = explode('\\', $class);

    return array_pop($parts);
}

/**
 * Convert camel case string to human readable string
 *
 * @param string $camel Camel case string
 *
 * @return string
 */
function macro_convert_camel_to_human_readable($camel)
{
    $camel = str_replace('_', ' ', $camel);
    $camel = str_replace('\\', ' ', $camel);
    $camel = preg_replace_callback(
        '/ ([A-Z])([a-z0-9])/Ss',
        function (array $matches) {
            return ' ' . strtolower($matches[1]) . $matches[2];
        },
        $camel
    );
    $camel = preg_replace_callback(
        '/([a-z0-9])([A-Z])([a-z0-9])/Ss',
        function (array $matches) {
            return $matches[1] . ' ' . strtolower($matches[2]) . $matches[3];
        },
        $camel
    );

    return ucfirst($camel);
}

function macro_exec($cmd)
{
    $output = array();
    $result = 0;

    exec('/usr/bin/env ' . $cmd, $output, $result);

    return array($result, $output);
}

function macro_print_csv(array $data)
{
    $fp = fopen('php://output', 'w');
    foreach ($data as $row) {
        if (!is_array($row)) {
            $row = array($row);
        }
        fputcsv($fp, $row);
    }
    fclose($fp);
}

// {{{ Arguments checkers

/**
 * Check file path
 *
 * @param string &$path Path
 *
 * @return void
 */
function macro_check_file_path(&$path)
{
    if (!$path) {
        macro_error('\'file_path\' argument is empty!');

    } elseif (!file_exists($path)) {
        macro_error('Path \'' . $path . '\' not exists!');

    }

    $path = realpath($path);
}

/**
 * Check class repo file path
 *
 * @param string $path Path
 *
 * @return void
 */
function macro_check_class_file_path($path)
{
    macro_check_file_path($path);

    if (0 !== strcmp(LC_DIR_CLASSES, $path, strlen(LC_DIR_CLASSES))) {
        macro_error('Path \'' . $path . '\' is not LC class repository!!');
    }
}

/**
 * Check class full name
 *
 * @param string &$class Class
 *
 * @return void
 */
function macro_check_class(&$class)
{
    $class = ltrim($class, '\\');

    return \XLite\Core\Operator::isClassExists($class);
}

/**
 * Check module name
 *
 * @param string $author Author
 * @param string $module Name
 *
 * @return void
 */
function macro_check_module($author, $module)
{
    if (!$author) {
        macro_error('\'module_author\' argument is empty!');
    }

    if (!$module) {
        macro_error('\'module_name\' argument is empty!');
    }

}

// }}}

// {{{ Templates

/**
 * Get file header
 *
 * @param string $path Path
 *
 * @return string
 */
function macro_get_file_header($path)
{
    return <<<HEAD
<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * X-Cart
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the software license agreement
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.x-cart.com/license-agreement.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to licensing@x-cart.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade X-Cart to newer versions
 * in the future. If you wish to customize X-Cart for your needs please
 * refer to http://www.x-cart.com/ for more information.
 *
 * @category  X-Cart 5
 * @author    Qualiteam software Ltd <info@x-cart.com>
 * @copyright Copyright (c) 2011-2014 Qualiteam software Ltd <info@x-cart.com>. All rights reserved
 * @license   http://www.x-cart.com/license-agreement.html X-Cart 5 License Agreement
 * @link      http://www.x-cart.com/
 */


HEAD;
}

/**
 * Get class file repo header
 *
 * @param string $path Path
 *
 * @return string
 */
function macro_get_class_repo_header($path)
{
    $ns = explode('\\', macro_convert_path_to_class_name($path));
    array_pop($ns);
    $ns = implode('\\', $ns);

    return macro_get_file_header($path)
        . <<<HEAD
namespace $ns;


HEAD;
}

/**
 * Get class header
 *
 * @param string $path Path
 *
 * @return string
 */
function macro_get_class_header($path)
{
    $class = macro_convert_path_to_class_name($path);
    $reflection = new \ReflectionClass($class);
    $header = $reflection->getDocComment();

    if (!$header) {
        $name = $reflection->getShortName();
        $header = <<<HEAD
/**
 * Abstract class
 */
HEAD;
    }

    if (macro_is_entity($path)) {
        $header = preg_replace('/@Index\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@UniqueConstraint\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@MappedSuperclass/SsU', '', $header);
        $header = preg_replace('/@DiscriminatorMap\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@DiscriminatorColumn\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@InheritanceType\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@HasLifecycleCallbacks/SsU', '', $header);
        $header = preg_replace('/@Table\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@Entity\s*\(.+\)/SsU', '', $header);
        $header = preg_replace('/@Entity/SsU', '', $header);
    }

    $header = preg_replace('/@ListChild\s*\(.+\)/SsU', '', $header);

    $header = preg_replace('/( \*\s*.)+ \*\//SsU', ' */', $header);

    return $header;
}

// }}}

