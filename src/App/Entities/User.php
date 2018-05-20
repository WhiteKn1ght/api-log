<?php
namespace App\Entities;

/**
 *
 * @author rusakov.vv
 *
 */
class User extends \ArrayObject
{

    /** @var Int  */
    protected $id;

    /** @var String  */
    protected $name;

    /** @var String  */
    protected $city;

    /** @var String  */
    protected $country;

    /** @var String  */
    protected $currency;

    /** @var Float  */
    protected $amount;

    protected $totalSub, $totalUsdSub;

    protected $totalAdd, $totalUsdAdd;

    protected $requiredParamsForCreate = [
        'name', 'city', 'country', 'currency'
    ];

    public function __construct($data = []) {
        foreach ($data as $k => $v) {
            $k = trim(strtolower($k));
            $this->{$k} = $v;
        }
    }

    public function readyForInsert()
    {
        foreach ($this->requiredParamsForCreate as $param) {
            if(empty($this->{$param})) {
                return false;
            }
        }
        return true;
    }

    public function getId()
    {
        return (int) $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getAmount()
    {
        return round($this->amount, 4);
    }

    public function getTotalAdd()
    {
        return $this->totalAdd;
    }

    public function getTotalUsdAdd()
    {
        return $this->totalUsdAdd;
    }

    public function getTotalSub()
    {
        return $this->totalSub;
    }

    public function getTotalUsdSub()
    {
        return $this->totalUsdSub;
    }


    public function totalAdd($sum)
    {
        $this->totalAdd += $sum;
    }

    public function totalSub($sum)
    {
        $this->totalSub += $sum;
    }

    public function totalUsdSub($amount)
    {
        $this->totalUsdSub = $amount;
    }

    public function totalUsdAdd($amount)
    {
        $this->totalUsdAdd = $amount;
    }
}

