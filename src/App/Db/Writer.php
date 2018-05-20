<?php
namespace App\Db;

use App\Entities\User;

/**
 *
 * @author rusakov.vv
 *
 */
class Writer extends \PDO
{

    const OPERATION_TYPE_ADD = 1;

    const OPERATION_TYPE_SUB = 2;

    const OPERATION_TYPE_TRANSFER_ADD = 3;

    const OPERATION_TYPE_TRANSFER_SUB = 4;

    public static $operationTranslate = [
        self::OPERATION_TYPE_ADD => 'Получение средств',
        self::OPERATION_TYPE_SUB => 'Списание средств',
        self::OPERATION_TYPE_TRANSFER_ADD => 'Получен перевод',
        self::OPERATION_TYPE_TRANSFER_SUB => 'Перевод средств',
    ];

    protected $dbname;

    public function __construct($dsn, $username = NULL, $password = NULL, $driver_options = array())
    {
        if (! isset($driver_options[\PDO::ATTR_ERRMODE])) {
            $driver_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        }

        if (preg_match('/.*dbname=(.*?);.*/i', $dsn, $matches)) {
            $this->dbname = $matches[1];
        }

        return parent::__construct($dsn, $username, $password, $driver_options);
    }

    public function getDbName()
    {
        return $this->dbname;
    }

    public function lock($name, $timeout = 1)
    {
        $timeout = intval($timeout);
        if ($timeout < 0) {
            $timeout = 1;
        }
        $sql = "SELECT GET_LOCK(:name, :timeout) as block";
        $stmt = $this->prepare($sql);
        $stmt->execute(array(
            ':name' => $name,
            ':timeout' => $timeout
        ));
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($res['block'] == 1);
    }

    public function unlock($name)
    {
        $sql = "SELECT RELEASE_LOCK(:name) as block";

        $stmt = $this->prepare($sql);
        $stmt->execute(array(
            ':name' => $name
        ));
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($res['block'] == 1);
    }

    public function addUser(User $user)
    {
        $stmt = $this->prepare("INSERT INTO users (name, country, city, currency, created) VALUES (:name, :country, :city, :currency, unix_timestamp())");
        $binds = [
            ':name' => $user->getName(),
            ':country' => $user->getCountry(),
            ':city' => $user->getCity(),
            ':currency' => $user->getCurrency()
        ];
        $stmt->execute($binds);
        return $this->lastInsertId();
    }

    public function getUserById(int $id)
    {
        $stmt = $this->prepare("SELECT * FROM users WHERE id = :id");
        $binds = [
            ':id' => $id
        ];
        $stmt->execute($binds);
        return $stmt->fetchObject();
    }

    public function getUserByName($name)
    {
        $stmt = $this->prepare("SELECT * FROM users WHERE name = :name");
        $binds = [
            ':name' => $name
        ];
        $stmt->execute($binds);
        return $stmt->fetchObject();
    }

    public function getCurrentRate($currency)
    {
        $stmt = $this->prepare("SELECT rate FROM сurrencies WHERE currency_from = 'USD' AND currency_to = :to AND dateline <= date_format(NOW(), '%Y-%m-%d') ORDER BY dateline DESC LIMIT 1");
        $binds = [
            ':to' => $currency
        ];
        $stmt->execute($binds);
        return $stmt->fetchColumn();
    }

    public function addMoney(int $userid, $amount)
    {
        $stmt = $this->prepare("UPDATE users SET amount = amount + :amount WHERE id = :id");
        $binds = [
            ':id' => $userid,
            ':amount' => $amount
        ];
        return $stmt->execute($binds);
    }

    public function subMoney(int $userid, $amount)
    {
        $stmt = $this->prepare("UPDATE users SET amount = amount - :amount WHERE id = :id");
        $binds = [
            ':id' => $userid,
            ':amount' => $amount
        ];
        return $stmt->execute($binds);
    }

    public function log(int $userid, $amount, int $operation, $data = [])
    {
        $stmt = $this->prepare("INSERT INTO log (userid, servicetype, amount, dateline, data) VALUES (:userid, :servicetype, :amount, unix_timestamp(), :data)");
        $binds = [
            ':userid' => $userid,
            ':servicetype' => $operation,
            ':amount' => $amount,
            ':data' => !empty($data) ? json_encode($data) : ''
        ];
        $stmt->execute($binds);
        return $this->lastInsertId();
    }

    public function getLog(int $id, $start = false, $end = false)
    {
        $binds = [];
        $where = '';
        if (! empty($start)) {
            $start = strtotime($start);
            $where .= " AND dateline >= :start";
            $binds[':start'] = $start;
        }

        if (! empty($end)) {
            $end = strtotime($end);
            $where .= " AND dateline <= :end";
            $binds[':end'] = $end;
        }
        $stmt = $this->prepare("SELECT * FROM log WHERE userid = :id {$where} LIMIT 100");
        $binds[':id'] = $id;
        $stmt->execute($binds);
        return $stmt->fetchAll(\PDO::FETCH_CLASS);
    }

    public function addCurrency($date, $from, $to, $rate)
    {
        $stmt = $this->prepare("INSERT INTO сurrencies (dateline, currency_from, currency_to, rate) VALUES (:dt, :currency_from, :currency_to, :crate)
                                ON DUPLICATE KEY UPDATE rate = VALUES(rate)");
        $binds = [
            ':dt' => $date,
            ':currency_from' => $from,
            ':currency_to' => $to,
            ':crate' => $rate
        ];
        return $stmt->execute($binds);
    }
}

