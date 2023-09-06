<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

class Credentials
{
    private string $host;
    private string $clientID;
    private string $clientSecret;
    private string $user;
    private string $password;
    private string $channel;

    /**
     * Credentials constructor.
     *
     * @param string $host
     * @param string $clientId
     * @param string $clientSecret
     * @param string $user
     * @param string $password
     * @param string $channel
     */
    public function __construct(string $host, string $clientId, string $clientSecret, string $user, string $password, string $channel)
    {
        $this->host = $host;
        $this->clientID = $clientId;
        $this->clientSecret = $clientSecret;
        $this->user = $user;
        $this->password = $password;
        $this->channel = $channel;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getClientID(): string
    {
        return $this->clientID;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }
}
