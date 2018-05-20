# API + Log

# REST API

База данных MySQL
По умолчанию не используются никакие фреймворки

Методы:
  - add-user
  - add-money
  - add-currencies
  - transfer-money

Обзор запросов

# Add User
Добавить пользователя через апи
В качестве передаваемых параметров нужно передать Имя,Страну,Город,Валюту.
```sh
$  curl -i -X POST -d '{"name":"Slava","city":"Moscow","country":"Russia","currency":"RUB"}' http://localhost/api/add-user
```
Ответ вернёт ИД пользователя в системе:
```sh
$  {"status":"success","response":{"userid":"3"}}
```

# Add Money
Добавить пользователю денег.
В качестве параметров передаётся Сумма,Валюта,ИД пользователя
```sh
$  curl -i -X POST -d '{"amount":3.5,"currency":"GBP","id":3}' http://localhost/api/add-money
```
Ответ содержит служебную информацию по конвертации валюты конечное значение денег на счёте:
```sh
$  {"status":"success","response":{"userid":3,"startAmount":3.5,"endAmount":292.9614,"startCurrency":"GBP","endCurrency":"RUB","totalUserAmount":292.9614}}
```

# Add currencies
Добавить валюты по дням.
В качестве параметров передаётся массив содеражищий Имя валюты, Рейт, Дату
```sh
$   curl -i -X POST -d '[{"date":"2018-05-19","to":"EUR","rate":1.195},{"to":"EUR","rate":1.1952},{"date":"2018-05-21","to":"RUB","rate":61.9}]' http://localhost/api/add-currencies
```
Ответ вернёт обработанный значения:
```sh
$  {"status":"success","response":[{"date":"2018-05-19","to":"EUR","rate":1.195,"from":"USD"},{"to":"EUR","rate":1.1952,"date":"2018-05-20","from":"USD"},{"date":"2018-05-21","to":"RUB","rate":61.9,"from":"USD"}]}
```

# Transfer money
Перевод денег между пользователями.
В качестве параметров передаётся массив содеражищий ИД отправителя, ИД получателя, сумма, и в какой валюте перевод
```sh
$   curl -i -X POST -d '{"amount":20,"currency":"EUR","receiver":2,"userid":1}' http://localhost/api/transfer-money
```
Ответ вернёт исходный массив в случае успешного выполнения:
```sh
$  {"status":"success","response":{"amount":20,"currency":"EUR","receiver":2,"userid":1}}
```

# Tool
http://localhost/tool/find

Поиск лога по имени и дате

**Free Software, Hell Yeah!**

