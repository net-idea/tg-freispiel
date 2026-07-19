<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Builds navigation and footer items from content/_pages.php.
 */
readonly class NavigationService
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @return array<int, array{slug:string,label:string,url:string,order:int}>
     */
    public function getItems(): array
    {
        return $this->buildItems('nav');
    }

    /**
     * @return array<int, array{slug:string,label:string,url:string,order:int}>
     */
    public function getFooterItems(): array
    {
        return $this->buildItems('footer');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadPages(): array
    {
        $projectDir = $this->kernel->getProjectDir();
        $pagesFile = $projectDir . '/content/_pages.php';

        if (!is_file($pagesFile)) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $pages */
        $pages = require $pagesFile;

        return $pages;
    }

    /**
     * @return array<int, array{slug:string,label:string,url:string,order:int}>
     */
    private function buildItems(string $placement): array
    {
        $items = [];
        $pages = $this->loadPages();
        $labelKey = $placement . '_label';
        $orderKey = $placement . '_order';

        foreach ($pages as $slug => $meta) {
            $showInPlacement = (bool) ($meta[$placement] ?? false);

            if (!$showInPlacement) {
                continue;
            }

            $label = (string) ($meta[$labelKey] ?? $meta['nav_label'] ?? $meta['title'] ?? $slug);
            $order = (int) ($meta[$orderKey] ?? $meta['nav_order'] ?? 0);
            $url = (string) ($meta['path'] ?? '/' . ('start' === $slug ? '' : $slug));

            $items[] = [
                'slug'  => (string) $slug,
                'label' => $label,
                'url'   => $url,
                'order' => $order,
            ];
        }

        usort($items, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        return $items;
    }
}
