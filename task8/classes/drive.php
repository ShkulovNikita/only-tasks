<?php

namespace AppClasses;

require "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

/**
 * Класс, реализующий работу с библиотекой для Яндекс.Диска.
 */
class Drive
{
    /**
     * Получить список файлов в папке приложения на Яндекс.Диске.
     * @param string $subdir Подпапка внутри папки приложения.
     * @return array Коллекция файлов на Диске.
     */
    public static function getFiles($subdir = '')
    {
        try {
            /**
             * Получить лимит и смещение файлов.
             */
            $pageLimit = PageNavigator::getPageLimit();
            $pageOffset = PageNavigator::getOffset();
            /*
             * Получить файлы с указанными лимитом и смещением. 
             */
            $appFolderResource = self::getResource($subdir, $pageLimit, $pageOffset);
            if ($appFolderResource !== false) {
                /*
                 * Коллекция файлов в папке. 
                 */
                $files = $appFolderResource->items;
                return $files;
            } else {
                return [];
            }
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);          
            return [];  
        }
    }
    
    /**
     * Загрузка файла на Яндекс.Диск.
     * @param string $subdir Подпапка внутри папки приложения.
     * @return string Имя загруженного файла.
     */
    public static function uploadFile($subdir = '')
    {
        /*
         * Путь до файла.
         */
        $filePath;
        /*
         * Имя файла.
         */
        $fileName;
        /*
         * Тип: загружается файл с устройства или через URL.
         */
        $sourceType;
        /*
         * Определить, задан ли файл ссылкой или загружен пользователем с устройства. 
         */
        if (isset($_POST['type']) && $_POST['type'] == 'url') {
            if (isset($_POST['fileurl']) && !empty($_POST['fileurl'])) {
                self::getFileUrl($filePath, $fileName);
                $sourceType = 'url';
            }
        } else {
            if ($_FILES && $_FILES['filename']['error'] == UPLOAD_ERR_OK) {
                self::getFilePath($filePath, $fileName);
                $sourceType = 'file';
            }
        }
        /*
         * Загрузить файл на Диск. 
         */
        self::uploadFileToYandex($filePath, $fileName, $sourceType, $subdir);

        return $fileName;
    }

    /**
     * Удаление файла с Яндекс.Диска.
     * @param string $fileName Имя файла на Диске.
     * @param string $subdir Подпапка внутри папки приложения.
     */
    public static function deleteFile($fileName, $subdir = '')
    {
        try {
            if ($fileName === '') {
                Session::setValue('error', 'Не указан файл.');
            } else {
                /*
                 * Получить файл как ресурс.
                 */
                $fileResource = self::getResource($subdir . $fileName);
                $exists = false;
                if ($fileResource) {
                    /*
                     * Проверить, существует ли он на Диске. 
                     */
                    $exists = $fileResource->has();
                }
                if ($exists) {
                    $fileResource->delete();
                }
            }
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
        }
    }

    /**
     * Просмотр файла.
     * @param string $fileName Имя файла на Диске.
     * @param string $subdir Подпапка внутри папки приложения.
     * @return object Файл как ресурс.
     */
    public static function viewFile($fileName, $subdir = '')
    {
        try {
            if ($fileName === '') {
                Session::setValue('error', 'Не указан файл.');
                return '';
            }
            /*
             * Получить файл как ресурс.
             */
            $fileResource = self::getResource($subdir . $fileName);
            $exists = false;
            if ($fileResource) {
                /*
                 * Проверить, существует ли он на Диске. 
                 */
                $exists = $fileResource->has();
            }
            if ($exists) {
                return $fileResource;
            } else {
                Session::setValue('error', 'Указанный файл не существует.');
                return '';
            }
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return '';
        }
    }

    /**
     * Получить файл как ресурс.
     * @param string $fileName Имя файла.
     * @param string $subdir Подпапка внутри папки приложения.
     * return Resource/Closed|bool Файл как ресурс либо false.
     */
    public static function getFile($fileName, $subdir = '')
    {
        $fileResource = self::getResource($subdir . $fileName);
        if ($fileResource) {
            return $fileResource;
        } else {
            return false;
        }
    }

    /**
     * Скачивание файла из Яндекс.Диска.
     * @param string $fileName Имя файла на Диске.
     * @param string $subdir Подпапка внутри папки приложения.
     */
    public static function downloadFile($fileName, $subdir = '')
    {
        try {
            /*
             * Получить файл как ресурс.
             */
            $fileResource = self::getResource($subdir . $fileName);
            /*
             * Проверить, существует ли он на Диске. 
             */
            $exists = false;
            if ($fileResource) {
                $exists = $fileResource->has();
            }
            if ($exists) {
                /*
                 * Сохранить файл в папку temp. 
                 */
                $fileServerPath = Application::getDownloadPath() . $fileName;
                $fileTempDownloadResult = $fileResource->download($fileServerPath, true);
                /*
                 * Если файл был успешно загружен на сервер, то передать его пользователю. 
                 */
                if ($fileTempDownloadResult) {
                    $contentType = $fileResource->mime_type;

                    $file = $fileServerPath;
                    header("Content-Type: $contentType");
                    header('Content-Disposition: attachment; filename="' . $fileResource->name . '"');
                    readfile($file);
                    /*
                     * Удалить файл с сервера. 
                     */
                    unlink($fileServerPath);
                }
            } 
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
        }
    }

    /**
     * Добавить и отредактировать метаинформацию о файле.
     * @param Resource/Closed $file Файл как ресурс.
     * @return array Массив свойств, которые не удалось добавить.
     */
    public static function editProperties($file)
    {
        $newProperties = array_combine(
            $_POST['newPropertyKey'], 
            $_POST['newPropertyValue']
        );
        $errors = '';
        /*
         * Отредактировать уже существующие свойства. 
         */
        self::editProps($file, $errors);
        /*
         * Добавить новую метаинформацию.
         */
        $incorrectProps = self::addProperties($file, $newProperties, $errors);
        /*
         * Отобразить ошибки, если есть. 
         */
        if ($errors !== '') {
            Session::setValue('error', 'Ошибки:<br>' . $errors);
        }

        return $incorrectProps;
    }

    /**
     * Сохранить файл с Яндекс.Диска для редактирования.
     * @param string $fileName Имя файла на Диске.
     * @param string $subdir Подпапка внутри папки приложения.
     * @return int|bool Число - удалось скачать файл, иначе false.
     */
    public static function downloadEditFile($fileName, $subdir = '')
    {
        try {
            /*
             * Получить файл как ресурс.
             */
            $fileResource = self::getResource($subdir . $fileName);
            /*
             * Проверить, существует ли он на Диске. 
             */
            $exists = false;
            if ($fileResource) {
                $exists = $fileResource->has();
            }
            if ($exists) {
                $serverPath = Application::getEditPath() . $fileName;
                $fileDownloadResult = $fileResource->download($serverPath, true);
                return $fileDownloadResult;
            }
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return false;
        }
    }

    /**
     * Загрузить файл с хранилища на сервере на Яндекс.Диск.
     * @param string $filePath Место хранения файла на сервере.
     * @param string $fileName Имя файла.
     * @param string $sourceType Тип загрузки файла, 'file' либо 'url'.
     * @param string $subdir Подпапка внутри папки приложения.
     */
    public static function uploadFileToYandex($filePath, $fileName, $sourceType, $subdir = '')
    {
        try {
            if (!empty($filePath)) {
                $resource = self::getResource($subdir . $fileName);
                $resource->upload($filePath, true, false);

                if ($sourceType === 'file') {
                    /*
                     * Удалить файл после загрузки.
                     */
                    unlink($filePath);
                }
            }
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return;
        }
    }

    /**
     * Добавить к файлу метаинформацию, которой у него не было 
     * задано ранее.
     * @param Resource/Closed $file Файл как ресурс.
     * @param array $properties Массив пар ключ-значение,
     * представляющие собой добавляемую к файлу метаинформацию.
     * @param string $errors Переменная, в которую записываются ошибки,
     * возникшие в процессе работы метода.
     * @return array Массив пар ключ-значение, которые не были добавлены.
     */
    public static function addProperties($file, $properties, &$errors)
    {
        $incorrectProps = [];
        foreach ($properties as $propKey => $propValue) {
            $propKey = htmlspecialchars($propKey);
            $propValue = htmlspecialchars($propValue);
            /* 
             * Пропустить полностью незаполненные формы. 
             */
            if ($propKey == '' && $propValue == '') {
                continue;
            }
            /*
             * Если не задан ключ либо значение, 
             * то данное значение игнорируется с выводом ошибки. 
             */
            if ($propKey == '') {
                $errors .= "- Пустой ключ для значения $propValue.<br>";
                $incorrectProps[$propKey] = $propValue;
                continue;
            }
            if ($propValue == '') {
                $errors .= "- Пустое значение свойства для ключа $propKey.<br>";
                $incorrectProps[$propKey] = $propValue;
                continue;
            }
            /*
             * Добавить метаинформацию файлу через API. 
             */
            try {
                $file->set($propKey, $propValue);
            } catch (\Exception $ex) {
                $errors .= '- ' . $ex . "<br>";
                $incorrectProps[$propKey] = $propValue;
                continue;
            }
        }
        /*
         * Вернуть те пары ключ-значение, которые не удалось добавить. 
         */
        return $incorrectProps;
    }

    /**
     * Проверить размер загружаемого файла.
     * @return bool true - корректный размер файла, false - слишком большой.
     */
    public static function checkFileSize()
    {
        if (
            isset($_SERVER['CONTENT_LENGTH']) 
            && (int) $_SERVER['CONTENT_LENGTH'] > Application::getFileLimit()
        ) {
            Session::setValue('error', 'Файл слишком большой.');
            return false;
        }
        return true;
    }

    /**
     * Отредактировать метаинформацию файла.
     * @param Resource/Closed $file Файл как ресурс.
     * @param string $errors Переменная, в которую записываются ошибки,
     * возникшие в процессе работы метода.
     */
    private static function editProps($file, &$errors)
    {
        /*
         * Получить массив с метаинформацией о файле. 
         */
        $fileProps = $file->getProperties();
        /*
         * Если свойств нет, то обновлять ничего не нужно. 
         */
        if (!$fileProps) {
            return;
        }
        /*
         * Найти в массиве $_POST новые значения свойств. 
         */
        foreach ($fileProps as $propKey => $propValue) {
            /*
             * Удалить свойства для значений, которые не были переданы (пустые).
             */
            if (!isset($_POST[$propKey])) {
                self::clearProperty($file, $propKey, $errors);
                continue;
            }
            /*
             * Значение из формы. 
             */
            $newValue = $_POST[$propKey];
            /*
             * Если новое значение пустое, то удалить свойство. 
             */
            if ($newValue == '') {
                self::clearProperty($file, $propKey, $errors);
            } elseif ($newValue != $propValue) {
                /*
                 * Если старое и новое значения не совпадают,
                 * то обновить значение в файле. 
                 */
                try {
                    $file->set($propKey, $newValue);
                } catch (\Exception $ex) {
                    $errors .= '- ' . $ex . "\n";
                    continue;
                }
            }
        }
    }

    /**
     * Удалить свойство метаинформации файла.
     * @param Resource/Closed $file Файл как ресурс.
     * @param string $propKey Ключ удаляемого свойства.
     * @param string $errors Список ошибок.
     */
    private static function clearProperty($file, $propKey, &$errors)
    {
        try {
            $file->set($propKey, null);
        } catch (\Exception $ex) {
            $errors .= '- ' . $ex . "\n";
        }
    }

    /**
     * Получить ресурс по указанному пути.
     * @param string $subResource Подпапка внутри папки приложения либо файл.
     * @param int $limit Количество получаемых с Диска файлов в запросе.
     * @param int $offset Смещение в запросе.
     * @return object|bool Ресурс на Яндекс.Диске либо false.
     */
    private static function getResource($subResource = '', $limit = 20, $offset = 0)
    {
        try {
            /*
             * Получить объект для работы с диском.
             */
            $disk = new \Arhitector\Yandex\Disk(User::getToken());
            /*
             * Получить указанную папку как ресурс.
             */
            $appResource = $disk->getResource('app:/' . $subResource, $limit, $offset);
            return $appResource;
        } catch (\Arhitector\Yandex\Client\Exception\UnauthorizedException $ex) {
            Session::setValue('error', 'Ошибка авторизации: ' . $ex);
            return false;
        } catch (\InvalidArgumentException $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return false;
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);          
            return false;  
        }
    }

    /**
     * Получить URL файла, указанный пользователем.
     * @return string URL файла.
     */
    private static function getFileUrl(&$filePath, &$fileName)
    {
        $urlValue = htmlspecialchars($_POST['fileurl']);
        $filePath = $urlValue;
        /*
         * Получить имя файла из URL.
         */
        $parts = explode('/', $urlValue);
        if (count($parts) > 0) {
            $fileName = end($parts);
        } else {
            $fileName = '';
        }
    }

    /**
     * Получить путь файла во временной папке на сервере.
     * @return string Путь до файла, загруженного на сервер в папку temp.
     */
    private static function getFilePath(&$filePath, &$fileName)
    {
        try {
            $fileName = $_FILES["filename"]["name"];
            $path = "temp/upload/" . $fileName;
            move_uploaded_file($_FILES["filename"]["tmp_name"], $path);

            $filePath = "$_SERVER[DOCUMENT_ROOT]/" . $path;
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            $filePath = '';      
        }
    }
}
