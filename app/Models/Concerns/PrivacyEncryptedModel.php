<?php

namespace App\Models\Concerns;

use App\Libraries\PrivacyVault;

trait PrivacyEncryptedModel
{
    protected function privacyEncryptCallback(array $event): array
    {
        if (isset($event['data']) && is_array($event['data'])) {
            $event['data'] = (new PrivacyVault())->encryptRow($this->table, $event['data']);
        }
        return $event;
    }

    protected function privacyDecryptCallback(array $event): array
    {
        if (! array_key_exists('data', $event) || ! is_array($event['data']) || $event['data'] === []) {
            return $event;
        }
        $vault = new PrivacyVault();
        if (is_array($event['data']) && array_is_list($event['data'])) {
            $event['data'] = $vault->decryptRows($this->table, $event['data']);
        } elseif (is_array($event['data'])) {
            $event['data'] = $vault->decryptRow($this->table, $event['data']);
        }
        return $event;
    }
}
