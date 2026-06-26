<?php

namespace LaBoiteACode\Monitor\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('monitor');

        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->scalarNode('url')->defaultNull()->end()
            ->scalarNode('key')->defaultNull()->end()
            ->integerNode('timeout')->defaultValue(3)->end()
            ->scalarNode('release')->defaultNull()->end()
            ->integerNode('trace_limit')->defaultValue(50)->end()
            ->arrayNode('environments')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->end()
            ->arrayNode('scrub')
            ->scalarPrototype()->end()
            ->defaultValue(['password', 'token', 'secret', 'authorization', 'cookie', 'api_key'])
            ->end()
            ->arrayNode('logs')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->scalarNode('level')->defaultValue('warning')->end()
            ->integerNode('max_batch')->defaultValue(200)->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
