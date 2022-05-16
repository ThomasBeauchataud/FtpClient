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

use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TBCD\FtpClient\SftpClient;

class SftpClientFactory implements FtpClientFactoryInterface
{

    private OptionsResolver $optionsResolver;

    public function __construct(array $defaultConfig = [])
    {
        $this->optionsResolver = self::buildOptionsResolver($defaultConfig);
    }


    public function createClient(array $clientConfig): SftpClient
    {
        $clientConfig = $this->optionsResolver->resolve($clientConfig);
        return new SftpClient($clientConfig['host'], $clientConfig['user'], $clientConfig['credentials'], $clientConfig['port'], $clientConfig['keepAlive']);
    }

    public static function buildOptionsResolver(array $defaultConfig): OptionsResolver
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