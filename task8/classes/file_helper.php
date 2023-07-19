<?php

/**
 * Файл со вспомогательными функциями для получения информации
 * о файле с Яндекс.Диска.
 */
class FileHelper 
{
    /**
     * Получить размер файла в удобном для просмотра виде.
     * @param int $fileSizeInBytes Размер файла в байтах.
     * @return string $fileSize Размер файла с указанием единицы измерения.
     */
    public static function getFileSize($fileSizeInBytes) 
    {
        if ($fileSizeInBytes < 1024) {
            return $fileSizeInBytes . " Б";
        } elseif ($fileSizeInBytes < 1024*1024) {
            return round($fileSizeInBytes / 1024, 2) . " Кб";
        } elseif ($fileSizeInBytes < 1024*1024*1024) {
            return round($fileSizeInBytes / 1024 / 1024, 2) . " Мб";
        } else {
            return round($fileSizeInBytes / 1024 / 1024 / 1024, 2) . " Гб";
        }
    }

    /**
     * Преобразовать дату-время в удобочитаемый формат.
     * @param 
     */
    public static function getFileDate($date)
    {
        $parts = explode('T', $date);
        $time = explode('+', $parts[1])[0];
        $day = DateTime::createFromFormat('Y-m-d', $parts[0])->format('d.m.Y');
        return $time . ' ' . $day;
    }
}