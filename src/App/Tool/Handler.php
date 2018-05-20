<?php
declare(strict_types = 1);
namespace App\Tool;

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

    public function find()
    {

        $name = $_POST['name'] ?: false;
        $dateStart = $_POST['start'] ?: false;
        $dateEnd = $_POST['end'] ?: false;

        if (empty($name)) {
            return $this->responseView('list');
        }


        $user = new User($this->dbW->getUserByName($name));

        if (empty($user->getId())) {
            throw new \Exception('Wrong user');
        }
        #var_dump($name, $dateStart, $dateEnd); die;
        $log = $this->dbW->getLog($user->getId(), $dateStart, $dateEnd);

        foreach ($log as $row) {
            if(in_array($row->servicetype, [Writer::OPERATION_TYPE_ADD, Writer::OPERATION_TYPE_TRANSFER_ADD])) {
                $user->totalAdd($row->amount);
            }
            if(in_array($row->servicetype, [Writer::OPERATION_TYPE_SUB, Writer::OPERATION_TYPE_TRANSFER_SUB])) {
                $user->totalSub($row->amount);
            }
        }



        if($user->getCurrency() != Currency::getMainCurrency()) {
            $user->totalUsdAdd(Currency::convertFromCurrency($this->dbW->getCurrentRate($user->getCurrency()), $user->getTotalAdd()));
            $user->totalUsdSub(Currency::convertFromCurrency($this->dbW->getCurrentRate($user->getCurrency()), $user->getTotalSub()));
        } else {
            $user->totalUsdAdd($user->getTotalAdd());
            $user->totalUsdSub($user->getTotalSub());
        }

        return $this->responseView('list', ['user' => $user, 'list' => $log]);
    }

    public function save()
    {
        $name = $_REQUEST['name'] ?: false;
        $dateStart = $_REQUEST['start'] ?: false;
        $dateEnd = $_REQUEST['end'] ?: false;

        if (empty($name)) {
            return $this->responseView('list');
        }

        $user = new User($this->dbW->getUserByName($name));

        if (empty($user->getId())) {
            return $this->responseView('list');
        }

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$name . "_" .date("Y-m-d").'.csv";');

        #var_dump($name, $dateStart, $dateEnd); die;
        $log = $this->dbW->getLog($user->getId(), $dateStart, $dateEnd);
        $f = fopen('php://output', 'w');
        foreach ($log as $row) {
            unset($row->id);
            unset($row->userid);
            unset($row->data);
            $row->servicetype = Writer::$operationTranslate[$row->servicetype];
            $row->dateline = date("Y.m.d H:i:s", (int) $row->dateline);
            fputcsv($f, (array) $row, ';');
        }
        fclose($f);
        die;
    }

    protected function responseView($name, $data = [])
    {
        foreach ($data as $key => $value) {
            ${$key} = $value;
        }

        header('Content-Type: text/html; charset=utf-8', true);
        include_once (__DIR__ . '\\views\\' . $name . '.phtml');
    }


}
