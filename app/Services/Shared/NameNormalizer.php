<?php

namespace App\Services\Shared;

use Illuminate\Support\Str;

final class NameNormalizer
{
    public function display(string $name): string
    {
        return Str::squish($name);
    }

    public function normalize(string $name): string
    {
        return Str::lower($this->display($name));
    }
}
