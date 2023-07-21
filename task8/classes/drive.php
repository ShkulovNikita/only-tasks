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
            $appFolderResource = self::getResource($subdir);
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
        try {
            if (!empty($filePath)) {
                $resource = self::getResource($subdir . $fileName);
                $resource->upload($filePath, true, true);

                if ($sourceType === 'file') {
                    /*
                     * Удалить файл после загрузки.
                     */
                    unlink($filePath);
                }
            }
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return false;
        }

        return true;
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
                $fileServerPath = "$_SERVER[DOCUMENT_ROOT]/temp/download/" . $fileName;
                $fileTempDownloadResult = self::downloadFileToServer($fileResource, $fileServerPath);
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
     */
    public static function editProperties($file)
    {
        $newProperties = array_combine(
            $_POST['newPropertyKey'], 
            $_POST['newPropertyValue']
        );
        $errors = '';
        /*
         * Добавить новую метаинформацию.
         */
        self::addProperties($file, $newProperties, $errors);
        /*
         * Отредактировать уже существующие свойства. 
         */
        self::editProps($file, $errors);
        /*
         * Отобразить ошибки, если есть. 
         */
        if ($errors !== '') {
            Session::setValue('error', 'Ошибки:<br>' . $errors);
        }
    }

    /**
     * Сохранить файл с Яндекс.Диска в указанную папку сервера.
     * @param Resource/Closed $file Файл как ресурс.
     * @param string $serverPath Папка на сервере, в которую следует сохранить файл.
     * @return bool true - удалось скачать файл, иначе false.
     */
    public static function downloadFileToServer($file, $serverPath)
    {
        try {
            $fileDownloadResult = $file->download($serverPath, true);
            return $fileDownloadResult;
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return false;
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
     */
    private static function addProperties($file, $properties, &$errors)
    {
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
                $errors .= "- Пустое значение ключа.<br>";
                continue;
            }
            if ($propValue == '') {
                $errors .= "- Пустое значение свойства.<br>";
                continue;
            }
            /*
             * Добавить метаинформацию файлу через API. 
             */
            try {
                $file->set($propKey, $propValue);
            } catch (\Exception $ex) {
                $errors .= '- ' . $ex . "<br>";
                continue;
            }
        }
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
             * Игнорировать значения, которые не были переданы. 
             */
            if (!isset($_POST[$propKey])) {
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
                try {
                    $file->set($propKey, null);
                } catch (\Exception $ex) {
                    $errors .= '- ' . $ex . "\n";
                    continue;
                }
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
     * Получить ресурс по указанному пути.
     * @param string $subResource Подпапка внутри папки приложения либо файл.
     * @return object|bool Ресурс на Яндекс.Диске либо false.
     */
    private static function getResource($subResource = '')
    {
        try {
            /*
             * Получить объект для работы с диском.
             */
            $disk = new \Arhitector\Yandex\Disk(User::getToken());
            /*
             * Получить указанную папку как ресурс.
             */
            $appResource = $disk->getResource('app:/' . $subResource);
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
