<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageConfigurationService
{
    public function __construct(private HttpClientInterface $themoviedbClient, private CacheItemPoolInterface $themoviedbconfigCache)
    {
    }

    public function getImageBaseUrl(): string
    {
        return $this->themoviedbconfigCache->get('image_base_url', function (ItemInterface $item) {
            $configuration = json_decode($this->themoviedbClient->request(Request::METHOD_GET, 'configuration')->getContent(), true);
            $baseUrl = $configuration['images']['secure_base_url'];
            $size = in_array('original', $configuration['images']['poster_sizes']) ? 'original' : $configuration['images']['poster_sizes'][0];
            // This cache is valid for 3 days as this values should not change normally
            $item->expiresAfter(259200);

            return $baseUrl . '/' . $size;
        });
    }
}