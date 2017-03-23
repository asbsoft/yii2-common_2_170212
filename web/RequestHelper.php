<?php

namespace asb\yii2\common_2_170212\web;

use yii\base\InvalidConfigException;

/**
 * Some usefule methods copied from yii\web\Request.
 */
class RequestHelper
{
    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string|boolean the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
     *
     * @see yii\web\Request::resolveRequestUri()
     */
    public static function resolveRequestUri()
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new \Exception('Unable to determine the request URI.');
        }

        return $requestUri;
    }

    /**
     * Returns the relative URL for the application.
     * This is similar to [[scriptUrl]] except that it does not include the script file name,
     * and the ending slashes are removed.
     * @return string the relative URL for the application
     * @see setScriptUrl()
     *
     * @see yii\web\Request::getBaseUrl()
     */
    public static function baseUrl()
    {
        return rtrim(dirname(static::scriptUrl()), '\\/');
    }

    /**
     * Returns the relative URL of the entry script.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string the relative URL of the entry script.
     * @throws InvalidConfigException if unable to determine the entry script URL
     *
     * @see yii\web\Request::getScriptUrl()
     */
    public static function scriptUrl()
    {
        $scriptFile = static::scriptFile();
        $scriptName = basename($scriptFile);
        if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
            $scriptUrl = $_SERVER['SCRIPT_NAME'];
        } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $scriptName) {
            $scriptUrl = $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
            $scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
        } elseif (isset($_SERVER['PHP_SELF']) && ($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
            $scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
        } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
        } else {
            throw new InvalidConfigException('Unable to determine the entry script URL.');
        }
        return $scriptUrl;
    }

    /**
     * Returns the entry script file path.
     * The default implementation will simply return `$_SERVER['SCRIPT_FILENAME']`.
     * @return string the entry script file path
     * @throws InvalidConfigException
     *
     * @see yii\web\Request::getScriptFile()
     */
    public static function scriptFile()
    {
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        } else {
            throw new InvalidConfigException('Unable to determine the entry script file path.');
        }
    }

}
