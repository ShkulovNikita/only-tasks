<?php

namespace AppClasses;

/**
 * Файл со вспомогательными функциями для отображения
 * информации о файлах.
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
     * @param DateTime $date Дата.
     * @return string Дата в преобразованном формате.
     */
    public static function getFileDate($date)
    {
        $parts = explode('T', $date);
        $time = explode('+', $parts[1])[0];
        $day = \DateTime::createFromFormat('Y-m-d', $parts[0])->format('d.m.Y');
        return $time . ' ' . $day;
    }

    /**
     * Получить иконку-превью для файла.
     * @param string $mimeType Тип файла.
     * @return string Ссылка на локальный файл иконки.
     */
    public static function getFilePreview($mimeType)
    {
        $serverPath = 'images/previews/';
        if (str_contains($mimeType, 'text/plain')) {
            return $serverPath . 'file-earmark-text.svg';
        } elseif (str_contains($mimeType, 'image')) {
            return $serverPath . 'file-earmark-image.svg';
        } elseif (str_contains($mimeType, 'video')) {
            return $serverPath . 'file-earmark-play.svg';
        } elseif (str_contains($mimeType, 'x-dosexec')) {
            return $serverPath . 'filetype-exe.svg';
        } elseif (str_contains($mimeType, 'audio')) {
            return $serverPath . 'file-earmark-music.svg';
        } else {
            return $serverPath . 'file-earmark.svg';
        }
    }
}