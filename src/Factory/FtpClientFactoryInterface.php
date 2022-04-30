<?php

/*
 * The file is part of the WoWUltimate project 
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Author Thomas Beauchataud
 * From 30/04/2022
 */

namespace TBCD\FtpClient\Factory;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TBCD\FtpClient\FtpClientInterface;

/**
 * @author Thomas Beauchataud
 * @since 27/04/2022
 */
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