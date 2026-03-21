<?php

namespace MaxBotSdk\DTO;

/**
 * Базовый абстрактный класс для всех DTO MAX Bot API.
 *
 * Предоставляет единый контракт и вспомогательные методы для создания
 * типизированных, immutable Data Transfer Objects из данных API.
 *
 * @since 1.0.0
 */
abstract class AbstractDto
{
    /**
     * Создать DTO из массива данных API.
     *
     * Каждый дочерний класс ДОЛЖЕН переопределить этот метод.
     * Базовая реализация вместо abstract static — для совместимости с PHP 5.6,
     * который не поддерживает `abstract static`.
     *
     * @param array $data Данные из ответа API.
     * @return static
     * @throws \BadMethodCallException Если дочерний класс не переопределил метод.
     */
    public static function fromArray(array $data)
    {
        throw new \BadMethodCallException(
            sprintf('Класс %s должен переопределить метод fromArray().', get_called_class())
        );
    }

    /**
     * Сериализовать DTO обратно в массив.
     *
     * @return array
     */
    abstract public function toArray();

    /**
     * Получить строковое значение из массива данных.
     *
     * @param array  $data    Массив данных.
     * @param string $key     Ключ.
     * @param string $default Значение по умолчанию.
     * @return string
     */
    protected static function getString(array $data, $key, $default = '')
    {
        return isset($data[$key]) ? (string) $data[$key] : $default;
    }

    /**
     * Получить строковое значение или null.
     *
     * @param array  $data Массив данных.
     * @param string $key  Ключ.
     * @return string|null
     */
    protected static function getStringOrNull(array $data, $key)
    {
        return isset($data[$key]) ? (string) $data[$key] : null;
    }

    /**
     * Получить целочисленное значение из массива данных.
     *
     * @param array  $data    Массив данных.
     * @param string $key     Ключ.
     * @param int    $default Значение по умолчанию.
     * @return int
     */
    protected static function getInt(array $data, $key, $default = 0)
    {
        return isset($data[$key]) ? (int) $data[$key] : $default;
    }

    /**
     * Получить целочисленное значение или null.
     *
     * @param array  $data Массив данных.
     * @param string $key  Ключ.
     * @return int|null
     */
    protected static function getIntOrNull(array $data, $key)
    {
        return isset($data[$key]) ? (int) $data[$key] : null;
    }

    /**
     * Получить булево значение из массива данных.
     *
     * @param array  $data    Массив данных.
     * @param string $key     Ключ.
     * @param bool   $default Значение по умолчанию.
     * @return bool
     */
    protected static function getBool(array $data, $key, $default = false)
    {
        return isset($data[$key]) ? (bool) $data[$key] : $default;
    }

    /**
     * Получить массив из данных.
     *
     * @param array  $data    Массив данных.
     * @param string $key     Ключ.
     * @param array  $default Значение по умолчанию.
     * @return array
     */
    protected static function getArray(array $data, $key, array $default = [])
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : $default;
    }

    /**
     * Получить массив или null.
     *
     * @param array  $data Массив данных.
     * @param string $key  Ключ.
     * @return array|null
     */
    protected static function getArrayOrNull(array $data, $key)
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : null;
    }
}
