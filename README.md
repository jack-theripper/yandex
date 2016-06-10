## Введение

**Эта версия документации устарена т.к. был изменён дизайн данного SDK, именования методов и имён классов.**

PHP SDK для работы с Яндекс.Диском, в своей основе использует REST API диска. API диска для аунтификации использует OAuth-токен (например, *0c4181a7c2cf4521964a72ff57a34a07*), который Вы должны получить самостоятельно:
- зарегистрировать приложение и самостоятельно получить токен https://oauth.yandex.ru
- или воспользоваться возможностями SDK, читайте о методе *AccessToken::refreshAccessToken*

SDK работает только с отладочными токенами. OAuth-токен должен иметь разрешённые права "**Яндекс.Диск REST API**".

Понятие **ресурса** - файл или папка на Яндекс.Диске. SDK определяет три состояния ресурса: **публичный**, **закрытый**, и тот который **помещён в корзину**.

## Требования

- PHP 5.5
- Curl
- OpenSSL (по желанию)
- Zlib (по желанию)

## Возможности

- Работа с файлами на Яндекс.Диске (информация, копирование, перемещение, загрузка, скачивание и т.д.)
- Работа с публичными ресурсами (публикация, скачивание, копирование на свой Яндекс.Диск, и т.п.)
- Работа с ресурсами в корзине (список файлов в корзине, очистка корзины, восстановление файла из корзины и прочие возможности)
- Поддерживает события: operation, downloaded, uploaded, delete
x Шифрование файлов (более не поддерживается)
- Получение ссылки DocViewer
- возможно это не весь список

## Установка

Поддерживается установка с помощью менеджера пакетов.

```
$ composer require arhitector/yandex dev-master
```

## Тесты

Вы можете не найти некоторых тестов - мы их не публикуем по причинам приватности.

```
$ composer test
```

## Внести свой вклад в развитие

Вы можете сообщить о найденных неточностях в работе SDK, приветствуется помощь в разработке. Чтобы начать помогать вести разработку Вам нужно создать fork этого репозитория, внесите изменения в код и отправьте pull request с изменениями. Вы лучшие!

## Пример кода

```php
try
{
  // передать OAuth-токен зарегистрированного приложения
  $disk = new Arhitector\Yandex\Disk('OAuth-токен');

  /**
   * Получить закрытый ресурс
   * @var  Arhitector\Yandex\Disk\Resource\Closed $resource
   */
  $resource = $disk->getResource('новый файл.txt');

  // проверить сущестует такой файл на диске ?
  $resource->has(); // например false

  // загрузить файл на диск под имененм "новый файл.txt"
  $resource->upload(__DIR__.'/файл в локальной папке.txt');

  // файл загружен вывести информацию
  $resource->toArray();

  // теперь удалить в корзину
  // иногда SDK может самостоятельно отследить ресурс в корзине
  // и вернуть объект Arhitector\Yandex\Resource\Removed
  $removed = $resource->delete();

  // подобный пример можно выполнить другим способом
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
    // Ресурс на Диске отсутствует, загружу
    $resource->upload(__DIR__.'/файл в локальной папке.txt');
  }

  // Теперь удалю совсем
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


## Папки приложений

Приложения могут хранить на Диске пользователя собственные данные — например, настройки, сделанные этим пользователем или созданные им файлы.
Чтобы запрашивать доступ к собственной папке на Диске, приложение следует зарегистрировать с правом "**Доступ к папке приложения на Диске**".
Такое приложение сможет оперировать файлами только в рамках своей папки, если не получит также прав на общий доступ к Диску.
SDK различает общий доступ доступ и доступ приложения к собственной папке, префиксами "**disk:/**" и "**app:/**" соответственно.
Тем не менее в информации о ресурсе пути указываются в схеме disk:/, с абсолютными путями к ресурсам, например "*disk:/Приложения/МоёПервоеПриложение/photo.png*".

## 1. Начало

Обращение к диску происходит через **Mackey\Yandex\Disk**. После получения OAuth-токена его можно использовать следующим образом, есть несколько вариантов:

- Инициализировать клиент

```php
$client = new Mackey\Yandex\Client('OAuth-токен');
```

- Инициализировать клиент диска

```php
$disk = new Mackey\Yandex\Disk('OAuth-токен');
```

- Инициализировать клиент диска и передать клиент

```php
$disk = new Mackey\Yandex\Disk($client);
```

- Установить токен в ранее инициализированный объект

```php
$client->setAccessToken('OAuth-токен');
```

или

```php
$disk->setAccessToken('OAuth-токен');
```

На этом этапе есть несколько переменных, например *$disk* и *$client*, которые будут использованы далее в документации для отсылки к определённым объектам.

```php
/**
 * @var Mackey\Yandex\Client  $client
 * @var Mackey\Yandex\Disk    $disk
 */
```

### 1.1. Установить OAuth-токен

Устанавливает OAuth-токен для прохождения аунтификации на сервисах. Не все сервисы требуют OAuth-токен.

```php
public this Disk::setAccessToken(string $token)
```

**Примеры**

```php
$disk->setAccessToken('0c4181a7c2cf4521964a72ff57a34a07');

// или

$client->setAccessToken('0c4181a7c2cf4521964a72ff57a34a07');
```

### 1.2. Получить установленный OAuth-токен

Получает ранее установленный OAuth-токен или NULL.

```php
public mixed Disk::getAccessToken( void );
```

**Примеры**

```php
$disk->getAccessToken(); // null

// или

$client->getAccessToken(); // string '0c4181a7c2cf4521964a72ff57a34a07'
```

### 1.3. Получение информации о диске/ресурсе

Существуют базовые методы получения всевозможного рода информации, которые доступны везде. Кроме прочего, поддерживаются обращения к фиктивным свойствам и работа с объектом как с массивом.

```php
// Объект->свойство

$disk->total_space;

$resource->size;

// Объект[свойство]

$disk['free_space'];

$resource['name'];
```


### 1.3.1. метод get

Получить значение из контейнера по ключу.

```php
public mixed Объект::get(string $index [, mixed $default = null])
```

**$index** - ключ, по которому получить значение (free_space, name и т.д.)

**$default** - значение по умолчанию, если такой индекс отсутствует - может принимать анонимную функцию, которая будет вызвана с текущим контекстом (Disk, Closed, и т.д.)

**Примеры**

```php
// индекс total_space
$disk->get('total_space');

// custom_properties или пустой массив если отсутствует
$resource->get('custom_properties', []);

 // вернёт результат функции 'any thing'
$removedResource->get('xz', function (Removed $resource) {
  return 'any thing';
});

// обращение к свойствам как к массиву
$resource['name'];

// псевдо свойство объекта
$disk->free_space;
```

### 1.3.2. метод toArray

Получает содержимое всего контейнера в виде массива.

```php
public array Объект::toArray([array $allowed = null])
```

**$allowed** - получить только эти ключи (например [name, type] и т.п.)

**Примеры**

```php
$disk->toArray();

$collection->toArray();

$resource->toArray();
```

### 1.3.3. метод toObject

Получает содержимое всего контейнера в виде объекта.

```php
public stdClass Объект::toObject([array $allowed = null])
```

**$allowed** - получить только эти ключи (например [name, type] и т.п.)

**Примеры**

```php
$disk->toObject();

$collection->toObject();

$resource->toObject();
```

### 1.3.4. метод getIterator

Получает итератор.

```php
public ArrayIterator Объект::getIterator( void )
```

**Примеры**

```php
$disk->getIterator();

$collection->getIterator();

$resource->items->getIterator();
```

### 1.3.5. метод count

Получает размер контейнера или что-то другое.

```php
public integer Объект::count( void )
```

**Примеры**

```php
// возвращает float свободное место
$disk->count();

// в других случаях размер контейнера
$resource->items->count();
```

### 1.3.6. методы has, hasProperty

Проверяет, существует такое свойство в контейнере или нет.
Метод доступен везде, но с разными параметрами. В контексте ресурса (например Resource\\*) может проверить существует ли ресурс на диске.

```php
public boolean Объект::has(string $key)
```

Метод **hasProperty** алиас **has** в контексте ресурса и проверяет свойство на существование.

**Примеры**

```php
$disk->has('total_space_123'); /* false */

$resource->has();

$resource->has('name');

$resource->hasProperty('custom_properties');
```

## 2. Работа с диском.

SDK различает три типа ресурсов: **публичный**, **закрытый**, и тот который **помещён в корзину**. Каждый из типов представлен своим объектом. Для любого типа ресурса доступна фильтрация (методы **setMediaType**, **setType** и т.д.) у каждого из типов свой набор возможных значений.

- Публичный ресурс, **Mackey\Yandex\Disk\Resource\Opened**
- Ресурс доступный владельцу, **Mackey\Yandex\Disk\Resource\Closed**
- Ресурс в корзине, **Mackey\Yandex\Disk\Resource\Removed**

### 2.1. Получение информации о диске.

Вы можете использовать геттеры, описанные выше.

```php
$disk->toArray();
```

Вернёт массив, примерно такого содержания.

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

Метод **count** тут вернёт количество свободного места, типа **float** (вместо **integer**).

```php
$disk->count(); // float 412083761

// также вернёт количество свободного места
// значение может не помещаться в тип integer
count($disk);
```

**Примеры**

```php
$disk->get('total_space'); /* float 14495514624 */

$disk->used_space; /* float 14083430863 */

$disk['system_folders'];

/* array (size=2)
'applications' => string 'disk:/Приложения' (length=26)
'downloads' => string 'disk:/Загрузки/' (length=23) */

$disk->get('не существующее свойство', []); /* array */
```


### 2.2. Работа с закрытыми ресурсами.

Работа с ресурсами на диске осуществляется через метод **Disk::resource**, доступ к которым имеет владелец диска. Не имеет значения существует ли ресурс на диске в данный момент или нет. Разница в том, что когда ресурс существует - есть возможность запросить информацию о ресурсе в другом случае будет вызвано исключение NotFoundException. По факту для ресурса, который еще не существует доступна только операция загрузки на диск - **upload**, после чего операции публикации, удаления и т.п. смогут корректно выполняться.

```php
public mixed Disk::getResource(string $path [, int $limit = 20 [, int $offset = 0]])
```

**$path** - Путь к новому либо уже существующему ресурсу, NULL Список всех файлов.

**$limit** - Количество ресурсов в ответе.

**$offset** - Смещение. Задаётся для списка всех файлов или если ресурс является папка, то задаёт смещение вложенных в папку ресурсов.

**Примеры**

```php
/**
 * Получить какой-то ресурс
 *
 * @var Disk\Resource\Closed  $collection
 */
$resource = $disk->resouce('файл.txt');

$disk->resource('disk:/файл.txt');

$disk->resource('app:/папка/еще папка', 100, 10);
```

### Список всех файлов. Коллекция ресурсов.

Получает список всех файлов в папках, подпапках и т.д. Список представлен объектом **Disk\Resource\Collection**. Здесь доступны методы фильтрации, геттеры и ряд других.

**Примеры**

```php
/**
 * Получить список всех файлов
 *
 * @var Disk\Resource\Collection  $collection
 */
$collection = $disk->resource();

$disk->resource(null, 100, 15);
```

Коллекция ресурсов (объект **Mackey\Yandex\Client\Container**) содержит, например, список файлов в папке.

```php
$resource->items; // Mackey\Yandex\Client\Container
```

#### метод getFirst

Получает *первый* ресурс в списке. Это может быть **Closed**, **Opened**, **Removed**.

```php
public mixed Container::getFirst( void )
```

**Примеры**

```php
$collection->getFirst();
```

#### метод getLast

Получает *последний* ресурс в списке. Это может быть **Closed**, **Opened**, **Removed**.

```php
public mixed Container::getLast( void )
```

**Примеры**

```php
$collection->getLast();
```

### 2.2.1. Существует ресурс

Проверить, существует ли ресурс на диске поможет ранее описанный метод **has** (без параметров). Если использовать с параметром - проверяет существует ли свойство.

**Примеры**

```php
$resource->has();

$resource->has('name'); // проверить, есть ли 'name'
```

### 2.2.2. Получение информации о ресурсе

Осуществляется с помощью геттеров, описанных ранее.

**Примеры**

```php
$resource->toObject();

$resource->get('items');

$resource->count();

$resource->hasProperty('name');

$resource->has('type');
```

### 2.2.3. Ресурс является файлом/папкой

Для этого сущетсвуют **isFile** и **isDir**.

```php
public boolean Объект::isFile( void )

public boolean Объект::isDir( void )
```

**Примеры**

```php
$resource->isFile(); // true

$resource->isDir(); // false
```

### 2.2.4. Путь к ресурсу на диске

Для этого можно воспользоваться методом **getPath**.

```php
public string Объект::getPath( void )
```

Примеры

```php
$resource->getPath(); // disk:/файл.txt
```

### 2.2.5. Добавление/удаление метаинформации для ресурса

Добавленная метаинформация хранится в свойстве "custom_properties". Максимальная длина объекта (ключи + значения) 1024.

```php
public $this Closed::set(mixed $meta [, mixed $value = null])
```

**$meta** - строка либо массив значений.

**$value** - NULL чтобы удалить определённую метаинформацию когда **$meta** строка.

**Примеры**

```php
$resource->set('any', 'thing');

$resource->set([
  'any' => 'thing',
  'thing' => 'any'
]);

$resource['any'] = 'thing';

$resource->any = 'thing';

```

### 2.2.5.1. Удаление информации

Для этого нужно установить значение NULL.

**Примеры**

```php
$resource->set('any', null); // удалить 'any'

$resource->set('thing'); // удалить 'thing'

unset($resource['any']);

unset($resource->any);
```

### 2.2.5.2. метод getProperty

Работает со свойством "custom_properties" - в нём хранится добавляемая метаинформация. Метод похож на метод **get**.

**$index** - ключ, по которому получить значение (free_space, name и т.д.)

**$default** - значение по умолчанию, если такой индекс отсутствует - может принимать анонимную функцию, которая будет вызвана с текущим контекстом (Disk, Closed, и т.д.)

```php
public mixed Closed::getProperty(string $index [, mixed $default = null])
```

**Примеры**

```php
$resource->getProperty('any');

$resource->get('thing12141', 'значение по умолчанию');

$resource->get('index', function ($resource) {
  // анонимная функция будет вызвана с параметром
  // текущего контекста и значение по умолчанию
  // будет значение, возвращаемое этой функцией

  return 'значение по умолчанию';
});
```




### 2.2.6. Удаление файла или папки

public function delete($permanently = false)

	/**
	 * Удаление файла или папки
	 *
	 * @param    boolean $permanently TRUE Признак безвозвратного удаления
	 *
	 * @return    mixed
	 */


### 2.2.7. Перемещение файла или папки

	/**
	 * Перемещение файла или папки.
	 * Перемещать файлы и папки на Диске можно, указывая текущий путь к ресурсу и его новое положение.
	 * Если запрос был обработан без ошибок, API составляет тело ответа в зависимости от вида указанного ресурса –
	 * ответ для пустой папки или файла отличается от ответа для непустой папки. (Если запрос вызвал ошибку,
	 * возвращается подходящий код ответа, а тело ответа содержит описание ошибки).
	 * Приложения должны самостоятельно следить за статусами запрошенных операций.
	 *
	 * @param    string|Closed $destination новый путь.
	 * @param   boolean        $overwrite   признак перезаписи файлов. Учитывается, если ресурс перемещается в папку, в
	 *                                      которой уже есть ресурс с таким именем.
	 *
	 * @return bool
	 */
	public function move($destination, $overwrite = false)


### 2.2.8. Создание папки

	/**
	 *	Создание папки, если ресурса с таким же именем нет
	 *
	 *	@return	$this
	 *	@throws	mixed
	 */
	public function create()


### 2.2.9. Публикация ресурса\Закрытие доступа


	/**
	 *	Публикация ресурса\Закрытие доступа
	 *
	 *	@param	string|Resource
	 */
	public function publish($publish = true)


### 2.2.10. Копирование файла или папки


	/**
	 * Копирование файла или папки
	 *
	 * @param    string|Closed
	 *
	 * @return bool
	 */
	public function copy($destination, $overwrite = false)


### 2.2.11. Скачать файл с диска

	/**
	 * Скачивает файл
	 *
	 * @param string $path Путь, по которому будет сохранён файл
	 * @param mixed  $overwrite
	 *
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws AlreadyExistsException
	 * @throws \OutOfBoundsException
	 * @throws \UnexpectedValueException
	 */
	public function download($path, $overwrite = false)


### 2.2.12. Загрузка файла на диск

	/**
	 *	Загрузить файл на диск
	 *
	 *	@param	string	$file_path	может быть как путь к локальному файлу, так и URL к файлу
	 *	@param	mixed	$overwrite
	 *	@param	mixed	$progress
	 *
	 *	@return	boolean
	 *
	 *	@throws	mixed
	 */
	public function upload($file_path, $overwrite = false)


### 2.2.13. События

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




### 2.2.14. Проверяет этот файл зашифрован или нет

### 2.2.15. Включить/отключить шифрование

### 2.2.16. Установить секретную фразу для шифрования



### 2.3. Работа с публичными ресурсами.




### 2.4. Работа с ресурсами в корзине.




### 2.5. Асинхронные операции.


### 2.6. Список последних загруженных файлов.






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

## Список изменений

- версия 2.0b
- версия 1.1 стабильная
- версия 1.0









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
