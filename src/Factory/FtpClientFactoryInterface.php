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

namespace TBCD\FtpClient\Factory;

interface FtpClientFactoryInterface
{

    /**
     * @return array
     */
    public function getDefaultConfig(): array;

    /**
     * @return array
     */
    public function getClientsConfig(): array;

}