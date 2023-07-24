# Задание 8

Веб-приложение со следующими функциями:
- Загрузка файлов на Яндекс.Диск, просмотр информации, скачивание и удаление с Диска.
- Создание и редактирование метаинформации файлов (свойств вида "ключ-значение").
- Редактирование содержимого простых текстовых файлов.

Так как в описании библиотеки для Яндекс.Диска указано, что она работает только с отладочными токенами, то авторизация реализована в виде формы с ссылкой для получения такого токена и поля, в которое нужно его вставить.

Приложение построено по паттерну MVC, где представления отображают страницы для пользователя, уровень модели отвечает за получение и обработку данных от API, а контроллеры обрабатывают запросы пользователя, обращаясь к уровню модели.

### Представления

- [index.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/index.php) - Главная страница, предлагающая пользователю авторизоваться либо выводящая список файлов пользователя, если он уже авторизован.
- [signin.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/signin.php) - Страница авторизации.
- [upload.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/upload.php) - Страница загрузки файлов на Яндекс.Диск.
- [view.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/view.php) - Страница просмотра информации о выбранном файле.
- [edit.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/edit.php) - Страница редактирования выбранного файла.
- [logout.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/logout.php), [download.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/download.php), [delete.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/delete.php), [getfilecontent.php](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/getfilecontent.php) - Не имеют визуального представления, предназначены для вызова соответствующих методов контроллера (выход из системы, скачивание, удаление и получение содержимого файла) и перенаправление на другую страницу.

### Контроллеры

- [AuthorizationController](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/controllers/authorizationcontroller.php) - Выполняет вход и выход пользователя из системы.
- [FileController](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/controllers/filecontroller.php) - Обрабатывает запросы, связанные с работой с файлами на Диске.

### Модель

Модель приложения - это объекты класса Arhitector\Yandex\Disk\Resource\Closed из библиотеки, представляющие собой ресурсы (файлы либо папки) на Яндекс.Диске. Для получения и работы с файлами используется класс [Drive](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/drive.php).

### Вспомогательные классы.

- [User](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/user.php) - Класс для работы с пользователем: авторизация, выход, проверка факта авторизации, получение используемого токена.
- [Application](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/application.php) - Класс для хранения и получения данных самого приложения, таких как ID приложения в Яндексе, ссылка для получения токена, максимальный размер загружаемых файлов из php.ini, используемые пути на сервере для хранения временных файлов.
- [TextEditor](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/texteditor.php) - Класс, реализующий методы для редактирования текстовых файлов.
- [Session](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/session.php) - Класс для задания, получения и удаления значений в сессии.
- [Router](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/router.php) - Класс с методами для переадресации пользователя, в том числе на страницу авторизации в случае, если пользователь не имеет прав для доступа к целевой странице.

#### Классы для представлений.

Вспомогательные классы, используемые в представлениях для вывода повторяющейся HTML-разметки либо преобразования формата выводимых данных.
- [HtmlHelper](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/htmlhelper.php) - Выводит HTML заголовка, хэдера, футера и информационных сообщений.
- [PageNavigator](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/pagenavigator.php) - Используется для создания постраничной навигации.
- [FileHelper](https://github.com/ShkulovNikita/only-tasks/blob/main/task8/classes/filehelper.php) - Преобразует формат вывода некоторых данных о файле, например, конвертация размера файла в байтах в Кб, Мб и Гб; выбор выводимого превью файла по умолчанию (иконки Bootstrap), если у самого файла превью нет.
