<?php
declare(strict_types = 1);
namespace App\Api;

use App\Db\Writer;
use App\Entities\User;
use App\Entities\Currency;

class Handler
{

    /** @var \ArrayObject  */
    private $config;

    /** @var Writer  */
    protected $dbW;

    public function __construct($config = [])
    {
        $this->config = $config;
        if (empty($this->config)) {
            throw new \Exception('Wrong config data');
        }

        $this->dbW = new Writer($this->config['db']['dsn'], $this->config['db']['username'], $this->config['db']['password']);
    }

    public function addUser()
    {
        $body = $this->getInput();
        if(empty($body)) {
            throw new \Exception('Wrong input data');
        }

        $user = new User($body);
        if ($user->readyForInsert() && in_array($user->getCurrency(), $this->config['allowCurrencies'])) {
            $data = [
                'userid' => $this->dbW->addUser($user)
            ];
            return $this->responseStatus(true, $data);
        }

        return $this->responseStatus(false);
    }

    public function addMoney()
    {
        $data = $this->getInput();
        if (empty($data) || empty($data->id) || ! in_array($data->currency, $this->config['allowCurrencies'])) {
            throw new \Exception('Wrong input data');
        }

        if(empty($data->amount)) {
            throw new \Exception('Wrong is empty');
        }

        $user = new User($this->dbW->getUserById($data->id));
        if (empty($user->getId())) {
            throw new \Exception('Wrong input data');
        }

        $amount = $this->prepareAmount($data->currency, $user->getCurrency(), $data->amount);

        #$this->dbw->lock('addMoney_1');

        if($this->dbW->addMoney($user->getId(), $amount)) {
            $data = [
                'userid' => $user->getId(),
                'startAmount' => $data->amount,
                'endAmount' => $amount,
                'startCurrency' => $data->currency,
                'endCurrency' => $user->getCurrency(),
                'totalUserAmount' => ($amount + $user->getAmount())
            ];
            $this->dbW->log($user->getId(), $amount, Writer::OPERATION_TYPE_ADD, $data);
            return $this->responseStatus(true, $data);
        }

        return $this->responseStatus(false);
    }

    public function transferMoney()
    {
        $data = $this->getInput();
        if (empty($data) || empty($data->userid) || empty($data->receiver) || ! in_array($data->currency, $this->config['allowCurrencies'])) {
            throw new \Exception('Wrong input data');
        }
        if($data->userid == $data->receiver) {
            throw new \Exception('Wrong. Same user');
        }
        if(empty($data->amount)) {
            throw new \Exception('Wrong is empty');
        }
        $user = new User($this->dbW->getUserById($data->userid));
        if (empty($user->getId())) {
            throw new \Exception('Wrong user');
        }

        $receiver = new User($this->dbW->getUserById($data->receiver));
        if (empty($receiver->getId())) {
            throw new \Exception('Wrong receiver');
        }
        $subAmount = $this->prepareAmount($data->currency, $user->getCurrency(), $data->amount);
        if ($user->getAmount() < $subAmount) {
            throw new \Exception('Not enought money');
        }
        $addAmount = $this->prepareAmount($data->currency, $receiver->getCurrency(), $data->amount);
        // $this->dbw->lock('transfer_1');
        try {
            $this->dbW->beginTransaction();
            $this->dbW->subMoney($user->getId(), $subAmount);
            $this->dbW->addMoney($receiver->getId(), $addAmount);
            $log = [
                'userid' => $user->getId(),
                'receiver' => $receiver->getId(),
                'startAmount' => $data->amount,
                'endAmount' => $subAmount,
                'startCurrency' => $data->currency,
                'endCurrency' => $user->getCurrency(),
                'totalUserAmount' => round($user->getAmount() - $subAmount, 4)
            ];
            $this->dbW->log($user->getId(), $subAmount, Writer::OPERATION_TYPE_TRANSFER_SUB, $log);
            $log = [
                'userid' => $receiver->getId(),
                'sender' => $user->getId(),
                'startAmount' => $data->amount,
                'endAmount' => $addAmount,
                'startCurrency' => $data->currency,
                'endCurrency' => $receiver->getCurrency(),
                'totalUserAmount' => round($receiver->getAmount() + $addAmount, 4)
            ];
            $this->dbW->log($receiver->getId(), $addAmount, Writer::OPERATION_TYPE_TRANSFER_ADD, $log);
            $this->dbW->commit();
        } catch (\Exception $e) {
            $this->dbW->rollBack();
            return $this->responseStatus(false, $data);
        }

        return $this->responseStatus(true, $data);
    }

    public function addCurrencies()
    {
        $data = $this->getInput();
        if (empty($data)) {
            throw new \Exception('Wrong input data');
        }
        $result = [];
        foreach ($data as $currency) {
            if (empty($currency->to) || empty($currency->rate)) {
                continue;
            }
            if (empty($currency->date)) {
                $currency->date = date("Y-m-d");
            }
            if (empty($currency->from)) {
                $currency->from = Currency::getMainCurrency();
            }

            $result[] = $currency;
            $this->dbW->addCurrency($currency->date, $currency->from, $currency->to, $currency->rate);
        }

        return $this->responseStatus(true, $result);
    }

    protected function prepareAmount($fromCurrency, $toCurrency, $amount)
    {
        if ($fromCurrency == $toCurrency) {
            return $amount;
        }
        if ($fromCurrency != Currency::getMainCurrency()) {
            $amount = Currency::convertFromCurrency($this->dbW->getCurrentRate($fromCurrency), $amount);
        }
        if ($toCurrency != Currency::getMainCurrency()) {
            $amount = Currency::convertCurrency($this->dbW->getCurrentRate($toCurrency), $amount);
        }

        return $amount;
    }

    protected function getInput()
    {
        $data = file_get_contents('php://input');
        return json_decode($data);
    }

    protected function responseStatus($status = true, $data = []): array
    {
        echo json_encode([
            'status' => $status ? 'success' : 'fail',
            'response' => $data
        ]);
    }

}
