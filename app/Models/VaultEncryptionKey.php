<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vault_id', 'version', 'wrapped_key', 'nonce'])]
class VaultEncryptionKey extends Model
{
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
