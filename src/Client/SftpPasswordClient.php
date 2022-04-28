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

class SftpPasswordClient extends AbstractSftpClient
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
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->port = $config['port'];
        $this->keepAlive = $config['keepAlive'];
    }


    /**
     * @return SFTP
     * @throws FtpClientException
     */
    protected function getConnection(): SFTP
    {
        if (!$this->connection) {
            $this->connection = new SFTP($this->host, $this->port);
            if (!$this->connection->login($this->user, $this->password)) {
                throw new FtpClientException();
            }
        }

        return $this->connection;
    }
}