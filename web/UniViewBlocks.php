<?php

namespace asb\yii2\common_2_170212\web;

use yii\base\InvalidConfigException;

/**
 * Save blocks defined in views by $this->startBlock($blockName) ... $this->stopBlock().
 * Blocks can be nested that is reflected in block name 'outer/.../inner'.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class UniViewBlocks
{
    protected static $_blocks = [];

    protected $_viewName;

    /**
     * @param string $viewName
     */
    public function __construct($viewName)
    {
        $this->_viewName = $viewName;
        static::$_blocks[$this->_viewName] = [];
    }

    public function all()
    {
        return static::$_blocks;
    }

    /**
     * @param string $blockName
     * @param string $content
     */
    public function add($blockName, $content)
    {
        if (isset(static::$_blocks[$this->_viewName][$blockName])) {
            throw new InvalidConfigException("Block '{$blockName}' already defined in view '{$this->_viewName}'");
        } else {
            static::$_blocks[$this->_viewName][$blockName] = $content;
        }
    }

    /**
     * @param string $blockName
     */
    public function exists($blockName)
    {
        return isset(static::$_blocks[$this->_viewName][$blockName]);
    }

    /**
     * @param string $blockName
     */
    public function get($blockName)
    {
        if (isset(static::$_blocks[$this->_viewName][$blockName])) {
            return static::$_blocks[$this->_viewName][$blockName];
        } else {
            throw new InvalidConfigException("Block '{$blockName}' not found in view '{$this->_viewName}'");
        }
    }

    public function delete()
    {
        unset(static::$_blocks[$this->_viewName]);
    }

}
