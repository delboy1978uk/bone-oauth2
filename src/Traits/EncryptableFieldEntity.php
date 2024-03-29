<?php

namespace Bone\OAuth2\Traits;

use Laminas\Crypt\Password\Bcrypt;

trait EncryptableFieldEntity
{
    private Bcrypt $bcrypt;

    /**
     * @param $value
     * @return bool|string
     */
    protected function encryptField($value)
    {
        $this->bcrypt = new Bcrypt();
        $this->bcrypt->setCost(14);

        return $this->bcrypt->create($value);
    }

    /**
     * @param $encryptedValue
     * @param $value
     * @return bool
     */
    protected function verifyEncryptedFieldValue($encryptedValue, $value)
    {
        $this->bcrypt = new Bcrypt();
        $this->bcrypt->setCost(14);

        return $this->bcrypt->verify($value, $encryptedValue);
    }
}
