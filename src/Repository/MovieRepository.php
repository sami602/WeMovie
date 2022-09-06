<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MovieRepository
{
    public function __construct(private LoggerInterface $logger, private HttpClientInterface $themoviedbClient, private SerializerInterface $serializer)
    {
    }

    public function getMovie(int $id): ?Movie
    {
        try {
            $response = $this->themoviedbClient->request(Request::METHOD_GET, 'movie/' . $id);

            return $this->deserializeMovie($response->getContent());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    public function getTopRatedMovie(): ?Movie
    {
        try {
            $response = $this->themoviedbClient->request(Request::METHOD_GET, 'movie/top_rated');
            $topRatedMovieData = json_decode($response->getContent())->results[0];

            return $this->deserializeMovie(json_encode($topRatedMovieData));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    /**
     * @param int[] $genreIds
     * @return Movie[]
     */
    public function getMoviesByGenre(array $genreIds = []): array
    {
        try {
            $queryParameters = [
                'sort_by' => 'vote_count.desc',
            ];

            if (!empty($genreIds)) {
                $queryParameters['with_genres'] = implode(',', $genreIds);
            }

            $response = $this->themoviedbClient->request(Request::METHOD_GET, 'discover/movie', [
                'query' => $queryParameters,
            ]);
            $content = json_decode($response->getContent(), true);

            return $this->serializer->deserialize(json_encode($content['results']), Movie::class . '[]', 'json');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }

    /**
     * @return Video[]
     */
    public function getMovieYoutubeVideos(int $id): array
    {
        try {
            $response = $this->themoviedbClient->request(Request::METHOD_GET, 'movie/' . $id . '/videos');
            $content = json_decode($response->getContent(), true);
            $videos = $this->serializer->deserialize(json_encode($content['results']), Video::class . '[]', 'json');

            return array_filter($videos, function (Video $video) {
                return strtolower($video->getSite()) === 'youtube';
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }

    /**
     * @param string $query
     * @return Movie[]
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function search(string $query): array
    {
        if ($query === '') {
            return [];
        }

        try {
            $response = $this->themoviedbClient->request(Request::METHOD_GET, 'search/movie', [
                'query' => [
                    'query' => $query,
                ],
            ]);

            return $this->deserializeMovies($response);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }
    }

    private function deserializeMovie(string $json): ?Movie
    {
        return $this->serializer->deserialize($json, Movie::class, 'json');
    }

    /**
     * @return Movie[]
     */
    private function deserializeMovies(ResponseInterface $response): array
    {
        $content = json_decode($response->getContent(), true);

        return $this->serializer->deserialize(json_encode($content['results']), Movie::class . '[]', 'json');
    }
}