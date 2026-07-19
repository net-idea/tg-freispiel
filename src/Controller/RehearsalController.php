<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\NavigationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RehearsalController extends AbstractBaseController
{
    private const string PHOTO_DIR = 'media/proben/photos';
    private const string VIDEO_DIR = 'media/proben/videos';

    public function __construct(
        private readonly NavigationService $navigation,
    ) {
    }

    #[Route(
        path: '/proben',
        name: 'app_rehearsals',
        methods: ['GET']
    )]
    public function index(): Response
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $publicDir = $projectDir . '/public';

        return $this->render(
            'pages/proben.html.twig',
            [
                'slug'        => 'proben',
                'navItems'    => $this->navigation->getItems(),
                'footerItems' => $this->navigation->getFooterItems(),
                'pageMeta'    => $this->loadPageMetadata('proben'),
                'photos'      => $this->collectPhotoFiles(
                    $publicDir . '/' . self::PHOTO_DIR,
                    self::PHOTO_DIR,
                    ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif']
                ),
                'videos' => $this->collectVideoFiles(
                    $publicDir . '/' . self::VIDEO_DIR,
                    self::VIDEO_DIR,
                    ['mp4', 'webm', 'mov']
                ),
                'photosDir' => '/' . self::PHOTO_DIR,
                'videosDir' => '/' . self::VIDEO_DIR,
            ]
        );
    }

    /**
     * @param list<string> $extensions
     *
     * @return list<array{filename: string, name: string, url: string, thumbUrl: string}>
     */
    private function collectPhotoFiles(string $absoluteDir, string $publicPrefix, array $extensions): array
    {
        if (!is_dir($absoluteDir)) {
            return [];
        }

        /** @var array<string, array{filename: string, name: string, url: string, thumbUrl: string}> $originals */
        $originals = [];

        /** @var array<string, string> $thumbs */
        $thumbs = [];

        $dirHandle = opendir($absoluteDir);

        if (false === $dirHandle) {
            return [];
        }

        while (false !== ($entry = readdir($dirHandle))) {
            if ('.' === $entry || '..' === $entry || str_starts_with($entry, '.')) {
                continue;
            }

            $absolutePath = $absoluteDir . '/' . $entry;
            if (!is_file($absolutePath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($extension, $extensions, true)) {
                continue;
            }

            $stem = (string) pathinfo($entry, PATHINFO_FILENAME);
            $url = '/' . trim($publicPrefix, '/') . '/' . rawurlencode($entry);

            if (str_ends_with($stem, '.thumb')) {
                $key = substr($stem, 0, -strlen('.thumb'));
                if ('' !== $key) {
                    $thumbs[$key] = $url;
                }

                continue;
            }

            $originals[$stem] = [
                'filename' => $entry,
                'name'     => $stem,
                'url'      => $url,
                'thumbUrl' => $url,
            ];
        }

        closedir($dirHandle);

        foreach ($originals as $key => $item) {
            if (isset($thumbs[$key])) {
                $originals[$key]['thumbUrl'] = $thumbs[$key];
            }
        }

        $items = array_values($originals);
        usort(
            $items,
            static fn (array $left, array $right): int => strnatcasecmp($left['filename'], $right['filename'])
        );

        return $items;
    }

    /**
     * @param list<string> $extensions
     *
     * @return list<array{filename: string, name: string, url: string, thumbUrl: string}>
     */
    private function collectVideoFiles(string $absoluteDir, string $publicPrefix, array $extensions): array
    {
        if (!is_dir($absoluteDir)) {
            return [];
        }

        /** @var array<string, array{filename: string, name: string, url: string, thumbUrl: string}> $videos */
        $videos = [];
        /** @var array<string, string> $thumbs */
        $thumbs = [];
        $dirHandle = opendir($absoluteDir);
        $videoExtensions = array_map('strtolower', $extensions);
        $thumbnailExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

        if (false === $dirHandle) {
            return [];
        }

        while (false !== ($entry = readdir($dirHandle))) {
            if ('.' === $entry || '..' === $entry || str_starts_with($entry, '.')) {
                continue;
            }

            $absolutePath = $absoluteDir . '/' . $entry;
            if (!is_file($absolutePath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
            $stem = (string) pathinfo($entry, PATHINFO_FILENAME);
            $url = '/' . trim($publicPrefix, '/') . '/' . rawurlencode($entry);

            if (in_array($extension, $thumbnailExtensions, true)) {
                if (str_ends_with($stem, '.thumb')) {
                    $key = substr($stem, 0, -strlen('.thumb'));
                    if ('' !== $key) {
                        $thumbs[$this->normalizeVideoStem($key, $videoExtensions)] = $url;
                    }
                }

                continue;
            }

            if (!in_array($extension, $videoExtensions, true)) {
                continue;
            }

            $normalizedStem = $this->normalizeVideoStem($stem, $videoExtensions);
            $videos[$normalizedStem] = [
                'filename' => $entry,
                'name'     => $normalizedStem,
                'url'      => $url,
                'thumbUrl' => '/images/stage-background-mystical.webp',
            ];
        }

        closedir($dirHandle);

        foreach ($videos as $key => $item) {
            if (isset($thumbs[$key])) {
                $videos[$key]['thumbUrl'] = $thumbs[$key];
            }
        }

        $items = array_values($videos);
        usort(
            $items,
            static fn (array $left, array $right): int => strnatcasecmp($left['filename'], $right['filename'])
        );

        return $items;
    }

    /**
     * Normalisiert Thumb-Namen wie "szene-1.mp4.thumb.webp" auf den Videostamm "szene-1".
     *
     * @param list<string> $videoExtensions
     */
    private function normalizeVideoStem(string $stem, array $videoExtensions): string
    {
        foreach ($videoExtensions as $videoExtension) {
            $suffix = '.' . $videoExtension;
            if (str_ends_with($stem, $suffix)) {
                return substr($stem, 0, -strlen($suffix));
            }
        }

        return $stem;
    }
}
