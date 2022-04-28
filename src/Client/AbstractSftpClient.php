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
use phpseclib3\Net\SFTP;

abstract class AbstractSftpClient implements FtpClientInterface
{

    /**
     * @var bool
     */
    protected bool $keepAlive;

    /**
     * @var SFTP|null
     */
    protected ?SFTP $connection = null;

    /**
     * @inheritDoc
     */
    public function download(string $remoteFilePath, string $localFilePath, int $mode = FTP_ASCII): void
    {
        if (!$this->getConnection()->get($remoteFilePath, $localFilePath)) {
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
        if (!$this->getConnection()->put($remoteFilePath, $localFilePath)) {
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
        if (!$this->getConnection()->rename($oldFilePath, $newFilePath)) {
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
        $output = $this->getConnection()->file_exists($filePath);
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
        if (!$this->getConnection()->mkdir($directoryPath, -1, true)) {
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
        if (!$this->getConnection()->delete($filePath)) {
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
        $list = $this->getConnection()->nlist($directoryPath);
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
        $this->connection->disconnect();
        $this->connection = null;
    }

    /**
     * @return SFTP
     * @throws FtpClientException
     */
    protected abstract function getConnection(): SFTP;
}