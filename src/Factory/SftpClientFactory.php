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
use TBCD\FtpClient\SftpClient;

/**
 * @author Thomas Beauchataud
 * @since 27/04/2022
 */
class SftpClientFactory implements FtpClientFactoryInterface
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
        $this->optionsResolver = $this->buildOptionsResolver($defaultConfig);
    }


    /**
     * @param array $clientConfig
     * @return SftpClient
     */
    public function createClient(array $clientConfig): SftpClient
    {
        $clientConfig = $this->optionsResolver->resolve($clientConfig);
        return new SftpClient($clientConfig['host'], $clientConfig['user'], $clientConfig['credentials'], $clientConfig['port'], $clientConfig['keepAlive']);
    }

    /**
     * @param array $defaultConfig
     * @return OptionsResolver
     */
    private function buildOptionsResolver(array $defaultConfig): OptionsResolver
    {
        $optionsResolver = (new OptionsResolver)
            ->setRequired('host')
            ->setAllowedTypes('host', 'string')
            ->setRequired('user')
            ->setAllowedTypes('user', 'string')
            ->setRequired('credentials')
            ->setAllowedTypes('credentials', ['string', 'array'])
            ->setRequired('port')
            ->setAllowedTypes('port', 'integer')
            ->setDefault('port', 22)
            ->setRequired('keepAlive')
            ->setAllowedTypes('keepAlive', 'boolean')
            ->setDefault('keepAlive', true);

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