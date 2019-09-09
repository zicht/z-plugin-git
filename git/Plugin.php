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
                if (preg_match('/^commit (?P<hash>[a-f0-9]+)/im', $info, $m)) {
                    return $m['hash'];
                }
                throw new \RuntimeException("Could not find commit hash in:\n<comment>". $info . "</comment>");
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
            function(Container $container) {

                $version = $container->call($container->get('versionof'), $container->resolve('cwd'));

                if ($container->isDebug()) {
                    $container->output->writeln('<comment># current build version: ' . $version . '</comment>');
                }

                return $version;
            }
        );
        $container->decl(
            array('vcs', 'description'),
            function(Container $container) {

                $dir = $container->resolve('cwd');
                $output = $container->helperExec('cd ' . escapeshellarg($dir) . ' && git describe --always --match "*.*.*" --tags HEAD');
                if (preg_match('/^(?P<version>[^\s\+]+)/im', $output, $m)) {
                    $version = $m['version'];

                    if ($container->isDebug()) {
                        $container->output->writeln('<comment># current version description: ' . $version . '</comment>');
                    }

                    return $version;
                } else {
                    throw new \RuntimeException("Could not find descriptive version in:\n<comment>" . $output . "</comment>");
                }
            }
        );
    }
}
