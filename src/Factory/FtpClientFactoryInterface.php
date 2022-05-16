<?php

/*
 * This file is part of the tbcd/ftp-client package.
 *
 * (c) Thomas Beauchataud <thomas.beauchataud@yahoo.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TBCD\FtpClient\Factory;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TBCD\FtpClient\FtpClientInterface;

interface FtpClientFactoryInterface
{

    /**
     * @param array $clientConfig
     * @return FtpClientInterface
     */
    public function createClient(array $clientConfig): FtpClientInterface;

    /**
     * @param array $defaultConfig
     * @return OptionsResolver
     */
    public static function buildOptionsResolver(array $defaultConfig): OptionsResolver;

}