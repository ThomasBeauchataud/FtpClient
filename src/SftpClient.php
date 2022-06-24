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

use TBCD\FtpClient\Exception\FtpClientException;

class SftpClient implements FtpClientInterface
{

    private string $host;
    private string $user;
    private string|array $credentials;
    private int $port;
    private bool $keepAlive;
    private mixed $connection = null;

    /**
     * @param string $host
     * @param string $user
     * @param string|array $credentials
     * @param int $port
     * @param bool $keepAlive
     */
    public function __construct(string $host, string $user, string|array $credentials, int $port = 22, bool $keepAlive = true)
    {
        $this->host = $host;
        $this->user = $user;
        $this->credentials = $credentials;
        $this->port = $port;
        $this->keepAlive = $keepAlive;
    }


    /**
     * @inheritDoc
     */
    public function download(string $remoteFilePath, string $localFilePath, int $mode = FTP_ASCII): void
    {
        $sftp = $this->getConnection();
        if (!str_starts_with($remoteFilePath, '/')) {
            $remoteFilePath = "/$remoteFilePath";
        }
        $stream = fopen("ssh2.sftp://$sftp/$remoteFilePath", 'r');
        if (!$stream) {
            throw new FtpClientException("Could not open remote file $remoteFilePath");
        }
        $contents = stream_get_contents($stream);
        file_put_contents($localFilePath, $contents);
        fclose($stream);
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function upload(string $localFilePath, string $remoteFilePath, int $mode = FTP_ASCII): void
    {
        $sftp = $this->getConnection();
        if (!str_starts_with($remoteFilePath, '/')) {
            $remoteFilePath = "/$remoteFilePath";
        }
        $stream = fopen("ssh2.sftp://$sftp$remoteFilePath", 'w');
        if (!$stream)
            throw new FtpClientException("Could not open remote file $remoteFilePath");
        $data_to_send = file_get_contents($localFilePath);
        if ($data_to_send === false)
            throw new FtpClientException("Could not open local file $localFilePath");
        if (fwrite($stream, $data_to_send) === false)
            throw new FtpClientException("Could not send data from file $localFilePath");
        fclose($stream);
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function rename(string $oldFilePath, string $newFilePath): void
    {
        if (!ssh2_sftp_rename($this->getConnection(), $oldFilePath, $newFilePath)) {
            throw new FtpClientException("Failed to rename the remote file $oldFilePath to $newFilePath");
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
        $sftp = $this->getConnection();
        if (!str_starts_with($filePath, '/')) {
            $filePath = "/$filePath";
        }
        $output = file_exists("ssh2.sftp://$sftp$filePath");
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
        $sftp = $this->getConnection();
        $list = explode('/', $directoryPath);
        $path = '';
        foreach ($list as $dir) {
            $path .= "$dir/";
            if (empty($dir) || $this->exists($path)) {
                continue;
            }
            if (!ssh2_sftp_mkdir($sftp, $path)) {
                throw new FtpClientException("Unable to create the remote directory $path");
            }
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
        if (!ssh2_sftp_unlink($this->getConnection(), $filePath)) {
            throw new FtpClientException("Failed to delete the remote file $filePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function scan(string $directoryPath = '.', bool $excludeDefault = true): array
    {
        $directoryPath = "/." . (str_starts_with($directoryPath, '/') ? $directoryPath : "/$directoryPath");
        $sftp = $this->getConnection();
        $dir = "ssh2.sftp://$sftp$directoryPath";
        $list = [];
        $handle = opendir($dir);
        while (false !== ($file = readdir($handle))) {
            if (!str_starts_with("$file", ".")) {
                $list[] = $file;
            }
        }
        closedir($handle);
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
        if ($this->connection) {
            ssh2_disconnect($this->connection);
            $this->connection = null;
        }
    }

    /**
     * @return mixed
     * @throws FtpClientException
     */
    private function getConnection(): mixed
    {
        if (!$this->connection) {
            $connection = ssh2_connect($this->host, $this->port);
            if (!$connection) {
                throw new FtpClientException(sprintf("Failed to create a connexion to %s:%s", $this->host, $this->port));
            }
            if (is_string($this->credentials)) {
                if (!ssh2_auth_password($connection, $this->user, $this->credentials)) {
                    throw new FtpClientException(sprintf("Failed to login to %s@%s with password credentials", $this->user, $this->host));
                }
            } else {
                if (!ssh2_auth_pubkey_file($connection, $this->user, $this->credentials['publicKey'], $this->credentials['privateKey'], $this->credentials['passphrase'] ?? null)) {
                    throw new FtpClientException(sprintf("Failed to login to %s@%s with public key credentials", $this->user, $this->host));
                }
            }
            $this->connection = ssh2_sftp($connection);
            if (!$this->connection) {
                throw new FtpClientException("Failed to initialize a SFTP subsystem");
            }
        }

        return $this->connection;
    }
}