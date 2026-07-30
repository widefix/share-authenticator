<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'email',
    'generation',
    'code_hash',
    'attempts',
    'expires_at',
    'sent_at',
    'delivery_failed_at',
])]
class EmailLoginCode extends Model
{
    protected $primaryKey = 'email';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivery_failed_at' => 'datetime',
        ];
    }
}
