<?php

namespace asb\yii2\common_2_170212\models;

/**
 * For use in yii\db\Query, yii\db\ActiveQuery.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
trait QueryTrait
{
    /**
     * Check if join already exists.
     * Useful to prevent double joins.
     * @param string $aliasOrTable for alias is string without quotes, for table - in format '{{%tablename}}'
     * @return bool
     */
    public function hasJoin($aliasOrTable)
    {
        if ($this->join) {
            foreach ($this->join as $join) {
                $secondJoinParam = $join[1];
                if (is_array($secondJoinParam)) {
                    list($key, $val) = each($secondJoinParam);
                    if ($key == $aliasOrTable) {
                        return true;
                    }
                }
                if (is_string($secondJoinParam)) {
                    if ($secondJoinParam == $aliasOrTable) {
                        return true;
                    } else {
                        $pos = strrpos($secondJoinParam, ' ');
                        if ($pos) {
                            $alias = substr($secondJoinParam, $pos);
                            $alias = trim($alias, " \t`");
                            $table = substr($secondJoinParam, 0, $pos);
                            $table = trim($table);

                            if ($alias == $aliasOrTable || $table == $aliasOrTable) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

}
