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

interface FtpClientInterface
{

    /**
     * @param string $remoteFilePath
     * @param string $localFilePath
     * @param int $mode
     * @return void
     * @throws FtpClientException
     */
    public function download(string $remoteFilePath, string $localFilePath, int $mode = FTP_ASCII): void;

    /**
     * @param string $localFilePath
     * @param string $remoteFilePath
     * @param int $mode
     * @return void
     * @throws FtpClientException
     */
    public function upload(string $localFilePath, string $remoteFilePath, int $mode = FTP_ASCII): void;

    /**
     * @param string $oldFilePath
     * @param string $newFilePath
     * @return void
     * @throws FtpClientException
     */
    public function rename(string $oldFilePath, string $newFilePath): void;

    /**
     * @param string $filePath
     * @return bool
     * @throws FtpClientException
     */
    public function exists(string $filePath): bool;

    /**
     * @param string $filePath
     * @return void
     * @throws FtpClientException
     */
    public function delete(string $filePath): void;

    /**
     * @param string $directoryPath
     * @return void
     * @throws FtpClientException
     */
    public function mkdir(string $directoryPath): void;

    /**
     * @param string $directoryPath
     * @return array
     * @throws FtpClientException
     */
    public function list(string $directoryPath): array;

    /**
     * @return bool
     */
    public function isValidConnexion(): bool;

}