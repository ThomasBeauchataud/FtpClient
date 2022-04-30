<?php

/*
 * The file is part of the WoWUltimate project 
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Author Thomas Beauchataud
 * From 29/04/2022
 */

namespace TBCD\FtpClient\Factory;

use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TBCD\FtpClient\FtpClient;

/**
 * @author Thomas Beauchataud
 * @since 27/04/2022
 */
class FtpClientFactory implements FtpClientFactoryInterface
{

    /**
     * @var OptionsResolver
     */
    private OptionsResolver $optionsResolver;

    /**
     * @param array $defaultConfig
     */
    public function __construct(array $defaultConfig = [])
    {
        $this->optionsResolver = self::buildOptionsResolver($defaultConfig);
    }


    /**
     * @param array $clientConfig
     * @return FtpClient
     */
    public function createClient(array $clientConfig): FtpClient
    {
        $clientConfig = $this->optionsResolver->resolve($clientConfig);
        return new FtpClient($clientConfig['host'], $clientConfig['user'], $clientConfig['password'], $clientConfig['port'], $clientConfig['passive'], $clientConfig['keepAlive']);
    }

    /**
     * @param array $defaultConfig
     * @return OptionsResolver
     */
    public static function buildOptionsResolver(array $defaultConfig): OptionsResolver
    {
        $optionsResolver = (new OptionsResolver)
            ->setRequired('host')
            ->setAllowedTypes('host', 'string')
            ->setRequired('user')
            ->setAllowedTypes('user', 'string')
            ->setRequired('password')
            ->setAllowedTypes('password', 'string')
            ->setRequired('port')
            ->setAllowedTypes('port', 'integer')
            ->setRequired('port')
            ->setDefault('port', 21)
            ->setAllowedTypes('passive', 'boolean')
            ->setDefault('passive', true)
            ->setRequired('keepAlive')
            ->setAllowedTypes('keepAlive', 'boolean')
            ->setDefault('passive', true);

        foreach ($defaultConfig as $key => $value) {
            if ($optionsResolver->isDefined($key)) {
                $optionsResolver->setDefault($key, $value);
            } else {
                throw new UndefinedOptionsException("The option $key is undefined. Valid options are " . implode(', ', $optionsResolver->getDefinedOptions()));
            }
        }

        return $optionsResolver;
    }
}