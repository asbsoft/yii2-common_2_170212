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
    /** Default role can edit every parameters which did not describe in $this->roleParams */
    public $defaultRole = 'roleRoot';

    /**
     * @var array defines list of params available to edit by users with listed roles
     * in format [role => array of parameters].
     * Useful for disable edit read-only parameters and add some permissions if need.
     * For example,
     * ```php
     * [
     *   '' => ['behaviors', 'uploadDir'], // parameters which nobody can edit, will overwrite another rules
     *   'roleAdmin' => ['sizeListAdmin', 'sizeListFrontend'],
     *   'roleNewsAuthor' => ['latestNewsCounter', 'sizeListAdmin'],
     * ]
     * ```
     * Another parameters by default can edit only user with $this->defaultRole.
     */
    public $roleParams = [];

    /** List of restricted parameters in format [parameter => array of roles] */
    protected $_restrictedParams = [];

    /**
     * @inheritdoc
     * Create list of restricted parameters.
     */
    public function init()
    {
        parent::init();

        $this->_restrictedParams = [];
        foreach ($this->roleParams as $role => $params) {
            foreach ($params as $param) {
                if (empty($this->_restrictedParams[$param])) $this->_restrictedParams[$param] = [];
                array_push($this->_restrictedParams[$param], $role);
            }
        }
        if (isset($this->roleParams[''])) {
            foreach ($this->roleParams[''] as $param) { // overwrite
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
