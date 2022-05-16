<?php

/*
 * This file is part of the tbcd/ftp-client package.
 *
 * (c) Thomas Beauchataud <thomas.beauchataud@yahoo.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TBCD\FtpClient;

use FTP\Connection;
use TBCD\FtpClient\Exception\FtpClientException;

class FtpsClient implements FtpClientInterface
{

    private string $host;
    private string $user;
    private string $credentials;
    private int $port;
    private bool $passive;
    private bool $keepAlive;
    private ?Connection $connection = null;

    public function __construct(string $host, string $user, string $credentials, int $port = 21, bool $passive = true, bool $keepAlive = true)
    {
        $this->host = $host;
        $this->user = $user;
        $this->credentials = $credentials;
        $this->port = $port;
        $this->passive = $passive;
        $this->keepAlive = $keepAlive;
    }


    public function download(string $remoteFilePath, string $localFilePath, int $mode = FTP_ASCII): void
    {
        if (!ftp_get($this->getConnection(), $localFilePath, $remoteFilePath, $mode)) {
            throw new FtpClientException("Failed to download the remote file $remoteFilePath to $localFilePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function upload(string $localFilePath, string $remoteFilePath, int $mode = FTP_ASCII): void
    {
        if (!ftp_put($this->getConnection(), $remoteFilePath, $localFilePath, $mode)) {
            throw new FtpClientException("Failed to upload the local file $localFilePath to $remoteFilePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function rename(string $oldFilePath, string $newFilePath): void
    {
        if (!ftp_rename($this->getConnection(), $oldFilePath, $newFilePath)) {
            throw new FtpClientException("Failed to rename the remote file $oldFilePath to $newFilePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function exists(string $filePath): bool
    {
        $output = ftp_size($this->getConnection(), $filePath) > 0;
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
        return $output;
    }

    public function mkdir(string $directoryPath): void
    {
        if (!ftp_mkdir($this->getConnection(), $directoryPath)) {
            throw new FtpClientException("Failed to create the remote directory $directoryPath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function delete(string $filePath): void
    {
        if (!ftp_delete($this->getConnection(), $filePath)) {
            throw new FtpClientException("Failed to delete the remote file $filePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function scan(string $directoryPath = '.', bool $excludeDefault = true): array
    {
        $list = ftp_nlist($this->getConnection(), $directoryPath);
        if (!$list) {
            throw new FtpClientException("Failed to scan the remote directory $directoryPath");
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

    private function closeConnection(): void
    {
        if ($this->connection) {
            ftp_close($this->connection);
            $this->connection = null;
        }
    }

    private function getConnection(): Connection
    {
        if (!$this->connection) {
            $this->connection = ftp_ssl_connect($this->host, $this->port);
            if (!$this->connection) {
                throw new FtpClientException(sprintf("Failed to create a connexion to %s:%s", $this->host, $this->port));
            }
            if (!ftp_login($this->connection, $this->user, $this->credentials)) {
                throw new FtpClientException(sprintf("Failed to login to %s@%s", $this->user, $this->host));
            }
            if ($this->passive) {
                if (!ftp_pasv($this->connection, $this->passive)) {
                    throw new FtpClientException("Failed to turn the connexion to passive mode");
                }
            }
        }

        return $this->connection;
    }
}