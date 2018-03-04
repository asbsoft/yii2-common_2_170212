<?php

namespace asb\yii2\common_2_170212\behaviors;

use yii\base\Behavior;

/**
 * Behaviour describe what parameters from modules' params attribute can be change in parameters manager.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ParamsAccessBehaviour extends Behavior
{
    /**
     * @var string default role can edit every parameters which are not disable for edit
     */
    public $defaultRole = 'roleRoot';

    /**
     * @var array defines list of params available to edit by user(s) with such role(s)
     * in format [role => array of parameters].
     * Useful to add some permissions to user(s) without $defaultRole.
     * For such parameters $defaultRole will keep.
     * For example,
     * ```php
     *     'roleNewsAuthor' => ['latestNewsCounter', 'showTopNews'],
     * ```
     * Another parameters by default can edit only user with $this->defaultRole.
     */
    public $roleParams = [];

    /**
     * @var array defines exact list of roles for parameter(s)
     * in format [parameter => array of roles].
     * Useful to disable $defaultRole by overwriting ne role list for parameter
     * For example,
     * ```php
     *     'enableDiag' => ['roleRoot'], // ! overwrite rotes for param, disable $defaultRole to this parameter
     *     'adminPath'  => [], // disable parameter for everybody, same as 'readonlyParams' => ['adminPath', ...]
     * ]
     * ```
     * Another parameters by default can edit only user with $this->defaultRole.
     */
    public $paramRoles = [];

    /**
     * @var array defines list of params unavailable to edit
     * For example,
     * ```php
     *     ['behaviors', 'uploadDir'] // parameters which nobody can edit, will overwrite another rules here
     * ```
     */
    public $readonlyParams = [];

    /**
     * @var array list of restricted parameters in format [parameter => array of roles]
     * For parameters which are not enumerated here mean default roles list: [$this->defaultRole]
     */
    protected $_restrictedParams = [];

    /**
     * @inheritdoc
     * Create list of restricted parameters.
     */
    public function init()
    {
        parent::init();

        $this->_restrictedParams = [];

        // add roles
        foreach ($this->roleParams as $role => $params) {
            foreach ($params as $param) {
                if (empty($this->_restrictedParams[$param])) {
                    $this->_restrictedParams[$param] = [$this->defaultRole]; // add default role
                }
                array_push($this->_restrictedParams[$param], $role);
            }
        }

        // overwrite roles
        foreach ($this->paramRoles as $param => $roles) {
            $this->_restrictedParams[$param] = $roles;
        }
        
        // clean roles for read-only param(s)
        if (isset($this->readonlyParams)) {
            foreach ($this->readonlyParams as $param) {
                $this->_restrictedParams[$param] = [];
            }
        }
    }

    /**
     * @return array of roles
     */
    public function rolesForParam($param)
    {
        return isset($this->_restrictedParams[$param]) ? $this->_restrictedParams[$param] : [$this->defaultRole];
    }

    /**
     * Check if user with $role can edit $param
     * @param string $role
     * @param string $param
     * @return boolean
     */
    public function canUserEditParam($role, $param)
    {
        $result = false;
        if (isset($this->_restrictedParams[$param])) {
            $rolesList = $this->_restrictedParams[$param];
            if (in_array($role, $rolesList)) {
                $result = true;
            }
        } else if ($role === $this->defaultRole) {
            $result = true;
        }
        return $result;
    }

}
