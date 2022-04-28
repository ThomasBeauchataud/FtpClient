<?php

/*
 * The file is part of the WoWUltimate project
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Author Thomas Beauchataud
 * From 27/04/2022
 */

namespace TBCD\FtpClient\Client;

use TBCD\FtpClient\Exception\FtpClientException;
use FTP\Connection;

/**
 * @author Thomas Beauchataud
 * @since 27/04/2022
 */
class FtpClient implements FtpClientInterface
{

    /**
     * @var string
     */
    private string $host;

    /**
     * @var string
     */
    private string $user;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var bool
     */
    private bool $passive;

    /**
     * @var bool
     */
    private bool $keepAlive;

    /**
     * @var Connection|null
     */
    private ?Connection $connection = null;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->port = $config['port'];
        $this->passive = $config['passive'];
        $this->keepAlive = $config['keepAlive'];
    }


    /**
     * @inheritDoc
     */
    public function download(string $remoteFilePath, string $localFilePath, int $mode = FTP_ASCII): void
    {
        if (!ftp_get($this->getConnection(), $localFilePath, $remoteFilePath, $mode)) {
            throw new FtpClientException();
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function upload(string $localFilePath, string $remoteFilePath, int $mode = FTP_ASCII): void
    {
        if (!ftp_put($this->getConnection(), $remoteFilePath, $localFilePath, $mode)) {
            throw new FtpClientException();
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function rename(string $oldFilePath, string $newFilePath): void
    {
        if (!ftp_rename($this->getConnection(), $oldFilePath, $newFilePath)) {
            throw new FtpClientException();
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function exists(string $filePath): bool
    {
        $output = ftp_size($this->getConnection(), $filePath) > 0;
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function mkdir(string $directoryPath): void
    {
        if (!ftp_mkdir($this->getConnection(), $directoryPath)) {
            throw new FtpClientException();
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $filePath): void
    {
        if (!ftp_delete($this->getConnection(), $filePath)) {
            throw new FtpClientException();
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function list(string $directoryPath, $excludeDefault = true): array
    {
        $list = ftp_nlist($this->getConnection(), $directoryPath);
        if (!$list) {
            throw new FtpClientException();
        }
        if ($excludeDefault) {
            $list = array_filter($list, function ($element) {
                return $element !== '.' && $element !== '..';
            });
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function isValidConnexion(): bool
    {
        try {
            $this->getConnection();
            $this->closeConnection();
            return true;
        } catch (FtpClientException) {
            return false;
        }
    }

    /**
     * @return void
     */
    private function closeConnection(): void
    {
        ftp_close($this->connection);
        $this->connection = null;
    }

    /**
     * @return Connection
     * @throws FtpClientException
     */
    protected function getConnection(): Connection
    {
        if (!$this->connection) {
            $this->connection = ftp_connect($this->host, $this->port);
            if (!ftp_login($this->connection, $this->user, $this->password)) {
                throw new FtpClientException();
            }
            if (!ftp_pasv($this->connection, $this->passive)) {
                throw new FtpClientException();
            }
        }

        return $this->connection;
    }
}