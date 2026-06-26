<?php

namespace LaBoiteACode\Monitor\Symfony;

use LaBoiteACode\Monitor\Symfony\DependencyInjection\MonitorExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MonitorBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new MonitorExtension;
    }
}
