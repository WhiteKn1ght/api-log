<?php
namespace App\Entities;

/**
 *
 * @author rusakov.vv
 *
 */
class Currency extends \ArrayObject
{
    const ACCURACY = 10000;

    const MAIN_CURRENCY = 'USD';

    /** @var String  */
    protected $from;

    /** @var String  */
    protected $to;

    /** @var Float  */
    protected $amount;


    public static function convertFromCurrency($rate, $amount)
    {
        return round($amount * (1 / $rate), 4);
    }
    public static function convertCurrency($rate, $amount )
    {
        return round($amount * $rate, 4);
    }

    public static function getMainCurrency()
    {
        return self::MAIN_CURRENCY;
    }

}

