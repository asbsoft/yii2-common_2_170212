<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\controllers\BaseController;
use asb\yii2\common_2_170212\base\UniModule;

use yii\web\View;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

/**
 * Common basic view.
 * Help to find view-templates from parent module(s).
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UniView extends View
{
    const TMPL_RENDER_PARENT = "<!-- parentBlock='%s'@\|%s\| -->";
    
    /** Blocks stack */
    protected static $_blocksStack = [];

    /** Indicate regime of startParent: array(viewFile => boolean) */
    protected $_extendingParent = [];

    /** Current processing ancestor view file for $this->viewFile template: array(viewFile => file) */
    protected $_currentViewFile = [];
    
    /** Saved parameters for rendering files */
    protected $_viewsParams = [];

    /**
     * @var UniViewBlocks saved blocks: array(viewFile => array())
     */
    protected $_viewBlocks = [];

    /** Additional data from inherited view-file: array(viewFile => array() */
    protected $_addParentData = [];

    /**
     * @inheritdoc
     */
    public function render($view, $params = [], $context = null)
    {
        // Save context
        $_extendingParent = $this->_extendingParent;
        $_currentViewFile = $this->_currentViewFile;
        $_viewsParams     = $this->_viewsParams;
        $_viewBlocks      = $this->_viewBlocks;
        $_addParentData   = $this->_addParentData;

        $result = parent::render($view, $params, $context);

        // Restore context
        $this->_extendingParent = $_extendingParent;
        $this->_currentViewFile = $_currentViewFile;
        $this->_viewsParams     = $_viewsParams;
        $this->_viewBlocks      = $_viewBlocks;
        $this->_addParentData   = $_addParentData;

        return $result;
    }

    /**
     * @inheritdoc
     * Save paremeters for render views files
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        $this->_viewsParams[$viewFile] = $params;
        return parent::renderFile($viewFile, $params, $context);
    }

    /**
     * Get current processing file for $this->viewFile
     */
    public function getProcessingViewFile($thisViewFile = null)
    {
        if(empty($thisViewFile)) {
            $thisViewFile = $this->viewFile;
        }
        if (!empty($this->_currentViewFile[$thisViewFile])) {
            return $this->_currentViewFile[$thisViewFile];
        } else {
            return $thisViewFile;
        }
    }
    
    /**
     *
     */
    public function viewBlocks($viewFile)
    {
        if (empty($this->_viewBlocks[$viewFile])) {
            $this->_viewBlocks[$viewFile] = new UniViewBlocks($viewFile);
        }
        return $this->_viewBlocks[$viewFile];
    }

    /**
     *
     */
    public function getLevel()
    {
        return count(static::$_blocksStack);
    }

    /**
     * Get current block short name
     * @return string
     */
    public function getCurrentBlockName()
    {
        if (empty(static::$_blocksStack)) {
            return '';
        } else {
            return static::$_blocksStack[count(static::$_blocksStack) - 1];
        }
    }

    /**
     * Get current block full name (with parents names)
     * @return string
     */
    public function getCurrentBlockFullName()
    {
        $fullBlockName = '/' . implode('/', static::$_blocksStack);
        return $fullBlockName;
    }

    /**
     * Start block
     */
    public function startBlock($name)
    {
        array_push(static::$_blocksStack, $name);
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Stop block with save or echo.
     */
    public function stopBlock($name = null)
    {
        if (empty($this->currentBlockName)) {
            throw new InvalidParamException(
                "Unexpected stopBlock in '{$this->processingViewFile}'"
            );
        }
        if (!empty($name) && $this->currentBlockName != $name) {
            throw new InvalidParamException(
                "Expected current closed block name '{$this->currentBlockName}' instead of '{$name}'"
              . " in '{$this->processingViewFile}'"
            );
        }
        $name = $this->currentBlockName;

        $fullBlockName = $this->getCurrentBlockFullName();

        array_pop(static::$_blocksStack);

        $content = ob_get_clean();

        // save every block of every view-file in inherit chain (not only for last) - it will need for render parentBlock
        if (!$this->viewBlocks($this->processingViewFile)->exists($fullBlockName)) {
            $this->viewBlocks($this->processingViewFile)->add($fullBlockName, $content);
        }

        $savedContent = $this->findProperBlock($fullBlockName, $this->processingViewFile);
        if ($savedContent !== false) {
            $content = $savedContent; // rewrite
        }
        if ($savedContent !== false && empty($this->_extendingParent[$this->processingViewFile])) {
            // such block already saved - replace block in parent view-template by saved content
            $n = $this->hasParentBlock($savedContent);
            while ($n--) {
                $savedContent = $this->replaceParentBlock($savedContent);
            }
            echo $savedContent;
        } else {
            echo $content; // always echo - to catch by outer block
        }
    }
    
    /**
     * Represent parent block in view file.
     */
    public function parentBlock()
    {
        echo sprintf(stripslashes(self::TMPL_RENDER_PARENT), $this->currentBlockFullName, $this->processingViewFile);
    }

    /**
     * Check if in block exist tag(s) with parentBlock info.
     * @param string $savedContent
     * @return integer number of found fragments 0 if not found
     */
    protected function hasParentBlock($savedContent)
    {
       $pattern = sprintf(self::TMPL_RENDER_PARENT, "([^']+)", "([^|]+)");
       return preg_match_all("/{$pattern}/", $savedContent);
    }

    /**
     * Replace in block tag with parentBlock info.
     * @param string $savedContent
     * @return string
     */
    protected function replaceParentBlock($savedContent)
    {
       $pattern = sprintf(self::TMPL_RENDER_PARENT, "([^']+)", "([^|]+)");
       $result = preg_match("/{$pattern}/", $savedContent, $matches);
       if (!$result) {
           return $savedContent;
       } else { // processing parentBlock()
           $fullBlockName = $matches[1];
           $viewFileName = $matches[2];
           $replacement = $this->findParentBlock($fullBlockName, $viewFileName);
           $out = preg_replace("/{$pattern}/", $replacement, $savedContent);
           return $out;
       }
    }

    /**
     * Find parent block.
     * @param string $fullBlockName name of block - name of current block where parentBlock() occured
     * @return string block body or empty string if not found
     */
    protected function findParentBlock($fullBlockName, $viewFileName)
    {
        $parentViewFile = $this->findParentViewFile($viewFileName);
        $viewFilesChain = $this->getViewFilesChain($parentViewFile);
        $savedContent = '';
        foreach ($viewFilesChain as $viewFile) {
            if ($this->viewBlocks($viewFile)->exists($fullBlockName)) {
                $savedContent = $this->viewBlocks($viewFile)->get($fullBlockName);
                break;
            }
        }
        return $savedContent;
    }

    /**
     * @return string|false if not found
     */
    protected function findProperBlock($fullBlockName, $processingViewFile)
    {
        $list = $this->getViewFilesChain(); // full list from $this->viewFile
        $viewFilesChain = [];
        foreach ($list as $file) {
            $viewFilesChain[] = $file;
            if ($file == $processingViewFile) {
                break;
            }
        }

        $savedContent = false;
        foreach ($viewFilesChain as $viewFile) {
            if ($this->viewBlocks($viewFile)->exists($fullBlockName)) {
                $savedContent = $this->viewBlocks($viewFile)->get($fullBlockName);
                break;
            }
        }
        return $savedContent;
    }

    /**
     * Begin extend parent view file.
     * @param array $addParentData additional data from inherited view-file
     */
    public function startParent($addParentData = [])
    {
        if (!empty($this->_extendingParent[$this->processingViewFile])) {
            throw new InvalidParamException("Nested startParent() impossible in '{$this->processingViewFile}'");
        }
        $this->_extendingParent[$this->processingViewFile] = true;

        $this->_addParentData[$this->processingViewFile] = $addParentData;

        ob_start(); // for lost all non-block info between startParent...stopParent
        ob_implicit_flush(false);
    }

    /**
     * End extend parent view file.
     */
    public function stopParent()
    {
        if (!empty($this->currentBlockName)) {
            throw new InvalidParamException("Unclosed startBlock detected in '{$this->processingViewFile}'");
        }
        if (empty($this->_extendingParent[$this->processingViewFile])) {
            throw new InvalidParamException("stopParent unexpected here without startParent in '{$this->processingViewFile}'");
        }
        $this->_extendingParent[$this->processingViewFile] = false;

        $lost = ob_get_clean(); // lost all non-block info between startParent...stopParent
        
        // render parent view with blocks inclusions
        $parentFile = $this->findParentViewFile($this->processingViewFile);
        if ($parentFile) {
            $this->_currentViewFile[$this->viewFile] = $parentFile;

            $parentDataFile = $this->viewFile;
            $params = empty($this->_viewsParams[$parentDataFile]) ? [] : $this->_viewsParams[$parentDataFile];
            if (!empty($this->_addParentData[$parentDataFile])) {
                $addParentData = $this->_addParentData[$parentDataFile];
                $params = ArrayHelper::merge($params, $addParentData);
            }
            $result = $this->renderPhpFile($parentFile, $params);
            $n = $this->hasParentBlock($result);
            while ($n--) {
                $result = $this->replaceParentBlock($result);
            }
            if ($this->hasParentBlock($result)) {
                throw new InvalidParamException("Unprocessed nested parentBlock found in '{$this->processingViewFile}'");
            }            
            echo $result;
        }
    }

    /**
     * Find "parent" (precursor) view file.
     * @return string|null view file path
     */
    protected function findParentViewFile($currentViewFile = null)
    {
        if ($currentViewFile == null) {
            $currentViewFile = $this->viewFile; // main (source) view file
        }
        $viewsChain = $this->getViewFilesChain($currentViewFile);
        array_shift($viewsChain);
        $parentFile = array_shift($viewsChain);
        return $parentFile;
    }

    /**
     * Get view files chain from $currentViewFile (or $this->viewFile).
     * @param string $currentViewFile
     * @return array of view file path
     */
    protected function getViewFilesChain($currentViewFile = null)
    {
        if ($currentViewFile == null) {
            $currentViewFile = $this->viewFile; // main (source) view file
        }
        $currentViewFile = realpath($currentViewFile);

        $viewsSubSubdir = $this->getViewsSubSubdir($currentViewFile);

        $viewsChain = [$currentViewFile];
        if ($this->context instanceof BaseController) {
            $module = $this->context->module;
            $pathList = $module->getBasePathList();
            $skip = true;
            foreach ($pathList as $path) {
                $baseViewSubdir = $path . DIRECTORY_SEPARATOR . $module::$viewsSubdir . DIRECTORY_SEPARATOR . $viewsSubSubdir;
                $file = $baseViewSubdir . DIRECTORY_SEPARATOR . basename($currentViewFile); //!! do not use '/' only DIRECTORY_SEPARATOR
                //$file = realpath($file); //!! don't use - if file not found return ''

                // skip view-template-files until $currentViewFile found, get next file
                if (is_file($file)) {
                    if (!$skip) {
                        $viewsChain[] = $file;
                    }
                    if ($file == $currentViewFile) {
                        $skip = false;
                    }
                }
            
            }
        }
        return $viewsChain;
    }

    /**
     * Get views sub subdir.
     * @param string $currentViewFile
     * @return string 
     * @throws InvalidParamException if $currentViewFile is out of this module parents path list
     */
    protected function getViewsSubSubdir($currentViewFile)
    {
        $viewsSubSubdir = false;
        $currentViewFile = realpath($currentViewFile);
        $currentViewPath = dirname($currentViewFile);

        $module = $this->context->module;

        $pathList = $module->getBasePathList();
        foreach ($pathList as $modulePath) {
            $viewsPath = realpath($modulePath) . DIRECTORY_SEPARATOR . $module::$viewsSubdir;
            if (strpos($currentViewPath, $viewsPath) === 0) {
                $viewsSubSubdir = substr($currentViewPath, strlen($viewsPath) + 1);
                return $viewsSubSubdir;
            }
        }
        throw new InvalidParamException(
            "Unexpected view file '{$currentViewFile}' is out of module's '{$module->className()}' parents chain"
        );
    }

    /**
     * @inheritdoc
     * Additional find views in parents views subdirs
     */
    protected function findViewFile($view, $context = null)
    {
        if (!empty($context->module) && $context->module instanceof UniModule && $context instanceof BaseController) {
            $module = $context->module;
            $pathList = $module->getBasePathList();
            foreach ($pathList as $path) {
                $viewPath = $path . DIRECTORY_SEPARATOR . $module::$viewsSubdir . DIRECTORY_SEPARATOR . $context->id;
                $file = $viewPath . DIRECTORY_SEPARATOR . ltrim($view, '/');
                if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
                    $path = $file;
                } else {
                    $path = $file . '.' . $this->defaultExtension;
                }
                if (is_file($path)) {
                    return $path;
                } elseif ($this->defaultExtension !== 'php') {
                    $path = $file . '.php';
                    if (is_file($path)) {
                        return $path;
                    }
                }
            }
        }

        $viewFile = parent::findViewFile($view, $context);
        return $viewFile;
    }

}
