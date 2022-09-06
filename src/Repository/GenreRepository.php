<?php

namespace App\Repository;

use App\Entity\Genre;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GenreRepository
{
    public function __construct(private LoggerInterface $logger, private HttpClientInterface $themoviedbClient, private SerializerInterface $serializer)
    {
    }

    /**
     * @return Genre[]
     */
    public function getGenres(): array
    {
        try {
            $response = $this->themoviedbClient->request(Request::METHOD_GET, 'genre/movie/list');
            $content = json_decode($response->getContent());

            return $this->serializer->deserialize(json_encode($content->genres), Genre::class . '[]', 'json');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }
}