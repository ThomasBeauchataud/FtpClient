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

namespace TBCD\FtpClient\CompilerPass;

use Exception;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TBCD\FtpClient\Client\FtpClient;
use TBCD\FtpClient\Client\SftpPasswordClient;
use TBCD\FtpClient\Client\SftpPublicKeyClient;
use TBCD\FtpClient\Factory\FtpClientFactoryInterface;
use TBCD\FtpClient\FtpProtocol;

class FtpClientCompilerPass implements CompilerPassInterface
{

    /**
     * @throws Exception
     *
     * @inheritDoc
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(FtpClientFactoryInterface::class)) {
            return;
        }

        /** @var FtpClientFactoryInterface $ftpClientFactory */
        $ftpClientFactory = $container->get(FtpClientFactoryInterface::class);
        $optionsResolver = $this->buildOptionsResolver($ftpClientFactory->getDefaultConfig());
        foreach ($ftpClientFactory->getClientsConfig() as $clientName => $clientConfig) {
            $serviceId = "tbcd.ftp_client.$clientName";
            $serviceAlias = "$clientName.ftp_client";
            $clientConfig = $optionsResolver->resolve($clientConfig);
            $class = $this->getClientClass($clientConfig);
            $definition = new Definition($class, [$clientConfig]);
            $container->setDefinition($serviceId, $definition);
            $container->setAlias($serviceAlias, $serviceId);
        }
    }

    /**
     * @param array $clientConfig
     * @return string
     * @throws ServiceNotFoundException
     */
    private function getClientClass(array $clientConfig): string
    {
        if ($clientConfig['protocol'] === FtpProtocol::FTP) {
            return FtpClient::class;
        } elseif ($clientConfig['protocol'] === FtpProtocol::SFTP && array_key_exists('password', $clientConfig)) {
            return SftpPasswordClient::class;
        } elseif ($clientConfig['protocol'] === FtpProtocol::SFTP && array_key_exists('publicKeyPath', $clientConfig)) {
            return SftpPublicKeyClient::class;
        } else {
            throw new ServiceNotFoundException("Unable to find a suitable ftp client with the given configuration");
        }
    }

    /**
     * @param array $defaultConfig
     * @return OptionsResolver
     * @throws UndefinedOptionsException
     */
    private function buildOptionsResolver(array $defaultConfig): OptionsResolver
    {
        $optionsResolver = (new OptionsResolver)
            ->setRequired('host')
            ->setAllowedTypes('host', 'string')
            ->setRequired('user')
            ->setAllowedTypes('user', 'string')
            ->setDefined('password')
            ->setAllowedTypes('password', 'string')
            ->setDefined('publicKeyPath')
            ->setAllowedTypes('publicKeyPath', 'string')
            ->setRequired('port')
            ->setAllowedTypes('port', 'integer')
            ->setRequired('passive')
            ->setAllowedTypes('passive', 'boolean')
            ->setRequired('protocol')
            ->setAllowedValues('protocol', FtpProtocol::cases())
            ->setNormalizer('password', function (Options $options, $value) {
                if ($options['protocol'] === FtpProtocol::FTP && !isset($options['password'])) {
                    throw new MissingOptionsException('The option "password" is required when using the protocol FTP');
                }
                if ($options['protocol'] === FtpProtocol::SFTP && !isset($options['password']) && !isset($options['publicKeyPath'])) {
                    throw new MissingOptionsException('The options "password" or "publicKeyPath" is required when using the protocol SFTP');
                }
                return $value;
            });

        foreach ($defaultConfig as $key => $value) {
            if ($optionsResolver->isRequired($key)) {
                $optionsResolver->setDefault($key, $value);
            } else {
                throw new UndefinedOptionsException("The option $key is undefined. Valid options are " . implode(', ', $optionsResolver->getDefinedOptions()));
            }
        }

        return $optionsResolver;
    }
}