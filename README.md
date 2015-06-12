
Яндекс.Диск использует OAuth-токен для аунтификации, зарегистрировать приложение и получить токен https://oauth.yandex.ru/

после получения OAuth-токена его можно использовать слудующим образом

	$client = new Mackey\Yandex\Client('token')
	
	или 
	
	$client->token('token')
	
	или 

	$disk = new Mackey\Yandex\Disk('token')
	
	или
	
	$disk = new Mackey\Yandex\Disk($client)
	
	или 
	
	$disk->token('token')


Работа с диском разделена на несколько методов, так для ресурсов, которые расположены в корзине нужно использовать $disk->trash(...), а для тех что опубликоаны $disk->publish(...)

1. Информация о диске

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
	
	Для любой подобновй операции доступна фильтрация. см. ниже

2.2. Работа с одним ресурсом
	
	$file = $disk->resource('#AVG Meek Mill feat. Drake - Amen (Mighty Mi & Slugworth Trap Mix)_trapsound.ru.mp3');
	
2.2.1 Получить информацию
	
	$file->getContents();
	
	$file->get('name');
	
	$file->size;
	
	$file['public_url'];
	
	public mixed Disk::get(string $key [, mixed $default = null])
	
	key
		Индекс
		
	default
		Значение по умолчанию, может является функцией - функция будет выполнена с параметров текущего контекста (Disk\Resource) и возвращен результат
	
2.2.2. Проверить на существование

	$file->has()
	
2.2.3. Проверить существует ли свойство или любое другое поле

	$file->has('custom_properties', []);
	
	второй параметр возвращает значение по умолчанию, если свойсто не сущестует - т.е. в этом примере пустой массив
	
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
		Перезапить true, false - не перезаписывать
		
	progress
		Процесс загрузки
	
	Может возвращать false или true, а также строковый идентификатор для загрузки по ссылкам - получить статус операции $disk->operation('идентификатор')

	$file->upload('путь до файла');
	
	$file->upload('ссылка на удаленный файл http://....');
	
	Перезаписать файл, не работает для ссылок
	
	$file->upload('путь о файла', true);
	
	Отследить процесс выполнения загрузки, не работает для ссылок
	
	Третий либо второй параметр нужны для слежения за процессом загрузи, для файлов загржаемых из сети параметр не доступен

	function($upload_size, $uploaded) {...}

	в случае загрузки на диск будут доступны $upload_size, $uploaded, и наоборот в случае скачивания с диска $download_size, $downloaded
	
	$file->upload('путь о файла', function($upload_size, $uploaded) {...});
	
	Процесс с перезаписью
	
	$file->upload('путь о файла', true, function($upload_size, $uploaded) {...});
	
2.2.7. Удаление ресурсов
	
	public mixed delete([bool $permanently = false])
	
	permanently
		Признак безвозвратного удаления, FALSE помещает файл в корзину
		
	Возвращается идентификатор выполнения операции либо boolean
	
	$file->delete()
	
	удалить без помещения в корзину
	
	$file->delete(true)
	
	После удаления или помещения в корзину $file->has() // вернет false
	
2.2.8. Скачивание файла
	
	public mixed download(string $path [, mixed $overwrite = false [, Closure $progress = null] ])
	
	Поведение идентично upload
	
	path
		Куда будет загружен файл, например "путь до файла/файл.txt"
	
	overwrite
		Если по такому путь есть файл, например есть уже файл "путь до файла/файл.txt" чтобы его перезаписать нужно передать true
		
2.2.9. Копирование файла или папки

	$file->copy('новое имя копии файла');
	
	с перезаписью
	
	$file->copy('новое имя копии файла', true);

	или другой объект Disk\Resource, здесь также работает второй параметр - перезапись
	
	$file2 = $disk->resource('друго_существующий_или_нет_файл');
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

	Если ресурса с таким же имененм нет
	
	$file->create();
	
2.2.14. Восстановление файла или папки из Корзины

	$file->restore('новое имя или Disk\Resource', 'перезаписать ?');
	
	Важно: метод работает неоднозначно чаще всего файл не будет восстановлен
	
2.2.15. Удаление из корзины

	Если файл помещен в корзину, удалить из корзины
	
	$file->trash();
	
	Важно: метод работает неоднозначно чаще всего файл не будет удален
	
2.2.16. Получить путь к ресурсу

	$path = $file->getPath();
	
	/* string 'disk:/blah.txt' */
	
... продолжение следует