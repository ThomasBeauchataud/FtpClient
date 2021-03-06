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

class ScpClient implements FtpClientInterface
{

    private string $host;
    private string $user;
    private string|array $credentials;
    private int $port;
    private bool $keepAlive;
    private mixed $connection = null;

    public function __construct(string $host, string $user, string|array $credentials, int $port = 22, bool $keepAlive = true)
    {
        $this->host = $host;
        $this->user = $user;
        $this->credentials = $credentials;
        $this->port = $port;
        $this->keepAlive = $keepAlive;
    }


    public function download(string $remoteFilePath, string $localFilePath, int $mode = FTP_ASCII): void
    {
        if (!ssh2_scp_recv($this->getConnection(), $remoteFilePath, $localFilePath)) {
            throw new FtpClientException("Failed to download the remote file $remoteFilePath to $localFilePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function upload(string $localFilePath, string $remoteFilePath, int $mode = FTP_ASCII): void
    {
        if (!ssh2_scp_send($this->getConnection(), $remoteFilePath, $localFilePath, $mode)) {
            throw new FtpClientException("Failed to upload the local file $localFilePath to $remoteFilePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function rename(string $oldFilePath, string $newFilePath): void
    {
        if (!ssh2_sftp_rename(ssh2_sftp($this->getConnection()), $oldFilePath, $newFilePath)) {
            throw new FtpClientException("Failed to rename the remote file $oldFilePath to $newFilePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function exists(string $filePath): bool
    {
        $sftp = ssh2_sftp($this->getConnection());
        $output = filesize("ssh2.sftp://$sftp$filePath") > 0;
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
        return $output;
    }

    public function mkdir(string $directoryPath): void
    {
        if (!ssh2_sftp_mkdir(ssh2_sftp($this->getConnection()), $directoryPath)) {
            throw new FtpClientException();
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function delete(string $filePath): void
    {
        if (!ssh2_sftp_unlink(ssh2_sftp($this->getConnection()), $filePath)) {
            throw new FtpClientException("Failed to delete the remote file $filePath");
        }
        if (!$this->keepAlive) {
            $this->closeConnection();
        }
    }

    public function scan(string $directoryPath = '.', bool $excludeDefault = true): array
    {
        $directoryPath = "/." . (str_starts_with($directoryPath, '/') ? $directoryPath : "/$directoryPath");
        $sftp = ssh2_sftp($this->getConnection());
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
            ssh2_disconnect($this->connection);
            $this->connection = null;
        }
    }

    private function getConnection(): mixed
    {
        if (!$this->connection) {
            $this->connection = ssh2_connect($this->host, $this->port);
            if (!$this->connection) {
                throw new FtpClientException(sprintf("Failed to create a connexion to %s:%s", $this->host, $this->port));
            }
            if (is_string($this->credentials)) {
                if (!ssh2_auth_password($this->connection, $this->user, $this->credentials)) {
                    throw new FtpClientException(sprintf("Failed to login to %s@%s with password credentials", $this->user, $this->host));
                }
            } else {
                if (!ssh2_auth_pubkey_file($this->connection, $this->user, $this->credentials['publicKey'], $this->credentials['privateKey'], $this->credentials['passphrase'] ?? null)) {
                    throw new FtpClientException(sprintf("Failed to login to %s@%s with public key credentials", $this->user, $this->host));
                }
            }
        }

        return $this->connection;
    }
}