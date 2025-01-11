<?php

namespace vendornamespace\Testpakage\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \vendornamespace\Testpakage\Testpakage
 */
class Testpakage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \vendornamespace\Testpakage\Testpakage::class;
    }
}
