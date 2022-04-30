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

namespace TBCD\FtpClient;

use TBCD\FtpClient\Exception\FtpClientException;

/**
 * @author Thomas Beauchataud
 * @since 27/04/2022
 */
class ScpClient implements FtpClientInterface
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
     * @var string|array
     */
    private string|array $credentials;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var bool
     */
    private bool $keepAlive;

    /**
     * @var mixed
     */
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
        if (!ssh2_scp_recv($this->getConnection(), $remoteFilePath, $localFilePath)) {
            throw new FtpClientException("Failed to download the remote file $remoteFilePath to $localFilePath");
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
        if (!ssh2_scp_send($this->getConnection(), $remoteFilePath, $localFilePath, $mode)) {
            throw new FtpClientException("Failed to upload the local file $localFilePath to $remoteFilePath");
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
        if (!ssh2_sftp_rename(ssh2_sftp($this->getConnection()), $oldFilePath, $newFilePath)) {
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
        $sftp = ssh2_sftp($this->getConnection());
        $output = filesize("ssh2.sftp://$sftp$filePath") > 0;
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
        if (!ssh2_sftp_mkdir(ssh2_sftp($this->getConnection()), $directoryPath)) {
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
        if (!ssh2_sftp_unlink(ssh2_sftp($this->getConnection()), $filePath)) {
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
    protected function closeConnection(): void
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
    protected function getConnection(): mixed
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