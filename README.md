
Яндекс.Диск использует OAuth-токен для аунтификации, зарегистрировать приложение и получить токен https://oauth.yandex.ru/

## Требования

* PHP 5.5
* OpenSSL
* Curl
* либа с репозитория php-curl-class

## Composer

$ composer require arhitector/yandex dev-master
	
после получения OAuth-токена его можно использовать следующим образом

```php
$client = new Mackey\Yandex\Client('token')
```
или 
```php
$client->token('token')
```
или 
```php
$disk = new Mackey\Yandex\Disk('token')
```
или
```php
$disk = new Mackey\Yandex\Disk($client)
```
или 
```php
$disk->token('token')
```

Работа с диском разделена на несколько методов, так для ресурсов, которые расположены в корзине нужно использовать $disk->trash(...), а для тех что опубликованы $disk->publish(...)

1. Информация о диске
```php
		$disk = new Mackey\Yandex\Disk('token');
		
		$disk->getContents();
		
		/* array (size=5)
		  'trash_size' => int 187017199
		  'total_space' => float 14495514624
		  'used_space' => float 14083430863
		  'system_folders' => 
			array (size=2)
			  'applications' => string 'disk:/Приложения' (length=26)
			  'downloads' => string 'disk:/Загрузки/' (length=23)
		  'free_space' => float 412083761 */
```
		public mixed Disk::get(string $key [, mixed $default = null])
	
	key
		Индекс
		
	default
		Значение по умолчанию, может является функцией - функция будет выполнена с параметров текущего контекста (Disk) и возвращен результат

		$disk->get('total_space');
		 
		/* float 14495514624 */
		
		$disk->used_space;
		
		/* float 14083430863 */
		
		$disk['system_folders'];
		
		/* array (size=2)
		  'applications' => string 'disk:/Приложения' (length=26)
		  'downloads' => string 'disk:/Загрузки/' (length=23) */

2. Работа с ресурсами на диске
	
		public Disk\Resource Disk::resource([string $path = null [, int $limit = 20 [, int $offset = 0]]])
	
	path
		Путь к новому либо уже существующему ресурсу, NULL Список всех файлов
	
	limit
		Количество файлов на выборку
		
	offset
		Смещение
	
	Возвращает объект Disk\Resource
	
2.1. Получить список всех файлов, исключая вложенность в папки

	$files = $disk->resource();
	
	foreach ($files as $file)
	{
		/* $file является объектом Disk\Resource, поэтому здесь доступны delete(...), upload(...) и прочие методы */

		$file = $file->getContents();
		
		var_dump($file);
		
	}

	/* array (size=11)
	  'public_key' => string '07O2zk07o3LHAyOlsRHcQ2tklzrfFttYId69FQeQ=' (length=44)
	  'name' => string '#AVG Meek Mill feat. Drake - Amen (Mighty Mi & Slugworth Trap Mix)_trapsound.ru.mp3' (length=83)
	  'created' => string '2014-11-09T09:20:05+00:00' (length=25)
	  'public_url' => string 'https://yadi.sk/d/Mo5u3UW1d9Rr4' (length=31)
	  'modified' => string '2014-11-09T09:20:05+00:00' (length=25)
	  'media_type' => string 'audio' (length=5)
	  'path' => string 'disk:/749/#AVG Meek Mill feat. Drake - Amen (Mighty Mi & Slugworth Trap Mix)_trapsound.ru.mp3' (length=113)
	  'md5' => string '7822e71a92fc6eea9dacc1ef547889a8' (length=32)
	  'type' => string 'file' (length=4)
	  'mime_type' => string 'audio/mpeg' (length=10)
	  'size' => int 6995765 */
	
Для любой подобной операции доступна фильтрация. см. ниже

2.2. Работа с одним ресурсом
	
	$file = $disk->resource('#AVG Meek Mill feat. Drake - Amen (Mighty Mi & Slugworth Trap Mix)_trapsound.ru.mp3');
	
2.2.1 Получить информацию
	
	$file->getContents();
	
	$file->get('name');
	
	$file->size;
	
	$file['public_url'];
	
	public mixed Disk::get(string $key [, mixed $default = null])
	
key	Индекс
		
default	Значение по умолчанию, может является функцией - функция будет выполнена с параметров текущего контекста (Disk\Resource) и возвращен результат
	
2.2.2. Проверить на существование

	$file->has()
	
2.2.3. Проверить существует ли свойство или любое другое поле

	$file->has('custom_properties');
	
второй параметр возвращает значение по умолчанию, если свойство не существует - т.е. в этом примере пустой массив
	
2.2.4. Добавление метаинформации для ресурса
	
	$file->set('info', 'value');
	
	$file->info = 'value';
	
	$file->any_key = 'any thing';

	$file->{any-key} = 'any thing;
	
	добавленная таким образом информация доступна в "custom_properties"

	$file->get('custom_properties');
	
2.2.5. Удаление метаинформации для ресурса

	$file->set('info');
	
	$file->info = null;
	
2.2.6. Загрузить файл на диск

	public mixed upload(string $path [, mixed $overwrite = false [, Closure $progress = null] ])
	
	path
		Путь до файла
	
	overwrite
		Перезапись true, false - не перезаписывать
		
	progress
		Процесс загрузки
	
Может возвращать false или true, а также строковый идентификатор для загрузки по ссылкам - получить статус операции $disk->operation('идентификатор')
	
Если было включено шифрование файл на Яндекс.Диск запишется уже зашифрованным AES 256 - файл шифруется небольшими порциями, но не целиком сразу.
При скачивании $file->download(...) такой зашифрованный файл будет сразу расшифрован.

Примеры

	$file->upload('http://site.ru/file.ext');
	
	$file->upload('путь до файла');
	
	$file->upload('ссылка на удаленный файл http://....');
	
Перезаписать файл, не работает для ссылок
	
	$file->upload('путь о файла', true);
	
Отследить процесс выполнения загрузки, не работает для ссылок

Третий либо второй параметр нужны для слежения за процессом загрузи

	function($upload_size, $uploaded) {...}

в случае загрузки на диск будут доступны $upload_size, $uploaded, и наоборот в случае скачивания с диска $download_size, $downloaded
	
	$file->upload('путь о файла', function($upload_size, $uploaded) {...});
	
Процесс с перезаписью
	
	$file->upload('путь о файла', true, function($upload_size, $uploaded) {...});
	
Процесс

	$file->upload(__DIR__.'/.../file.ext', function($upload_size, $uploaded) {
		static $prev_size = null;
		
		if ($upload_size === 0)
		{
			return;
		}

		if ($prev_size !== $uploaded)
		{
			$progress_size = 40;
			$fraction_downloaded = $uploaded / $upload_size;
			$dots = round($fraction_downloaded * $progress_size);
			printf('%3.0f%% [', $fraction_downloaded * 100);
			$i = 0;
			for ( ; $i < $dots - 1; $i++)
			{
				echo '=';
			}
			
			echo '>';
			
			for ( ; $i < $progress_size - 1; $i++)
			{
				echo ' ';
			}
			
			echo ']' . "\r\n";
			
			$prev_size = $uploaded;
		}
	});
	
Результат

	0%  [> ]
	3%  [> ]
	6%  [==> ]
	9%  [===> ]
	16% [=====> ]
	19% [=======> ]
	28% [==========> ]
	31% [============> ]
	38% [==============> ]
	44% [=================> ]
	47% [==================> ]
	50% [===================> ]
	53% [====================> ]
	57% [======================> ]
	60% [=======================> ]
	63% [========================> ]
	66% [=========================> ]
	69% [===========================> ]
	72% [============================> ]
	75% [=============================> ]
	79% [==============================> ]
	82% [================================> ]
	85% [=================================> ]
	88% [==================================> ]
	91% [===================================> ]
	94% [=====================================> ]
	97% [======================================> ]
	100% [=======================================> ]
	
2.2.7. Удаление ресурсов
	
Disk\Resource::delete - удалить файл или папку либо переместить в корзину.

Описание

	public mixed delete ( [bool $permanently = false] )

Перемещает файл или папку в корзину или удаляет ресурс без помещения в корзину. Возвращает идентификатор статуса операции либо объект Disk\ResourceTrash или boolean.

Список параметров

	permanently - Признак безвозвратного удаления, FALSE помещает файл в корзину

Примеры

Поместить ресурс в корзину
	
	$file->delete()
	
Поместить ресурс в корзину
	
	$file->delete(false)
	
Удалить без помещения в корзину
	
	$file->delete(true)

После удаления или помещения в корзину $file->has() вернет false
	
2.2.8. Скачивание файла
	
	public mixed download(string $path [, mixed $overwrite = false [, Closure $progress = null] ])
	
Поведение идентично upload
Зашифрованный файл расшифровывается сам, для этого не обязательно включать для ресурса шифрование.
	
	path
		Куда будет загружен файл, например "путь до файла/файл.txt"
	
	overwrite
		Если по такому путь есть файл, например есть уже файл "путь до файла/файл.txt" чтобы его перезаписать нужно передать true
		
2.2.9. Копирование файла или папки

	$file->copy('новое имя копии файла');
	
с перезаписью
	
	$file->copy('новое имя копии файла', true);

или другой объект Disk\Resource, здесь также работает второй параметр - перезапись
	
	$file2 = $disk->resource('другой_существующий_или_нет_файл');
	$file->copy($file2);
	
2.2.10. Перемещение

	Идентично копированию, но тут есть возможность передавать в качестве имени публичный файл и его объект Disk\ResourcePublish
	
	$public = $disk->publish('идентификатор');
	$file->move($public);
	
	Такой публичный ресурс будет скопирован
	
	Важно: данная возможность сейчас не работает
	
2.2.11. Публикация ресурса
	
	$file->publish();
	
	или
	
	$file->publish(true);
	
2.2.12. Закрытие доступа
	
	$file->publish(false);

2.2.13. Создание папки

	Если ресурса с таким же именем нет
	
	$file->create();
	
2.2.14. Восстановление файла или папки из Корзины

	$file->restore('новое имя или Disk\Resource', 'перезаписать ?');
	
2.2.15. Удаление из корзины

	Если файл помещен в корзину, удалить из корзины
	
	$file->trash();
	
2.2.16. Получить путь к ресурсу

	$path = $file->getPath();
	
	/* string 'disk:/blah.txt' */

3. Фильтры

Все ресурсы поддерживают фильтры, некоторые ресурсы поддеживают не все. Вызов таких методов без параметров или с NULL возвращает текущее значение. 
Эти методы, как и весь SDK, поддерживают "сцепку" при изменении значений.

	$files->preview('XXL')
		->preview_crop(true)
		->offset(50)
		->media('image');

3.1. Ограничение на количество

	public mixed limit([integer $limit = null [, integer $offset = null]])
	
limit	количество
offset	смещение

3.2. Смещение

	public mixed offset([integer $offset = null])
	
Например
	
	$diks->resource()
		->offset(100);
		
	/* array <...> */
	
3.3. Сортировка

Атрибут, по которому сортируется список ресурсов, вложенных в папку. Поддерживается: name, path, created, modified, size.

	public mixed sorting([string $sort = null [, boolean $inverse = false]])
	
sort	один из поддерживаемых атрибутов
inverse	сортировать в обратном порядке TRUE

3.4. Размер уменьшенного превью файла

Допустимые значения размера превью - S, M, L, XL, XXL, XXXL, <ширина>, x<высота>, <ширина>x<высота>

	public mixed preview([mixed $preview = null])
	
3.5. Обрезать превью согласно размеру

	public mixed preview_crop([boolean $crop = null])
	
3.6. Тип файлов, которые нужно включить в список

Допустимые значения - audio, backup, book, compressed, data, development, diskimage, document, encoded, executable, flash, font, image, settings, spreadsheet, text, unknown, video, web
	
	public mixed media([string $media_type = null])
	
3.7. Относительный путь к ресурсу внутри публичной папки

	public mixed path([string $path = null])

Имеется папка с открытым доступом, метод поможет выбрать какой-то файл внутри этой папки.

3.8. Тип ресурса

Допустимые значения - file, dir

	public mixed type([string $type = null])

4. Шифрование

Все ресурсы (а именно файлы) на диске, которые не в корзине, определённые с помощью контекста $disk->resource(...) могут быть зашифрованы при загрузке (и храниться в зашифрованном виде) а расшифрованы при скачивании.

4.1. Включить шифрование

	public mixed encryption([mixed $encryption = null])
	
encryption	может установить парольную фразу, или TRUE для включения шифрования
	
По умолчанию в качестве фразы используется название алгоритма. Алгоритм не изменяется и меняться не должен.

	if ($file->encryption())
	{
		// шифрование включено
	}

4.2. Отключить шифрование

	$file->encryption(false)
		->upload(...) // файл будет загружен как есть

4.3. Секретная фраза

	public mixed phrase([string $phrase = null])

По умолчанию в качестве фразы используется название алгоритма. Хранить фразу у себя не обязательно, файл при скачивании расшифруется автоматически.

4.4. Вектор инициализации

	public mixed vector([mixed $property = null])
	
Не устанавливайте свой вектор. Файл может правильно расшифроваться только с изначальным вектором.

property
NULL получить текущий вектор
TRUE установить случайный
FALSE получить и стереть вектор
Строка - установить свой

4.5. Зашифрован ли файл

	public boolean hasEncrypted( void )

5. Операции

Иногда если вы, например, удаляете большую папку это может занять некоторое время - ваш код не ждет когда завершится удалении, а статус операции можно проверить по идентификатору операции, который возвращается нужным методов (например delete()).

5.1. Статус

	public mixed operation([string $identifier = null])
	
Получить

	$disk->operation('идентификатор');
	
5.2. Все операции

За цикл php скрипт может выполнить много разных манипуляций с ресурсами, некоторые могут потребовать некоторое время на их выполнение (например, копирование большого файла). Вы можете получить список всех идентификаторов, которые имели место быть за время жизни скрипта.

	$disk->operation();
	
	/*
		array(
			'идентификатор',
			'идентификатор',
			'идентификатор',
			...
		)	
	*/
	
	Идентификаторы не хранятся, а собираются за весь цикл. При следующем запуске этого же скрипта, данный список будет пуст.
	
6. Последние загруженные файлы

Список можно фильтровать по типу файла (аудио, видео, изображение и т. д.). Диск определяет тип каждого файла при загрузке.

	public Disk\ResourceList uploaded([integer $limit = 20 [, integer $offset = 0==)
	
	$disk->uploaded()
		->media('audio')
		->getContents();
		
	/*
		array (
			0 => Disk\Resource {
				...
			}
		)
	*/

7. Работа с опубликованными ресурсами

Вы можете обращаться с помощью ключа к чужим ресурсам. Тут доступны фильтры.

	public Disk\ResourceList publish([string $public_key = null [, integer $limit = 20 [, integer $offset = 0]]])
	
7.1. Получение списка опубликованных файлов и папок

	$files = $disk->publish()
	
Этот метод требует аунтификации. Список будет предоставлен от вашего имени и, например, получив ссылку на скачивание или ссылку на пред просмотр, кто-то другой не сможет воспользоваться этой ссылкой т.е. в такие моменты вам нужно будет работать с одним ресурсом. Так устроен Яндекс.Диск.
	
	foreach ($files->getContents() as $picture)
	{
		$picture = $disk->publish($picture->getPublicKey())
			->getLink();
		
		// ...
	}

7.2. Работа с одним

	$file = $disk->publish('public_key')

7.3. Получить публичный ключ

	$file->getPublicKey()
	
7.4. Получает информацию о ресурсе

	$file->getContents()

7.5. Есть такой ресурс или свойство

	$file->has();
	
	$file->has('property');
	
7.6. Получить прямую ссылку

	$file->getLink();
	
7.7. Скачивание публичного файла или папки

	public mixed download ( [string $path [, mixed $overwrite = false [, Callable $progress = null]]] )
	
Описанное ранее справедливо и тут, кроме расшифровывания зашифрованных файлов.

7.8. Такой же ресурс есть на моем аккаунте Яндекс.Диска ?

	$file->hasEqual()
	
7.9. Сохранение публичного файла в «Загрузки» или отдельный файл из публичной папки

	public mixed save([string $name = null [, string $path = null]])
	
name	новое имя, поддерживается Disk\Resource

8. Работа с ресурсами в корзине

Возможно очистить корзину. Тут доступны фильтры.

8.1. Список файлов в корзине

	$disk->trash();

	Доступные фильтры:
	
	limit 			Количество выводимых вложенных ресурсов.
	
	offset			Смещение от начала списка вложенных ресурсов.
	
	preview_crop 	Разрешить обрезку превью.
	
	preview 		Размер превью.
	
	sorting			Сортировка вложенных ресурсов (deleted, created).
	
8.2. Очистка Корзины

	$disk->trash(true);
	
8.3. Ресурс в корзине

	$file = $disk->trash('имя ресурса');
	
Одинаковые ресурсы, после удаления имеют названия "ресурс_<timestamp>"

8.4. Есть ресурс в корзине или свойство

	$file->has()
	
8.5. Получить информацию

	$file->getContents()
	
8.6. Удалить из корзины

	$file->delete()
	
8.7. Восстановить

	public mixed restore([string $name = null [, boolean $overwrite = false]])

8.8. Путь к ресурсу

	public string getPath( void )

9. Обработка исключений

SDK вызывает только искючения, и имеет свой набор исключений на типовые события. Хорошо написанное приложение всегда должно перехватываеть исключения.

9.1. Набор исключений

	Mackey\Yandex\Exception\AlreadyExistsException	исключение бывает там, где могут встретиться два ресурса с одинаковыми именами (при upload(), move(), и т.д.)
	
	Mackey\Yandex\Exception\ForbiddenException	тогда, когда невозможно выполнить операцию из-за, например, недостаточных привилегий (например, за приделами папки приложения, но не только!)
	
	Mackey\Yandex\Exception\NotFoundException	ресурс по искомому пути отсутствует
	
	Mackey\Yandex\Exception\OutOfSpace	закончилось свободное место (Важно: размер метаинформации ограничен, из-за чего может быть не возможно шифрование. Есть над чем работать)
	
	Mackey\Yandex\Exception\UnauthorizedException	нужно авторизоваться
	
	Mackey\Yandex\Exception\UnsupportedException	в том случае, когда что-то пошло не так (информацию, о том что случилось можно получить по коду $exc->getCode() и посмотреть в документации Яндекс.Диска или в кратком описании $exc->getMessage())
	
	Важно: SDK всегда сохраняет всю цепочку предыдущих искючений, естественно если произошло несколько вызовов исключений.
	
	В PHP есть и встроенные исключения.
	
9.2. Как с этим работать ?

	Например так.
	
```php
	try
	{
		// Передать токен
		$disk = new Mackey\Yandex\Disk('OAuth-токен');
		
		try
		{
			// Получить ресурс
			$file = $disk->resource('wakeup.txt');
			
			// До этого момента запросов к Диску не было
			// Только сейчас был сделан запрос на получение информации о ресурсе к Яндекс.Диску
			var_dump($file->getContents());
		}
		catch (Mackey\Yandex\Exception\NotFoundException $exc)
		{
			// Ресурс на Диске отсутствует, загружу
			$file->upload(__DIR__.'/to_upload.txt');
		}
		
		// Теперь удалю в корзину
		$file->delete();
		
		// Проверю, существет теперь ресурс на Дске ? Он в корзине
		var_dump($file->has());
	}
	catch (Mackey\Yandex\Exception\UnauthorizedException $exc)
	{
		// Записать в лог, авторизоваться не удалось
		// log($exc->getMessage()); // не авторизован
	}
	catch (Exception $exc)
	{
		// Что-то другое
	}
```

... продолжение следует