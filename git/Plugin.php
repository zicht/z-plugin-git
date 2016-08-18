<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Zicht\Tool\Plugin\Git;

use \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use \Zicht\Tool\Container\Container;
use \Zicht\Tool\Plugin as BasePlugin;

/**
 * Git plugin configuration
 */
class Plugin extends BasePlugin
{
    /**
     * Appends Git configuration options
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode
     * @return mixed|void
     */
    public function appendConfiguration(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('vcs')
                    ->children()
                        ->scalarNode('url')->end()
                        ->arrayNode('export')
                            ->children()
                                ->scalarNode('revfile')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @{inheritDoc}
     */
    public function setContainer(Container $container)
    {
        $container->method(
            array('vcs', 'versionid'),
            function($container, $info) {
                if (preg_match('/^commit ([a-z0-9]+)/', $info, $m)) {
                    return $m[1];
                }
                trigger_error("could not parse info");
            }
        );
        $container->decl(array('vcs', 'versions'), function($c) {
            return explode("\n", $c->helperExec(sprintf('git branch && git tag -l')));
        });
        $container->method(
            array('versionof'),
            function($container, $dir) {
                $info = $container->helperExec('cd ' . escapeshellarg($dir) . ' && git log $(git rev-parse --abbrev-ref HEAD) -1');

                if (!$info && is_file($revFile = ($dir . '/' . $container->resolve(array('vcs', 'export', 'revfile'))))) {
                    $info = file_get_contents($revFile);
                }

                if ($info) {
                    return $container->call($container->resolve(array('vcs', 'versionid')), $info);
                } else {
                    return null;
                }
            }
        );
        $container->fn(
            array('vcs', 'diff'),
            function($left, $right, $verbose = false) {
                return sprintf('git diff %s %s %s', $left, $right, ($verbose ? '' : '--name-only -G.'));
            }
        );
        $container->decl(
            array('vcs', 'current'),
            function($container) {
                return $container->helperExec('git rev-parse HEAD');
            }
        );
        $container->decl(
            array('vcs', 'current'),
            function($container) {
                return $container->call($container->get('versionof'), $container->resolve('cwd'));
            }
        );
    }
}
