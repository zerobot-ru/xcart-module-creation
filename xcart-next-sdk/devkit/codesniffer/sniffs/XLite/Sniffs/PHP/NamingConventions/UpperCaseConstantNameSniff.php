<?php
/**
 * XLite_Sniffs_PHP_NamingConventions_UpperCaseConstantNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: a56efe30696942990896a6867b28e38fa76818c1 $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * XLite_Sniffs_PHP_NamingConventions_UpperCaseConstantNameSniff.
 *
 * Ensures that constant names are all uppercase.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.2.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class XLite_Sniffs_PHP_NamingConventions_UpperCaseConstantNameSniff extends XLite_ReqCodesSniff
{

	protected $predefinedConstants = array(
		'DIRECTORY_SEPARATOR',
		'__DIR__', '__FILE__', '__CLASS__',
		'E_ALL', 'E_STRICT', 'E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_CORE_ERROR', 'E_CORE_WARNING',
		'E_COMPILE_ERROR', 'E_COMPILE_WARNING', 'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE', 'E_RECOVERABLE_ERROR',
		'E_DEPRECATED', 'E_USER_DEPRECATED',
		'CURLOPT_URL', 'CURLOPT_HEADER', 'CURLOPT_HTTPHEADER', 'CURLOPT_HTTPGET', 'CURLOPT_POSTFIELDS', 'CURLOPT_RETURNTRANSFER',
		'CURLOPT_TIMEOUT', 'CURLOPT_POST', 'CURLOPT_SSLCERT', 'CURLOPT_SSLCERTPASSWD', 'CURLOPT_SSLKEY', 'CURLOPT_SSLKEYPASSWD',
		'CURLOPT_PROXYTYPE', 'CURLPROXY_HTTP', 'CURLOPT_PROXY', 'CURLOPT_HEADERFUNCTION', 'CURLAUTH_BASIC', 'CURLAUTH_DIGEST',
		'CURLAUTH_GSSNEGOTIATE', 'CURLAUTH_NTLM', 'CURLAUTH_ANY', 'CURLAUTH_ANYSAFE', 'CURLOPT_HTTPAUTH', 'CURLOPT_USERPWD',
		'CURLOPT_SSL_VERIFYPEER', 'CURLOPT_SSL_VERIFYHOST', 'CURLOPT_CAINFO', 'CURLOPT_NOBODY', 'CURLOPT_CUSTOMREQUEST',
		'CURLOPT_PORT', 'CURLOPT_FOLLOWLOCATION', 'CURLOPT_SSLVERSION', 'CURLOPT_REFERER',
		'MCRYPT_RAND',
		'XML_TEXT_NODE', 'XML_COMMENT_NODE',
		'GLOB_MARK',
		'PREG_GREP_INVERT',
		'ENT_NOQUOTES', 'ENT_COMPAT', 'ENT_QUOTES',
		'LIBXML_DTDLOAD', 'LIBXML_NOERROR', 'LIBXML_NOWARNING',
		'LOG_ERR', 'LOG_WARNING', 'LOG_DEBUG', 'PEAR_LOG_ERR', 'PEAR_LOG_WARNING', 'PEAR_LOG_NOTICE', 'PEAR_LOG_CRIT',
		'PEAR_LOG_INFO', 'PHP_EOL', 'PHP_SAPI', 'PHP_VERSION', 'PHP_OS', 'INFO_MODULES',
		'PREG_SPLIT_NO_EMPTY', 'XDEBUG_TRACE_COMPUTERIZED', 'FILTER_VALIDATE_EMAIL', 'FILTER_VALIDATE_IP', 'FILTER_FLAG_IPV4',
		'FILTER_VALIDATE_REGEXP', 'FILTER_REQUIRE_ARRAY', 'FILTER_VALIDATE_INT', 'FILTER_SANITIZE_STRING',
		'FILTER_VALIDATE_FLOAT', 'FILTER_SANITIZE_URL', 'HTML_ENTITIES', 'FILTER_VALIDATE_BOOLEAN', 'FILTER_SANITIZE_NUMBER_FLOAT',
        'FILTER_FLAG_ALLOW_FRACTION',
        'PATHINFO_EXTENSION', 'UPLOAD_ERR_OK', 'FILE_APPEND', 'UPLOAD_ERR_EXTENSION', 'UPLOAD_ERR_CANT_WRITE', 'UPLOAD_ERR_NO_TMP_DIR',
        'UPLOAD_ERR_NO_FILE', 'UPLOAD_ERR_PARTIAL', 'UPLOAD_ERR_FORM_SIZE', 'UPLOAD_ERR_INI_SIZE',
        'CLI_RESULT_CODE',
        
        'SORT_NUMERIC', 'SEEK_END',
        'T_FINAL', 'T_ABSTRACT',
        // Defined in Drupal:
        'MENU_VISIBLE_IN_BREADCRUMB',
        'USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL', 'USER_REGISTER_VISITORS', 'CSS_DEFAULT', 'JS_DEFAULT',
        'MENU_LOCAL_TASK', 'MENU_NORMAL_ITEM', 'MENU_CALLBACK',
	);

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {

        $tokens    = $phpcsFile->getTokens();
        $constName = $tokens[$stackPtr]['content'];

        // If this token is in a heredoc, ignore it.
        if ($phpcsFile->hasCondition($stackPtr, T_START_HEREDOC) === true) {
            return;
        }

        // If the next non-whitespace token after this token
        // is not an opening parenthesis then it is not a function call.
        $openBracket = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
        if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
            $functionKeyword = $phpcsFile->findPrevious(array(T_WHITESPACE, T_COMMA, T_COMMENT, T_STRING), ($stackPtr - 1), null, true);

            $declarations = array(
                             T_FUNCTION,
                             T_CLASS,
                             T_INTERFACE,
                             T_IMPLEMENTS,
                             T_EXTENDS,
                             T_INSTANCEOF,
                             T_NEW,
                            );
            if (in_array($tokens[$functionKeyword]['code'], $declarations) === true) {
                // This is just a declaration; no constants here.
                return;
            }

            if ($tokens[$functionKeyword]['code'] === T_CONST) {
                // This is a class constant - do not check
                return;
            }

			if (
				$tokens[$stackPtr - 1]['code'] == T_NS_SEPARATOR
				|| $tokens[$stackPtr + 1]['code'] == T_NS_SEPARATOR
				|| $tokens[$stackPtr - 2]['code'] == T_NAMESPACE
			) {
				// This is a namespace prefix
				return;
			}

            // Is this a class name?
            $nextPtr = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
            if ($tokens[$nextPtr]['code'] === T_DOUBLE_COLON) {
                return;
            }

            // Is this a class alias?
            $prevPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), ($stackPtr - 1), null, true);
            if ($tokens[$prevPtr]['code'] === T_AS) {
                return;
            }

            // Is this a type hint?
            if ($tokens[$nextPtr]['code'] === T_VARIABLE || $phpcsFile->isReference($nextPtr) === true) {
                return;
            }

            // Is this a member var name?
            $prevPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), ($stackPtr - 1), null, true);
            if ($tokens[$prevPtr]['code'] === T_OBJECT_OPERATOR) {
                return;
            }

            // Is this an instance of declare()
            $prevPtr = $phpcsFile->findPrevious(array(T_WHITESPACE, T_OPEN_PARENTHESIS), ($stackPtr - 1), null, true);
            if ($tokens[$prevPtr]['code'] === T_DECLARE) {
                return;
            }

			if ($tokens[$stackPtr - 1]['code'] == T_PAAMAYIM_NEKUDOTAYIM) {
				return;
			}

            // This is a real constant.
			$this->checkConstant($constName, $phpcsFile, $stackPtr);

        } else if (strtolower($constName) === 'define' || strtolower($constName) === 'constant') {

            /*
                This may be a "define" or "constant" function call.
            */

            // The next non-whitespace token must be the constant name.
            $constPtr = $phpcsFile->findNext(array(T_WHITESPACE), ($openBracket + 1), null, true);
            if ($tokens[$constPtr]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
                return;
            }

            $constName = substr($tokens[$constPtr]['content'], 1, -1);
            $this->checkConstant($constName, $phpcsFile, $stackPtr);
        }//end if

    }//end process()


	protected function checkConstant($constName, PHP_CodeSniffer_File $phpcsFile, $stackPtr) {

		if (in_array($constName, $this->predefinedConstants)) {
			return;
		}

		if (substr($constName, 0, 3) !== 'LC_') {
			$error = 'Constant ' . $constName . ' has no prefix LC_';
			$phpcsFile->addError($this->getReqPrefix('REQ.PHP.1.6.3') . $error, $stackPtr);
		}

		if (!preg_match('/^[A-Z0-9_]+$/Ss', $constName)) {
        	$error = 'Constants must be uppercase; expected '.strtoupper($constName)." but found $constName";
            $phpcsFile->addError($this->getReqPrefix('REQ.PHP.1.6.1') . $error, $stackPtr);
        }

	}

}//end class

?>
