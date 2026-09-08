<?php

namespace QuietGuard\Monitor\Symfony\DependencyInjection;

use QuietGuard\Monitor\Support\ValueRedactor;
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
            // 0 = unlimited: the full trace ships by default, like the sibling SDKs.
            ->integerNode('trace_limit')->defaultValue(0)->end()
            ->arrayNode('environments')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->end()
            ->arrayNode('scrub')
            ->scalarPrototype()->end()
            ->defaultValue(['password', 'passphrase', 'token', 'secret', 'authorization', 'cookie', 'referer', 'referrer', 'api_key'])
            ->end()
            // Le masquage par FORME était actif par défaut (Config le veut
            // ainsi, article 25.2), déclaré nulle part dans cet arbre et
            // documenté nulle part pour Symfony. Un client voyait donc
            // [redacted:phone] dans un message d'exception, ne le trouvait dans
            // aucune documentation, et toute tentative de le configurer
            // échouait à la compilation du conteneur avec « Unrecognized
            // option ». Une valeur par défaut qu'on ne peut ni comprendre ni
            // changer est pire qu'une absence de valeur par défaut.
            //
            // Un tableau VIDE désactive, comme dans le SDK Laravel. La valeur
            // par défaut vient de ValueRedactor pour qu'il n'y ait pas deux
            // listes qui divergent.
            ->arrayNode('redact')
            ->scalarPrototype()->end()
            ->defaultValue(ValueRedactor::PATTERNS)
            ->end()
            ->arrayNode('redact_custom')
            ->useAttributeAsKey('label')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->end()
            ->arrayNode('logs')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->scalarNode('level')->defaultValue('warning')->end()
            // Ramené à l'exécution, pas refusé à la compilation. Une borne au
            // niveau de l'arbre empêcherait de démarrer une application qui
            // démarre aujourd'hui, ce qui n'est pas ce qu'un correctif de patch
            // doit faire, et casserait aussi 0, qui valait « vider à chaque
            // enregistrement ». Voir MonitorHandler, qui applique la borne du
            // serveur comme le SDK Laravel.
            ->integerNode('max_batch')->defaultValue(200)->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
