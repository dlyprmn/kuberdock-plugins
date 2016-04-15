<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use Exception;
use base\CL_Model;

class CL_Server extends CL_Model
{
    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblservers';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function decryptPassword()
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['password2'] = $this->password;

        $results = localAPI('decryptpassword', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'];
    }

    /**
     * @param $password
     * @return string
     * @throws Exception
     */
    public function encryptPassword($password)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['password2'] = $password;

        $results = localAPI('encryptpassword', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'];
    }
} 