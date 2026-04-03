<?php

namespace MaxBotSdk\DTO;

/**
 * Результат загрузки файла.
 *
 * @since 1.0.0
 */
final class UploadResult extends AbstractDto
{
    /** @var string */
    private $url;

    /** @var string */
    private $token;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->url = self::getString($data, 'url');
        
        $token = self::getString($data, 'token');
        if (empty($token)) {
            array_walk_recursive($data, function ($value, $key) use (&$token) {
                if ($key === 'token') {
                    $token = (string) $value;
                }
            });
        }
        $this->token = (string) $token;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /**
     * Создать из URL загрузки (результат getUploadUrl).
     *
     * @param string $url
     * @return self
     */
    public static function fromUrl($url)
    {
        return new self(['url' => $url]);
    }

    /**
     * Создать из токена (результат uploadFileToUrl).
     *
     * @param string $token
     * @return self
     */
    public static function fromToken($token)
    {
        return new self(['token' => $token]);
    }

    /** @return string */
    public function getUrl()
    {
        return $this->url;
    }

    /** @return string */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'url'   => $this->url,
            'token' => $this->token,
        ];
    }
}
