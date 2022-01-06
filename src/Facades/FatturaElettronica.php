<?php

namespace StefanmcdsMnt\FatturaElettronica\Facades;

use Illuminate\Support\Facades\Facade;

class FatturaElettronica extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'fatturaelettronica';
    }
}
