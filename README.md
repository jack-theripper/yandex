
## Введение

Неофициальное PHP SDK для некоторых сервисов Яндекса: сервис Яндекс.Диск.

## Список изменений

21/11/2022

- PHP 7.4, 8.0, 8.1

21/12/2020

- Начиная с 2.1 минимальная версия php 7.3
- zend-diactoros заменён на laminas-diactoros (laminas-zendframework-bridge)

23/08/2016

- метод `upload` поддерживает событие `progress`, слушатель принимает `float` значение в процентах.
- возвращена ранее удалённая опция `disable_redirects`.
- исправление грамматических ошибок в `README.md`

## Требования

- PHP >= 5.6
- Расширение php_curl

## Внести свой вклад в развитие

Вы можете сообщить о найденных неточностях в работе SDK, приветствуется помощь в разработке. Чтобы начать помогать вести разработку Вам нужно создать fork репозитория ветки **development**, внесите изменения в код и отправьте pull request нам с изменениями в ветку **development**.

## Установка

Поддерживается установка с помощью [менеджера пакетов](https://getcomposer.org).

```
$ composer require arhitector/yandex dev-master
```

Или 

```
$ php composer.phar require arhitector/yandex dev-master
```

## Тесты

Вы можете не найти некоторых тестов - мы их не публикуем по причинам приватности.

```
$ composer test
```

## 1. Сервис Яндекс.Диск
### 1.1. Введение

PHP SDK для работы с Яндекс.Диском, в своей основе использует REST API диска. API диска для аунтификации использует OAuth-токен (например, *0c4181a7c2cf4521964a72ff57a34a07*), который Вы должны получить самостоятельно:
- зарегистрировать приложение и самостоятельно получить токен https://oauth.yandex.ru
- или воспользоваться возможностями SDK, читайте о методе *AccessToken::refreshAccessToken* (RFC 6749#4.3.2. в скором времени, а возможно уже, будет отключен)

SDK работает только с отладочными токенами. OAuth-токен должен иметь разрешённые права "**Яндекс.Диск REST API**".

**Ресурс** - файл или папка на Яндекс.Диске. SDK определяет три состояния ресурса: **публичный**, **закрытый**, и тот который **помещён в корзину**.

### 1.1.1. Возможности

Основные моменты

- Работа с файлами на Яндекс.Диске (получение информации, копирование, перемещение, загрузка, скачивание и т.д.)
- Работа с публичными ресурсами (публикация, скачивание, копирование на свой Яндекс.Диск, и т.п.)
- Работа с ресурсами в корзине (список файлов в корзине, очистка корзины, восстановление файла из корзины и прочее)
- Поддерживает события: 'operation', 'downloaded', 'uploaded', 'deleted'
- ~~Шифрование файлов (не поддерживается, используйте ветку 1.0)~~
- Получение ссылок DocViewer

возможно это не полный список

### Плагины, дополнения, адаптеры

- адаптер [yandex-disk-flysystem](https://github.com/jack-theripper/yandex-disk-flysystem) для [thephpleague/flysystem](https://github.com/thephpleague/flysystem) (существующий адаптер WebDav хорош, но не позволяет публиковать ресурсы - тут эта возможность имеется).

## 1.1.2. Папки приложений

Приложения могут хранить на Диске пользователя собственные данные — например, настройки, сделанные этим пользователем или созданные им файлы.
Чтобы запрашивать доступ к собственной папке на Диске, приложение следует зарегистрировать с правом "**Доступ к папке приложения на Диске**".
Такое приложение сможет оперировать файлами только в рамках своей папки, если не получит также прав на общий доступ к Диску.
SDK различает общий доступ и доступ приложения к собственной папке, префиксами "**disk:/**" и "**app:/**" соответственно.
Тем не менее в информации о ресурсе пути указываются в схеме disk:/, с абсолютными путями к ресурсам, например "*disk:/Приложения/МоёПервоеПриложение/photo.png*".

## 1.1.3. Пример использования

```php

// передать OAuth-токен зарегистрированного приложения.
$disk = new Arhitector\Yandex\Disk('OAuth-токен');

/**
 * Получить Объектно Ориентированное представление закрытого ресурса.
 * @var  Arhitector\Yandex\Disk\Resource\Closed $resource
 */
$resource = $disk->getResource('новый файл.txt');

// проверить сущестует такой файл на диске ?
$resource->has(); // вернет, например, false

// загрузить файл на диск под имененм "новый файл.txt".
$resource->upload(__DIR__.'/файл в локальной папке.txt');

// файл загружен, вывести информацию.
var_dump($resource->toArray())

// теперь удалить в корзину.
$removed = $resource->delete();
```

SDK вызывает исключения по каждой ситуации, следующий пример показывает как это можно использовать.

```php
try
{
  try
  {
    /**
     * Получить закрытый ресурс
     * @var  Arhitector\Yandex\Disk\Resource\Closed $resource
     */
    $resource = $disk->getResource('новый файл.txt');

    // До этого момента запросов к Диску не было
    // Только сейчас был сделан запрос на получение информации о ресурсе к Яндекс.Диску
    // Вывести информацию. Когда ресурс не найден будет вызвано исключение NotFoundException
    $resource->toArray();
  }
  catch (Arhitector\Yandex\Client\Exception\NotFoundException $exc)
  {
    // Ресурс на Диске отсутствует, загрузить под именем 'новый файл.txt'
    $resource->upload(__DIR__.'/файл в локальной папке.txt');
  }

  // Теперь удалю, совсем.
  $file->delete(true);
}
catch (Arhitector\Yandex\Client\Exception\UnauthorizedException $exc)
{
	// Записать в лог, авторизоваться не удалось
	log($exc->getMessage());
}
catch (Exception $exc)
{
	// Что-то другое
}
```

## 1.2. Как подключиться к Яндекс.Диску

Обращение к Яндекс.Диску осуществляется через **Arhitector\Yandex\Disk**. После получения OAuth-токена, его (OAuth-токен) можно использовать следующим образом:

- Вариант 1. Инициализировать клиент.

Один и тот же OAuth-токен может быть использован для доступа к разным сервисам.

```php
$client = new Arhitector\Yandex\Client\OAuth('OAuth-токен');
```

Инициализировать клиент Яндекс.Диска и передать `$client`.

```php
$disk = new Arhitector\Yandex\Disk($client);
```

- Вариант 2. Инициализировать клиент Яндекс.Диска с передачей OAuth-токена в конструктор.

```php
$disk = new Arhitector\Yandex\Disk('OAuth-токен');
```

- Вариант 3. Инициализировать клиент Яндекс.Диска без передачи OAuth-токена.

Вы можете установить или изменить OAuth-токен в ранее инициализированном объекте.

```php
$client->setAccessToken('OAuth-токен');
```

Или изменить OAuth-токен для клиента Яндекс.Диска

```php
$disk->setAccessToken('OAuth-токен');
```

На этом этапе есть несколько переменных, например `$disk` и `$client`, которые будут использованы далее в документации для отсылки к определённым объектам.

```php
/**
 * @var Arhitector\Yandex\Client\OAuth  $client
 * @var Arhitector\Yandex\Disk          $disk
 */
```

> Примечание: Arhitector\Yandex\Client\OAuth не является реализацией протокола OAuth 2.0.

### 1.2.1. Установить OAuth-токен

Устанавливает OAuth-токен для прохождения аунтификации на сервисах. Не все операции требуют OAuth-токен.

```php
public $this OAuth::setAccessToken(string $token)

public $this Disk::setAccessToken(string $token)
```

**Примеры**

```php
$disk->setAccessToken('0c4181a7c2cf4521964a72ff57a34a07');
```

или

```php
$client->setAccessToken('0c4181a7c2cf4521964a72ff57a34a07');
```

### 1.2.2. Получить установленный OAuth-токен

Получает ранее установленный OAuth-токен или `NULL`.

```php
public mixed OAuth::getAccessToken( void );

public mixed Disk::getAccessToken( void );
```

**Примеры**

```php
$disk->getAccessToken(); // null
```

или

```php
$client->getAccessToken(); // string '0c4181a7c2cf4521964a72ff57a34a07'
```

## 1.3. Работа с Яндекс.Диском

SDK различает три типа ресурсов: **публичный**, **закрытый**, и тот который **помещён в корзину**. Каждый из типов представлен своим объектом. Для любого типа ресурса доступна фильтрация (методы **setMediaType**, **setType** и т.д.) у каждого из типов свой набор возможных значений.

- Публичный ресурс, `Arhitector\Yandex\Disk\Resource\Opened`

- Ресурс доступный владельцу, `Arhitector\Yandex\Disk\Resource\Closed`

- Ресурс в корзине, `Arhitector\Yandex\Disk\Resource\Removed`

### Введение

Существуют базовые методы получения всевозможного рода информации, которые доступны везде. Кроме прочего, поддерживаются обращения к фиктивным свойствам и работа с объектом как с массивом.

- Объект->свойство

```php
$disk->total_space; // объём диска

$resource->size; // размер файла
```

- Объект['свойство']

```php
$disk['free_space']; // свободное место

$resource['name']; // название файла/папки
```

### 1.3.1. Метод get

Получить значение по ключу.

```php
public mixed Объект::get(string $index [, mixed $default = null])
```

`$index` - индекс/ключ, по которому получить значение (`free_space`, `name` и т.д.)


`$default` - значение по умолчанию, если такой индекс отсутствует - может принимать анонимную функцию, которая будет вызвана с текущим контекстом (`Disk`, `Closed`, и т.д.)

**Примеры**

```php
// индекс total_space
$disk->get('total_space');

// custom_properties или FALSE если отсутствует
$resource->get('custom_properties', false);

 // вернёт результат 'any thing' анонимной функции 
$removedResource->get('property_123', function (Removed $resource) {
  return 'any thing';
});
```

### 1.3.2. Метод toArray

Получает содержимое всего контейнера в виде массива.

> Примечание: метод не является рекурсивным, это означает, что вложенные ресурсы (например, файлы в папке) не будут преобразованы в массив, а результатом будет массив объектов т.е. массив ресурсов (файлы, папки), представленные своим объектом.

```php
public array Объект::toArray([array $allowed = null])
```

`$allowed` - массив ключей, которые необходимо вернуть.

**Примеры**

```php
// массив информация о Яндекс.Диске
$disk->toArray();

// получить только 
$disk->toArray(['total_space', 'free_space']);

// массив объектов
$collection->toArray();

// массив, информация о ресурсе
$resource->toArray();
```

### 1.3.3. Метод toObject

Получает содержимое всего контейнера в виде объекта.

> Примечание: метод не является рекурсивным, это означает, что вложенные ресурсы (например, файлы в папке) не будут преобразованы в объект, а результатом будет коллекция объектов.

```php
public stdClass Объект::toObject([array $allowed = null])
```

`$allowed` - получить только эти ключи.


**Примеры**

```php
$disk->toObject();

$collection->toObject();

$resource->toObject(['name', 'type']);
```

### 1.3.4. Метод getIterator

Получает итератор. Вы можете использовать объекты SDK в циклах.

```php
public ArrayIterator Объект::getIterator( void )
```

**Примеры**

```php
$disk->getIterator();

$collection->getIterator();

$resource->items->getIterator();
```

Проход циклом, например, `$resource` является папкой. Получим вложенные файлы/папки в эту папку. 

```php
foreach ($resource->items as $item)
{
  // $item объект ресурса `Resource\\*`, вложенный в папку.
}
```

### 1.3.5. Метод count

Подсчитывает количество чего-то.

```php
public integer Объект::count( void )
```

Возвращает количество асинхронных операций экземпляра:

- Disk::count() 

Возвращает количество полей:

- Resource\\*::count()

**Примеры**

```php
// Возвращает количество асинхронных операций экземпляра.
$disk->count();

// в других случаях размер контейнера
$resource->items->count();
```

### 1.3.6. Методы has, hasProperty

#### Метод has

Поведение метода `has` отличается в зависимости от контекста. Может проверить существует ли свойство или существует ли такой ресурс на Яндекс.Диске.

> Примечание: возможно в будущем поведение метода будет упрощено.

```php
public bool Объект::has([string $key = NULL])
```

`$key` - необязательный параметр, индекс.

- Вызов с параметром проверяет свойство на существование

```php
$disk->has('total_space_123'); // false

$resource->has('name'); // true
```

- Вызов без параметров поддерживается только в контексте ресурса `Resource\\*` и проверяет ресурс на существование.

```php
$resource->has(); // true
```

#### Метод hasProperty

Тоже самое что и метод `has`, но выполняет только одно действие - проверка свойства на существование и доступен только в контексте ресурса `Resource\\*`

```php
public boolean Объект::hasProperty(string $key)
```

`$key` - индекс/свойство для проверки на существование.


**Примеры**

```php
$resource->hasProperty('custom_properties'); // false
```

### 1.3.7. Получение информации о диске.

Методы получения информации описаны выше.

```php
$disk->toArray();
```

Вернёт массив, примерно такого содержания. Метод `toObject` возвращает соответственно объект.

```php
array (size=5)
    'trash_size' => int 187017199
    'total_space' => float 14495514624
    'used_space' => float 14083430863
    'system_folders' => array (size=2)
        'applications' => string 'disk:/Приложения' (length=26)
        'downloads' => string 'disk:/Загрузки/' (length=23)
    'free_space' => float 412083761
```

Метод `count` тут вернёт количество инициированных асинхронных операций.

```php
$disk->count(); // int 0

count($disk); // int 5
```
Доступные ключи для метода `get`

- trash_size - размер корзины в байтах.
- total_space - объём диска в байтах.
- used_space - использованное место в байтах.
- free_space - свободное место в байтах.
- system_folders - массив содержит пути к системным папкам.

```php
// метод get
$disk->get('total_space'); // float 14495514624

// объект->свойство
$disk->used_space; // float 14083430863

// объект['свойство']
$disk['system_folders'];

/* array (size=2)
'applications' => string 'disk:/Приложения' (length=26)
'downloads' => string 'disk:/Загрузки/' (length=23) */

// используем параметр $default
$disk->get('не существующее свойство', 'default value'); // string 'default value'
```

### 1.3.8. Работа с закрытыми ресурсами.

Работа с ресурсами на диске осуществляется через метод ы `Disk::getResource` и `Disk::getResources`, доступ к которым имеет владелец диска. Не имеет значения существует ли ресурс на диске в данный момент или нет. Разница в том, что когда ресурс существует - есть возможность запросить информацию о ресурсе в другом случае будет вызвано исключение NotFoundException. По факту для ресурса, который еще не существует доступна только операция загрузки на диск - **upload**, после чего операции публикации, удаления и т.п. смогут корректно выполняться.

#### Метод Disk::getResource

Получает объектно ориентированное представление конкретного ресурса на Яндекс.Диске. Доступ к такому ресурсу имеет владелец диска.

```php
public Resource\Closed Disk::getResource(string $path [, int $limit = 20 [, int $offset = 0]])
```

`$path` - Путь к новому либо уже существующему ресурсу.


`$limit` - Количество ресурсов в ответе.


`$offset` - Смещение. Задаётся для списка всех файлов или если ресурс является папка, то задаёт смещение вложенных в папку ресурсов.

**Примеры**

Получить объект ресурса.

```php
/**
 * @var Arhitector\Yandex\Disk\Resource\Closed  $resource
 */
$resource = $disk->getResource('/путь от корня диска/до файла/или папки/название.txt');

$resource = $disk->getResource('disk:/путь от корня диска/до файла/или папки/название.txt');
```

Получить объект ресурса из папки приложения.

```php
/**
 * @var Arhitector\Yandex\Disk\Resource\Closed  $resource
 */
$resource = $disk->getResource('app:/название.txt', 100, 10);
```

Установить `$limit` и `offset` можно и после получения объекта ресурса методами `setLimit` и `setOffset`.

```php
$resource = $disk->getResource('/', 10);

$resource = $disk->getResource('/', 10, 5);

$resource->setLimit(100);

$resource->setOffset(200);
```

#### Метод Disk::getResources, список всех файлов.

Получает список всех файлов в папках, под папках и т.д. Список представлен объектом `Arhitector\Yandex\Disk\Resource\Collection`. 

```php
public Resource\Collection Disk::getResources([, int $limit = 20 [, int $offset = 0]])
```

Здесь доступны методы фильтрации, основные методы получения информации и ряд других.

**Примеры**

```php
/**
 * Получить список всех файлов
 *
 * @var Disk\Resource\Collection  $collection
 */
$collection = $disk->getResources();

$disk->getResources(100, 15);
```

Список файлов в папках также представлен объектом `Arhitector\Yandex\Disk\Resource\Collection`

```php
$resource->items; // object 'Arhitector\Yandex\Disk\Resource\Collection'
```

##### Метод getFirst

Получает *первый* ресурс в списке. Это может быть `Closed`, `Opened`, `Removed`.

```php
public mixed Collection::getFirst( void )
```

**Примеры**

```php
$collection->getFirst(); // object 'Resource/Closed'
```

##### Метод getLast

Метод коллекции, получает последний элемент.

```php
public mixed Collection::getLast( void )
```

**Примеры**

```php
$collection->getLast(); // object 'Resource/Opened'
```

##### Методы фильтрации

Все это дело происходит на стороне API. Для коллекции доступны методы

- setLimit
- setMediaType
- setOffset
- setPreviewCrop
- setPreview
- setSort

### 1.3.8.1. Проверить ресурс на существование

Проверить, существует ли ресурс на диске поможет ранее описанный метод `has` (вызывается без параметров). Если использовать с параметром - проверяет существует ли свойство.

> Примечание: возможно в будущем метод будет упрощен.

**Примеры**

```php
$resource->has();

$resource->has('name'); // проверить, есть ли 'name'
```

### 1.3.8.2. Получение информации о ресурсе

Осуществляется с помощью основных методов получения информации, описанных ранее.

**Примеры**

```php
$resource->toObject();

$resource->get('items');

$resource->hasProperty('name');

$resource->has('type');

$resource->toArray(['name', 'type', 'size']);

$resource->size;

$resource['type'];

$resource->get('custom_properties', []);
```

### 1.3.8.3. Ресурс является файлом/папкой

Для этого существуют методы **isFile** и **isDir**.

```php
public boolean Объект::isFile( void )

public boolean Объект::isDir( void )
```

**Примеры**

```php
$resource->isFile(); // true

$resource->isDir(); // false
```

### 1.3.8.4. Ресурс публичный/или доступен только владельцу

Проверить открыт ли доступ к файлу или папке позволяет метод `isPublish`

```php
public boolean Объект::isPublish( void )
```

**Примеры**

```php
$resource->isPublish(); // false

// отрыть доступ к ресурсу
if ( ! $resource->isPublish())
{
  $resource->setPublish(true);
}
```

### 1.3.8.5. Путь к ресурсу на диске

Для этого можно воспользоваться методом `getPath`. Этот путь использует SDK, но  хоть значение может и отличаться от того, которое может возвращать Яндекс.Диск, такое не совпадение вполне корректно.

```php
public string Объект::getPath( void )
```

Примеры

```php
$resource->getPath(); // disk:/файл.txt
```

### 1.3.8.6. Добавление/удаление метаинформации для ресурса

Добавленная метаинформация хранится в свойстве "custom_properties". Максимальная длина объекта (ключи + значения) 1024 байта. Значение не должно быть `NULL`.

```php
public $this Closed::set(mixed $meta [, mixed $value = null])
```

`$meta` - строка либо массив значений.


`$value` - `NULL` чтобы удалить определённую метаинформацию когда `$meta` строка.

**Примеры**

```php
$resource->set('any', 'thing');

$resource->set([
  'any'   => 'thing',
  'thing' => 'any'
]);

$resource['any'] = 'thing';

$resource->any = 'thing';

```

#### Удаление информации

Чтобы удалить метаинформацию необходимо установить значение `NULL`.

**Примеры**

```php
$resource->set('any', null); // удалить 'any'

$resource->set('thing'); // удалить 'thing'

unset($resource['any']);

unset($resource->any);
```

#### метод getProperty

Работает со свойством "custom_properties" - в нём хранится добавляемая метаинформация. Метод похож на метод `get`.

> Примечание: возможно в будущем метод будет переименован.

```php
public mixed Closed::getProperty(string $index [, mixed $default = null])
```

`$index` - ключ, по которому получить значение.


`$default` - значение по умолчанию, если такой индекс отсутствует - может принимать анонимную функцию, которая будет вызвана с текущим контекстом (только Resource\Closed).

**Примеры**

```php
$resource->getProperty('any');

$resource->get('thing12141', 'значение по умолчанию'); // вернет значение по умолчанию

$resource->get('index', function (Resource\Closed $resource) {
  // анонимная функция будет вызвана с параметром
  // текущего контекста и значение по умолчанию
  // будет значение, возвращаемое этой функцией

  return 'значение по умолчанию';
});

```

#### Метод getProperties

Получает массив всей метаинформации.

```php
public array Closed::getProperties( void )
```

**Примеры**

```php
$resource->getProperties(); // array

// метод 'get' также может получать метаинформацию.
// получить всю доступную метаинформацию в виде массива
$resource->get('custom_properties', []);

// получение информации без использования метода 'getProperty'
$resource->get('custom_properties')['thing12141']
```

### 1.3.8.7. Удаление файла или папки

Удалить совсем или поместить файл или папку в корзину можно методом `delete`.

```php
public mixed delete([bool $permanently = false])
```

`$permanently` - признак безвозвратного удаления. `FALSE` поместит ресурс в корзину (поведение по умолчанию).

**Возвращаемые значения:**

- `boolean` - результат выполнения.

- `Arhitector\Yandex\Disk\Operation` - объект синхронной операции, если по мнению API Яндекс.Диска операция удаления длительная.

- ~~`Arhitector\Yandex\Disk\Resource\Removed` - объект ресурса в корзине (не поддерживается).~~

**Примеры**

```php
$resource->delete(); // в корзину

$resource->delete(true); // удалить без помещения в корзину
```

### 1.3.8.8. Перемещение файла или папки

Перемещать файлы и папки на Диске можно, указывая новое положение ресурса.

```php
public mixed Closed::move(mixed $destionation [, $overwrite = FALSE] )
```

`$destination` - новое расположение ресурса. Может быть `строкой` или `Resource\Closed`.


`$overwrite` - `boolean` признак перезаписи, если по новому пути существует ресурс. `TRUE` перезапишет (поведение по умолчанию `FALSE` - не перезаписывать).

**Возвращаемые значения**

`bool` или объект `Arhitector\Yandex\Disk\Operation`

**Примеры**

```php
$resource->move('/путь/до/файла.txt');

$resource->move('app:/новая папка', true);

$resource->move($resource2);
```

### 1.3.8.9. Создание папки

Если ресурс уже существует будет вызвано исключение `AlreadyExists`.

```php
public $this Closed::create( void )
```

**Примеры**

```php
$resource->create();
```

### 1.3.8.10. Публикация ресурса\Закрытие доступа

Открывает доступ к ресурсу из вне по публичной ссылке. Опубликованные ресурсы управляются своим объектом `Arhitector\Yandex\Disk\Resource\Opened`.

```php
public mixed Closed::setPublish([bool $publish = true])
```

`$publish` - признак публичности, `TRUE` сделать ресурс публичным (поведение по умолчанию), `FALSE` отменить публикации ресурса.

**Возвращаемые значения**

`Arhitector\Yandex\Disk\Resource\Closed` возвращается когда доступ закрыт.


`Arhitector\Yandex\Disk\Resource\Opened` возвращается если был открыт доступ к ресурсу.

У ресурса с открытым доступом существует дополнительная информация, такая как `public_key` или `public_url`. Также `docviewer` возвращает ссылку доступную всем из вне.

**Примеры**

```php
$resource->setPublish(); // открывает доступ

$resource-setPublish(true); // открывает доступ

$resource->setPublish(false); // закрывает доступ

$resource->isPublish(); // true если ресурс с открытым доступом

$resource->public_url; // URL адрес
```
### 1.3.8.11. Скачивание файла

Метод `download` безопасен от переполнения памяти и может быть использован для скачивания файлов и папок (автоматически в виде zip-архива).  

```php
public bool Closed::download(mixed $destination [, bool $overwrite = false])
```

`$destination` - позволяет указать куда будет сохранён ресурс.

Поддерживаются следующие типы:

- `string` - путь, по которому будет записан ресурс.
- `resource` - дескриптор файла, открытый на запись.
- `StreamInterface` - объект на запись, реализующий PSR StreamInterface.

`$overwrite` - используется совместно с `$destination` строкового типа `string`, определяет поведение (перезаписать/не перезаписывать), если по такому пути существует локальный файл.

**Возвращаемые значения**

`TRUE` или `FALSE`, а также вызывает исключения по типовым событиям, например, `AlreadyExistsException` или `NotFoundException`.

**Примеры**

Скачать файл в локальную папку.

```php
// без перезаписи
$resource->download(__DIR__.'/файл.txt');

// без перезаписи
$resource->download(__DIR__.'/файл.txt', false);

// с перезапсью
$resource->download(__DIR__.'/файл.txt', true);
```

Запись в открытый дескриптор.

```php
// открыть любой дескриптор
$fp = fopen(__DIR__.'/файл.txt', 'wb+');

// или и т.д.
$fp = fopen('php://memory', 'r+b');

$resource->download($fp);

// продолжить работу ...
fseek($fp, 0);
```

Использовать обертку над потоком так же просто.

```php
$stream = new Stream('php://temp', 'r+');

$resource->download($stream);

var_dump($stream->getSize());
```

### 1.3.8.12. Копирование файла или папки

Сделать копию ресурса.

```php
public bool Closed::copy(mixed $destination [,bool  $overwrite = false])
```

`$destination` - путь до нового ресурса.

Может принимать значения:

- `string` - строка, путь от корня папки приложения или  корня Яндекс.Диска.
- `Arhitector\Yandex\Disk\Resource\Closed` - инициализированный объект другого ресурса.

`$overwrite` - признак перезаписи, если по указанному пути существует ресурс. Поведение по умолчанию `FALSE`.

**Возвращаемые значения**

`TRUE` или `FALSE`, а также `Arhitector\Yandex\Disk\Operation` в случае длительного копирования.

**Примеры**

```php
// сделать копию файла
$resource->copy('папка/файл-копия.txt');

// сделать копию папки
$resource->copy('app:/папка-копия');

// сделать копию $resource по пути 'копия/путь до файла.txt'
$resource2 = $disk->getResource('копия/путь до файла.txt');
$resource->copy($resource2, true);
```

### 1.3.8.13. Загрузка файла

Метод `upload` безопасен от утечки памяти и используется для загрузки файлов на Яндекс.Диск. Может загружать как файлы расположенные локально, в локальной папке, так и, файлы расположенные на удаленном хостинге/сервере и доступные по URL-адресу.

```php
public mixed upload(mixed $file_path [, bool $overwrite = false [, bool $disable_redirects = false]])
```

`$file_path` - может быть как путь к локальному файлу, так и URL к файлу.

Принимает значения:

- `string` - путь до локального файла или URL-адрес.
- `resource` - дескриптор файла, открытый на чтение.

`$overwrite` - признак перезаписи, если ресурс на Яндекс.Диске существует. Параметр не влияет на загрузку файлов по URL-адресу.

`$disable_redirects` - параметр влияет на файлы, загружаемые по URL-адресу. `TRUE` помогает запретить перенаправление по адресу. Поведение по умолчанию `FALSE` - пре адресация разрешена.

**Примеры**

Загрузка локального файла.

```php
$resource->upload(__DIR__.'/файл.txt');

// загрузка с перезаписью
$resource->upload(__DIR__.'/файл.txt', true);

// если передан дескриптор файла, загрузка с перезаписью
$fp = fopen(__DIR__.'/файл.txt', 'rb');
$resource->upload($fp, true);
```

Загрузка файлов, расположенных на удалённом сервере. Возвращает объект операции `Arhitector\Yandex\Disk\Operation`.

```php
$operation = $resource->upload('http://домен.ру/файл.zip');

// запретить пере адресацию.
$operation = $resource->upload('https://домен.ру/файл.zip', null, true);
```

### 1.3.8.14. Методы фильтрации

Объект `Arhitector\Yandex\Disk\Resource\Closed` поддерживает:

- setLimit
- setOffset
- setPreviewCrop
- setPreview
- setSort

## 1.3.9. Работа с публичными ресурсами.


Работа с ресурсами с открытым доступом осуществляется через методы `Disk::getPublishResource` и `Disk::getPublishResources`, доступ к которым имеет владелец диска.

#### Метод Disk::getPublishResource

Получает объектно ориентированное представление конкретного ресурса на Яндекс.Диске с открытым доступом. 

```php
public Resource\Closed Disk::getPublishResource(string $public_key [, int $limit = 20 [, int $offset = 0]])
```

`$public_key` - публичный ключ или URL-адрес ресурса с открытым доступом.

`$limit` - Количество ресурсов в ответе, если это папка.

`$offset` - Смещение. Задаётся для списка всех файлов или если ресурс является папка, то задаёт смещение вложенных в папку ресурсов.

**Примеры**

Получить объект ресурса.

```php
/**
 * @var Arhitector\Yandex\Disk\Resource\Opened  $publicResource
 */
$publicResource = $disk->getResource('https://yadi.sk/d/g0N4hNtXcrq22');

$publicResource = $disk->getResource('wICbu9SPnY3uT4tFA6P99YXJwuAr2TU7oGYu1fTq68Y=', 10, 0);
```

Установить `$limit` и `offset` можно и после получения объекта ресурса методами `setLimit` и `setOffset`.

```php

$publicResource->setLimit(100);

$publicResource->setOffset(200);
```

#### Метод Disk::getPublishResources, список всех опубликованных файлов.

Получает список всех файлов на Яндекс.Диске с открытым доступом и т.д. Список представлен объектом `Arhitector\Yandex\Disk\Resource\Collection`.

```php
public Resource\Collection Disk::getPublishResources([, int $limit = 20 [, int $offset = 0]])
```

Здесь доступны методы фильтрации, основные методы получения информации и ряд других.

**Примеры**

```php
/**
 * Получить список всех файлов
 *
 * @var Disk\Resource\Collection  $collection
 */
$collection = $disk->getPublishResources();

$disk->getPublishResources(100, 15);
```

##### Методы фильтрации

Все это дело происходит на стороне API. Для коллекции доступны методы

- setLimit
- setMediaType
- setOffset
- setPreviewCrop
- setPreview
- setSort

### 1.3.9.1. Получить публичный ключ

Получает публичный ключ или URL, который был использован для получения доступа к ресурсу.

```php
public string Opened::getPublicKey( void )
```

**Примеры**

```php
$publicResource->getPublicKey();
```

### 1.3.9.2. Получает прямую ссылку

Получить прямую ссылку на скачивание файла или папки.

> Примечание: возвращаемая ссылка действует ("живет") пару часов.

> Примечание: метод не поддерживает получение ссылок на ресурсы внутри публичной папки. Эта возможность реализуема, но не реализована.

```php
public string Opened::getLink( void )
```

**Примеры**

```php
$publicResource->getLink();
```

### 1.3.9.3. Скачивание публичного файла или папки.

Скачивание публичного файла или папки (в виде zip-архива).

```php
public bool Opened::download(mixed $destination [, bool $overwrite = false [, bool $check_hash = false]])
```

`$destination` - Путь, по которому будет сохранён файл

Принимает значения:

- `string` - файловый путь.
- `resource` - открытый на запись дескриптор файла.
- `StreamInterface` - поток, открытый на запись.

`$overwrite` - флаг перезаписи, если `$destination` является файловым путем. `FALSE` - поведение по умолчанию.

`$check_hash` - провести проверку целостности скачанного файла. Значение `TRUE` позволяет проверить `md5` хеш скачанного файла. По умолчанию `FALSE`.

**Примеры**

```php
$publicResource->download(__DIR__.'/file.txt');

$publicResource->download(__DIR__.'/file.txt', true);

$publicResource->download(__DIR__.'/file.txt', true, true);
```

Запись в открытый дескриптор.

```php
// открыть любой дескриптор
$fp = fopen(__DIR__.'/файл.txt', 'wb+');

// или и т.д.
$fp = fopen('php://memory', 'r+b');

// true - провести проверку целостности скачанного файла
$publicResource->download($fp, false, true);

// продолжить работу ...
fseek($fp, 0);
```

Использовать обертку над потоком так же просто.

```php
$stream = new Stream('php://temp', 'r+');

$publicResource->download($stream);

var_dump($stream->getSize());
```

### 1.3.9.4. Есть ли доступ к этому файлу от имени владельца.

/**
	 * Этот файл или такой же находится на моём диске
	 * Метод требует Access Token
	 *
	 * @return    boolean
	 */
	 
	 public function hasEqual()
	 
### 1.3.9.5. Сохранение публичного файла в «Загрузки».

	/**
	 * Сохранение публичного файла в «Загрузки» или отдельный файл из публичной папки
	 *
	 * @param    string $name Имя, под которым файл следует сохранить в папку «Загрузки»
	 * @param    string $path Путь внутри публичной папки.
	 *
	 * @return    mixed
	 */
	 public function save($name = null, $path = null)
	 
### 1.3.9.6. Установить путь внутри публичной папки.

	/**
	 * Устанавливает путь внутри публичной папки
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	 public function setPath($path)





## 1.3.10. Работа с файлами в корзине.

	/**
	 * Ресурсы в корзине.
	 *
	 * @param    string $path путь к файлу в корзине
	 * @param int       $limit
	 * @param int       $offset
	 *
	 * @return \Arhitector\Yandex\Disk\Resource\Removed
	 * @example
	 *
	 * $disk->getTrashResource('file.ext') -> toArray() // файл в корзине
	 * $disk->getTrashResource('trash:/file.ext') -> delete()
	 */
	public function getTrashResource($path, $limit = 20, $offset = 0)
	
	/**
	 * Содержимое всей корзины.
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return \Arhitector\Yandex\Disk\Resource\Collection
	 */
	public function getTrashResources($limit = 20, $offset = 0)
	
### 1.3.10.1. Восстановить ресурс из корзины.

	/**
	 *	Восстановление файла или папки из Корзины
	 *	В корзине файлы с одинаковыми именами в действительности именют постфикс к имени в виде unixtime
	 *
	 *	@param	mixed	$name	оставляет имя как есть и если boolean это заменяет overwrite
	 *	@param	boolean	$overwrite
	 *	@return	mixed
	 */
	public function restore($name = null, $overwrite = false)

### 1.3.10.2. Удалить ресурс из корзины.

	/**
	 * Удаление файла или папки
	 *
	 * @return    mixed
	 */
	public function delete()


## 1.3.11. Очистка корзины.

	/**
	 * Очистить корзину.
	 *
	 * @return bool|\Arhitector\Yandex\Disk\Operation
	 */
	public function cleanTrash()

## 1.3.12. Последние загруженные файлы.
	/**
	 * Последние загруженные файлы
	 *
	 * @param    integer $limit
	 * @param    integer $offset
	 *
	 * @return   \Arhitector\Yandex\Disk\Resource\Collection
	 *
	 * @example
	 *
	 * $disk->uploaded(limit, offset) // коллекия закрытых ресурсов
	 */
	public function uploaded($limit = 20, $offset = 0)
	
## 1.3.13. Синхронные операции.

	/**
	 * Получить статус операции.
	 *
	 * @param   string $identifier идентификатор операции или NULL
	 *
	 * @return  \Arhitector\Yandex\Disk\Operation
	 *
	 * @example
	 *
	 * $disk->getOperation('identifier operation')
	 */
	public function getOperation($identifier)

	/**
	 * Возвращает количество асинхронных операций экземпляра.
	 *
	 * @return int
	 */
	public function count()
	
		/**
	 * Получить все операции, полученные во время выполнения сценария
	 *
	 * @return array
	 *
	 * @example
	 *
	 * $disk->getOperations()
	 *
	 * array (size=124)
	 *  0 => 'identifier_1',
	 *  1 => 'identifier_2',
	 *  2 => 'identifier_3',
	 */
	public function getOperations()
	
## 1.3.14. Методы фильтрации.

	/**
	 * Количество ресурсов, вложенных в папку, описание которых следует вернуть в ответе
	 *
	 * @param    integer $limit
	 * @param    integer $offset установить смещение
	 *
	 * @return   $this
	 */
	public function setLimit($limit, $offset = null)


	/**
	 * Количество вложенных ресурсов с начала списка, которые следует опустить в ответе
	 *
	 * @param    integer $offset
	 *
	 * @return    $this
	 */
	public function setOffset($offset)
	
		/**
	 * Атрибут, по которому сортируется список ресурсов, вложенных в папку.
	 *
	 * @param    string  $sort
	 * @param    boolean $inverse TRUE чтобы сортировать в обратном порядке
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setSort($sort, $inverse = false)
	
	'Допустимые значения сортировки - name, path, created, modified, size'
	
		/**
	 * Тип файлов, которые нужно включить в список
	 *
	 * @param    string $media_type
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setMediaType($media_type)
	Тип файлов, которые нужно включить в список. Диск определяет тип каждого файла при загрузке.
Чтобы запросить несколько типов файлов, можно перечислить их в значении параметра через запятую. Например, media_type="audio,video".
Поддерживаемые типы:
audio — аудио-файлы.
backup — файлы резервных и временных копий.
book — электронные книги.
compressed — сжатые и архивированные файлы.
data — файлы с базами данных.
development — файлы с кодом (C++, Java, XML и т. п.), а также служебные файлы IDE.
diskimage — образы носителей информации в различных форматах и сопутствующие файлы (например, CUE).
document — документы офисных форматов (Word, OpenOffice и т. п.).
encoded — зашифрованные файлы.
executable — исполняемые файлы.
flash — файлы с флэш-видео или анимацией.
font — файлы шрифтов.
image — изображения.
settings — файлы настроек для различных программ.
spreadsheet — файлы офисных таблиц (Numbers, Lotus).
text — текстовые файлы.
unknown — неизвестный тип.
video — видео-файлы.
web — различные файлы, используемые браузерами и сайтами (CSS, сертификаты, файлы закладок).

		/**
	 * Получает установленное значение.
	 *
	 * @return  string
	 */
	public function getMediaType()
	
		/**
	 * Все возможные типы файлов
	 *
	 * @return array
	 */
	public function getMediaTypes()
	
		/**
	 * Обрезать превью согласно размеру
	 *
	 * @param    boolean $crop
	 *
	 * @return    $this
	 */
	public function setPreviewCrop($crop)
	Параметр позволяет обрезать превью согласно размеру, заданному в значении параметра preview_size.
Допустимые значения:
 «false» — параметр игнорируется. Это значение используется по умолчанию.
 «true» — превью обрезается следующим образом:
Если передана только ширина или высота, картинка уменьшается до этого размера с сохранением пропорций. Затем из центра уменьшенного изображения также вырезается квадрат с заданной стороной.
Если передан точный размер (например, «120x240»), из центра оригинального изображения вырезается фрагмент максимального размера в заданных пропорциях ширины и высоты. Затем вырезанный фрагмент масштабируется до указанных размеров.

		/**
	 * Размер уменьшенного превью файла
	 *
	 * @param    mixed $preview S, M, L, XL, XXL, XXXL, <ширина>, <ширина>x, x<высота>, <ширина>x<высота>
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setPreview($preview)
	Вы можете задать как точный размер превью, так и размер одной из сторон. Получившееся изображение можно обрезать до квадрата с помощью параметра preview_crop.
Варианты значений:
 Предопределенный размер большей стороны.
Картинка уменьшается до указанного размера по большей стороне, пропорции исходного изображения сохраняются. Например, для размера «S» и картинки размером 120×200 будет сгененерировано превью размером 90×150, а для картинки 300×100 — превью размером 150×50.
Поддерживаемые значения:
«S» — 150 пикселей;
«M» — 300 пикселей;
«L» — 500 пикселей;
«XL» — 800 пикселей;
«XXL» — 1024 пикселей;
«XXXL» — 1280 пикселей.
 Точная ширина (например, «120» или «120x») или точная высота (например, «x145»).
Картинка уменьшается до указанной ширины или высоты, пропорции исходного изображения сохраняются.
Если передан параметр preview_crop, из центра уменьшенного изображения также вырезается квадрат с заданной стороной.
 Точный размер (в формате <ширина>x<высота>, например «120x240»).
Картинка уменьшается до меньшего из указанных размеров, пропорции исходного изображения сохраняются.
Если передан параметр preview_crop, из центра оригинального изображения вырезается фрагмент максимального размера в заданных пропорциях ширины и высоты (в примере — 1/2). Затем вырезанный фрагмент масштабируется до указанных размеров.


		/**
	 * Получает установленное значение "setPreview".
	 *
	 * @return  string
	 */
	public function getPreview()
	
		/**
	 * Получает установленное значение "setPreviewCrop".
	 *
	 * @return  string
	 */
	public function getPreviewCrop()
	
		/**
	 * Относительный путь к ресурсу внутри публичной папки.
	 *
	 * @param    string $path
	 *
	 * @return    $this
	 */
	public function setRelativePath($path)
	
		/**
	 * Получает установленное значение.
	 *
	 * @return  string
	 */
	public function getRelativePath()
	
		/**
	 * Тип ресурса
	 *
	 * @param    string $type
	 *
	 * @return    $this
	 * @throws    \UnexpectedValueException
	 */
	public function setType($type)
	
	
		/**
	 * Получает установленное значение.
	 *
	 * @return  string
	 */
	public function getType()
	
	
## 1.3.15 События.

Ресурсы поддерживают свои события. Чтобы получить больше информации читайте описание событий клиента (описано ниже). Список доступных событий:

- **disk.downloaded** - событие наступает всякий раз когда именно этот ресурс был скачан.
- **disk.uploaded** - событие наступает когда ресурс загружен.
- **disk.operation** - наступает когда Яндекс.Диск выполняет асинхронную операцию с ресурсом (например, перемещение большой папки).
- **disk.delete** - наступает тогда, когда именно этот ресурс удаляется, может отменить удаление.

Отличие от событий делигируемых клиентом заключается в видимости этих событий. Клиент устанавливает события для всех ресурсов, не смотря на это каждый ресурс может принимать свои собственные события.

Событие может принимать несколько обработчиков. Обработчики событий не заменяют друг друга, а добавляются в очередь.

**Примеры**

```php
$resource->addListeners();

```

## 1.3.16 Событие `uploaded`

Вызывается после выполнения запроса на загрузку локального файла.

```php
use Arhitector\Yandex\Disk;
use Arhitector\Yandex\Disk\Resource\Closed;
use League\Event\Event;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

// ... 
$disk->addListener('uploaded', function (Event $event, Closed $resource, Disk $disk, StreamInterface $uploadedStream, ResponseInterface $response) {
	// $event - событие
	// $resource - тоже самое что и $resource
	// $disk - клиент
	// $uploadedStream - в данном примере файл file_path.pdf обернутый в Stream
	// $response - Ответ от Я.Диска. $response->getBody() - не содержит ничего (см. документацию API Я.Диска)
});

// ...
$resource->upload(__DIR__.'/file_path.pdf');

```







## Стандарты кодирования

Эти стандарты должны соблюдаться при внесении изменений в код. В основе стандарта используется PSR-2 с некоторыми изменениями.

### Управляющие структуры

Ключевые слова, такие как **if**, **for**, **foreach**, **while**, **switch** должны сопровождаться пробелом перед списком параметров/аргументов. Фигурные скобки должны располагаться на новой линии и разрыв должен соблюдать ту же вложенность.

```php
if ($arg === true)
{
    // некоторый код
}
elseif ($arg === null)
{
    // некоторый код
}
else
{
    // некоторый код
}

foreach ($array as $key => $value)
{
    // некоторый код
}

for ($i = 0; $i < $max; $i++)
{
    // некоторый код
}

while ($i < $max)
{
    // некоторый код
}

do
{
    // некоторый код
}
while ($i < $max)

switch ($var)
{
    case 'value1':
        // некоторый код
    break;

    default :
        // некоторый код
    break;
}
```

### Сравнения и логические операторы

Оператор **!** должен быть окружён пробелами с обеих сторон.

```php
if ($var == false and $other_var != 'some_value')
if ($var === false or my_function() !== false)
if ( ! $var)
```







## 1. Базовый клиент для общения с сервисами Яндекса

Базовый клиент предоставляет функциональность для прохождения аунтификации на сервисах Яндекса, использующих протокол OAuth. Наследуется другими клиентами для определённых сервисов (например, *Mackey\Yandex\Disk* также наследует методы базового клиента).

```php
public void Client::__construct([string $token = null])
```

**$token** - необязательный параметр, OAuth-токен приложения.

**Примеры**

```php
$client = new Client();

$client = new Client('0c4181a7c2cf4521964a72ff57a34a07');
```

### 1.1. Формат обмена данными

Получает информацию о формате, в котором происходит общение с сервисами.

```php
public string Client::getContentType( void )
```

**Примеры**

```php
$client->getContentType(); // application/json
```

### 1.2. Установить ID приложения

Устанавливает ID зарегистрированного приложения.

```php
public this Client::setClientOauth(string $client_id)
```

**$client_id** - строка, ID приложения

**Примеры**

```
$client->setClientOauth('123456789');
```

### 1.3. Получить ID приложения

Возвращает ID приложения, если таковой был ранее установлен с помощью метода *setClientOauth*.

```php
public mixed Client::getClientOauth( void )
```

**Пример**

```php
$client->getClientOauth(); // null

$client->getClientOauth(); // string '123456789'
```

### 1.4. Установить пароль приложения

Устанавливает пароль приложения. Пароль приложения выдается при регистрации этого приложения.

```php
public this Client::setClientOauthSecret(string $client_secret)
```

**Примеры**

```php
$client->setClientOauthSecret('--------');
```

### 1.5. Получить пароль приложения

Возвращает ранее установленный пароль приложения или NULL.

```php
public mixed Client::getClientOauthSecret( void )
```

**Примеры**

```php
$client->getClientOauthSecret(); // null

$client->getClientOauthSecret(); // string '--------'
```

### 1.6. Установить OAuth-токен

Устанавливает OAuth-токен для прохождения аунтификация на сервисах. Не ве сервисы требуют OAuth-токен.

```php
public this Client::setAccessToken(string $token)
```

**Примеры**

```php
$client->setAccessToken('0c4181a7c2cf4521964a72ff57a34a07');
```

### 1.7. Получить установленный OAuth-токен

Получает ранее установленный OAuth-токен или NULL.

```php
public mixed Client::getAccessToken( void );
```

**Примеры**

```php
$client->getAccessToken(); // null

$client->getAccessToken(); // string '0c4181a7c2cf4521964a72ff57a34a07'
```

### 1.8. Запросить новый или обновить уже выданный OAuth-токен

Позволяет получить OAuth-токен или обновить уже выданный ранее токен для приложения.

```php
public mixed refreshAccessToken(string $username, string $password [, bool $onlyToken = false])
```

В случае успеха возвращает объект с информацией или только OAuth-токен если передан параметр $onlyToken = true.

**$username** - имя пользователя аккаунта, на котором зарегистрировано приложение

**$password** - пароль от аккаунта

**$onlyToken** - вернуть только строковый токен

**Примеры**

```php
$client->refreshAccessToken('username', 'password');

/* object(stdClass)[28]
  public 'token_type' => string 'bearer' (length=6)
	public 'access_token' => string 'c7621`6149032dwf9a6a7ca765eb39b8' (length=32)
	public 'expires_in' => int 31536000
	public 'uid' => int 241`68329
	public 'created_at' => int 1456882032 */

$client->refreshAccessToken('username', 'password', true);

/* string 'c7621`6b09032dwf9a6a7ca765eb39b8' (length=32) */
```

### 1.9. Установить обработчик события

SDK поддерживает события. Каждый сервис имеет свой набор возможных событий.  Перейдите на <http://event.thephpleague.com/2.0/> для получения более полной информации.

```php
public this Client::addListener(string $event, mixed $listener [, int $priority = ListenerAcceptorInterface::P_NORMAL])
```

**$event** - событие

**$listener** - обработчик ListenerInterface или callable

**$priority** - приоритет. **League\Event\EmitterInterface** предопределяет 3 приоритета:
- EmitterInterface::P_HIGH: 100
- EmitterInterface::P_NORMAL: 0
- EmitterInterface::P_LOW: -100


**Примеры**

```php
$client->addListener('disk.downloaded', function (Event $event, $resource) {
  // скачивание файла завершено
});
```

### 1.10. Удалить обработчик для события

Удаляет ранее установленный обработчик.

```php
public this Client::removeListener(string $event, mixed $listener)
```

**$event** - событие

**$listener** - обработчик ListenerInterface или callable

**Примеры**

```php
$client->removeListener('disk.downloaded', function (Event $event, $resource) {

});
```

### 1.11. Установить одноразовый обработчик

Устанавливает обработчик.

```php
public this addOneTimeListener(string $event, mixed $listener [, int $priority = ListenerAcceptorInterface::P_NORMAL])
```

**$event** - событие

**$listener** - обработчик ListenerInterface или callable

**$priority** - приоритет. **League\Event\EmitterInterface** предопределяет 3 приоритета:
- EmitterInterface::P_HIGH: 100
- EmitterInterface::P_NORMAL: 0
- EmitterInterface::P_LOW: -100

**Примеры**

```php
$client->addOneTimeListener('disk.downloaded', function (Event $event, $resource) {
  // скачивание файла завершено
});
```

### 1.12. Удалить все обработчики события

Удалить все ранее утсановленные обработчики.

```php
public this removeAllListeners(string $event)
```
**$event** - событие

**Примеры**

```php
$client->removeAllListeners('disk.downloaded');
```

### 1.13. Обработчик события на основе класса

Добавляет слушатель на основе класса.

```php
public this useListenerProvider(ListenerProviderInterface $provider)
```

**$provider** - объект класса

**Примеры**

```php
$client->useListenerProvider(new MyProvider);
```

### 1.14. Запустить событие

Выполняет вызов слушателей события.

```php
public mixed emit(string $event)
```

**$event** - событие

**Примеры**

```php
$client->emit('custom.event', 'custom parameter', 'etc.');
```
