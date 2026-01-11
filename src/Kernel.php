<?php
declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $confDir = $this->getProjectDir() . '/config';

        // Load service and package configs common to all environments
        $container->import($confDir . '/packages/*.yaml');

        // Load environment-specific package config if the directory exists
        $envConfigDir = $confDir . '/packages/' . $this->environment;

        if (is_dir($envConfigDir)) {
            // For dev/test environments, only import if dev bundles are available
            // This prevents errors when dev dependencies are not installed
            if (in_array($this->environment, ['dev', 'test'], true)) {
                // Check if at least one dev bundle class exists before importing dev configs
                if (class_exists(\Symfony\Bundle\DebugBundle\DebugBundle::class)
                    || class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)
                    || class_exists(\Symfony\Bundle\MakerBundle\MakerBundle::class)) {
                    $container->import($envConfigDir . '/*.yaml');
                }
                // Otherwise skip importing dev configs to avoid "extension not registered" errors
            } else {
                // For non-dev environments (prod, staging, etc.), import normally
                $container->import($envConfigDir . '/*.yaml');
            }
        }

        // Load service definitions
        $container->import($confDir . '/services.yaml');
        $servicesEnv = $confDir . '/services_' . $this->environment . '.yaml';
        if (is_file($servicesEnv)) {
            $container->import($servicesEnv);
        }
    }

    protected function configureRoutes(\Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        // Load common routes
        $routes->import($confDir . '/routes/*.yaml');

        // Load environment-specific routes if the directory exists
        $envRoutesDir = $confDir . '/routes/' . $this->environment;

        if (is_dir($envRoutesDir)) {
            // For dev/test environments, only import if dev bundles are available
            // This prevents errors trying to load WebProfilerBundle routes without the bundle
            if (in_array($this->environment, ['dev', 'test'], true)) {
                // Check if at least one dev bundle class exists before importing dev routes
                if (class_exists(\Symfony\Bundle\DebugBundle\DebugBundle::class)
                    || class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)
                    || class_exists(\Symfony\Bundle\MakerBundle\MakerBundle::class)) {
                    $routes->import($envRoutesDir . '/*.yaml');
                }
                // Otherwise skip importing dev routes to avoid "bundle does not exist" errors
            } else {
                // For non-dev environments, import normally
                $routes->import($envRoutesDir . '/*.yaml');
            }
        }

        // Load routes.yaml last (if it exists) for catch-all routes
        if (is_file($confDir . '/routes.yaml')) {
            $routes->import($confDir . '/routes.yaml');
        }
    }
}
