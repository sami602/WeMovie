<?php

namespace App\Tests\unit;

use App\Entity\Genre;
use App\Entity\Movie;
use App\Entity\Video;
use App\Repository\GenreRepository;
use App\Repository\MovieRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class TheMovieDbTest extends TestCase
{
    public function testGetMovie(): void
    {
        // Arrange
        $expectedResponseJson = $this->getMovieDetailResult();
        $mockResponse = new MockResponse($expectedResponseJson, [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);
        $movieRepository = $this->getRepository($mockResponse);

        // Act
        $responseMovie = $movieRepository->getMovie(1);

        // Assert
        self::assertSame('GET', $mockResponse->getRequestMethod());
        self::assertSame('https://api.themoviedb.org/3/movie/1', $mockResponse->getRequestUrl());
        self::assertEquals($this->getExpectedMovie(), $responseMovie);

        $movieRepository = $this->getRepository(new MockResponse('', [
            'http_code' => 404,
        ]));
        self::assertNull($movieRepository->getMovie(1));
    }

    public function testGetTopRatedMovie(): void
    {
        $mockResponse = new MockResponse($this->getMovieListResults(), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);
        $movieRepository = $this->getRepository($mockResponse);

        // Act
        $responseMovie = $movieRepository->getTopRatedMovie();

        // Assert
        self::assertSame('GET', $mockResponse->getRequestMethod());
        self::assertSame('https://api.themoviedb.org/3/movie/top_rated', $mockResponse->getRequestUrl());
        self::assertEquals($this->getExpectedMovie(), $responseMovie);

        $movieRepository = $this->getRepository(new MockResponse('', [
            'http_code' => 404,
        ]));
        self::assertNull($movieRepository->getTopRatedMovie());
    }

    public function testGetMoviesByGenre(): void
    {
        $mockResponse = new MockResponse($this->getMovieListResults(), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);
        $movieRepository = $this->getRepository($mockResponse);

        // Act
        $responseMovies = $movieRepository->getMoviesByGenre([16,28]);
        $firstResponseMovie = $responseMovies[0];

        // Assert
        self::assertSame('GET', $mockResponse->getRequestMethod());
        self::assertArrayHasKey('with_genres', $mockResponse->getRequestOptions()['query']);
        self::assertArrayHasKey('sort_by', $mockResponse->getRequestOptions()['query']);
        self::assertEquals($this->getExpectedMovie(), $firstResponseMovie);
        self::assertInstanceOf(Movie::class, $responseMovies[1]);
        self::assertInstanceOf(Movie::class, $responseMovies[2]);
        self::assertNotEquals($firstResponseMovie, $responseMovies[1]);
        self::assertNotEquals($responseMovies[1], $responseMovies[2]);

        $movieRepository = $this->getRepository(new MockResponse('', [
            'http_code' => 500,
        ]));
        self::assertEmpty($movieRepository->getMoviesByGenre([16,28]));
    }

    public function testSearch(): void
    {
        $mockResponse = new MockResponse($this->getMovieListResults(), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $movieRepository = $this->getRepository($mockResponse);

        // Act
        $responseMovies = $movieRepository->search('Les Évadés');
        $firstResponseMovie = $responseMovies[0];

        // Assert
        self::assertSame('GET', $mockResponse->getRequestMethod());
        self::assertArrayHasKey('query', $mockResponse->getRequestOptions()['query']);
        self::assertEquals($this->getExpectedMovie(), $firstResponseMovie);
        self::assertInstanceOf(Movie::class, $responseMovies[1]);
        self::assertInstanceOf(Movie::class, $responseMovies[2]);
        self::assertNotEquals($firstResponseMovie, $responseMovies[1]);
        self::assertNotEquals($responseMovies[1], $responseMovies[2]);
        self::assertEmpty($movieRepository->search(''));
    }

    public function testGetMovieYoutubeVideos(): void
    {
        $mockResponse = new MockResponse($this->getVideoListResults(), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);
        $movieRepository = $this->getRepository($mockResponse);
        $expectedVideo = new Video();
        $expectedVideo->setName('Les évadés (VF) - Bande Annonce');
        $expectedVideo->setSite('YouTube');
        $expectedVideo->setKey('dSL2Ec_0fUs');

        // Act
        $responseVideos = $movieRepository->getMovieYoutubeVideos(1);
        $firstResponseVideo = $responseVideos[0];

        // Assert
        self::assertSame('GET', $mockResponse->getRequestMethod());
        self::assertSame('https://api.themoviedb.org/3/movie/1/videos', $mockResponse->getRequestUrl());
        self::assertEquals($expectedVideo, $firstResponseVideo);
        self::assertEquals('YouTube', $responseVideos[1]->getSite());
        self::assertCount(2, $responseVideos); // We only expect 2 video objects as the third result has not youtube as a site
        self::assertNotEquals($firstResponseVideo, $responseVideos[1]);

        $mockResponse = new MockResponse('{"id": 276, "results": []}', [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);
        $movieRepository = $this->getRepository($mockResponse);
        self::assertEmpty($movieRepository->getMovieYoutubeVideos(276));
    }

    public function testGetGenres(): void
    {
        $mockResponse = new MockResponse($this->getGenreListResults(), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $genreRepository = $this->getRepository($mockResponse, 'genre');

        // Act
        $genres = $genreRepository->getGenres();
        $firstGenre = $genres[0];
        $expectedGenre = new Genre();
        $expectedGenre
            ->setId(28)
            ->setName('Action')
        ;

        // Assert
        self::assertSame('GET', $mockResponse->getRequestMethod());
        self::assertSame('https://api.themoviedb.org/3/genre/movie/list', $mockResponse->getRequestUrl());
        self::assertEquals($expectedGenre, $firstGenre);

        foreach ($genres as $genre) {
            self::assertInstanceOf(Genre::class, $genre);
        }

        self::assertNotEquals($firstGenre, $genres[1]);
        self::assertNotEquals($genres[1], $genres[2]);
    }

    /**
     * @return GenreRepository|MovieRepository
     */
    private function getRepository(MockResponse $mockResponse, string $type = 'movie')
    {
        if (!in_array($type, ['movie', 'genre'])) {
            throw new \LogicException('Only genre and movie repository are available');
        }

        $logger = new NullLogger();
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer(), new ArrayDenormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $themoviedbClient = new MockHttpClient($mockResponse, 'https://api.themoviedb.org/3/');
        $themoviedbClient->withOptions(['headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']]);

        return $type === 'movie' ? new MovieRepository($logger, $themoviedbClient, $serializer) : new GenreRepository($logger, $themoviedbClient, $serializer);
    }

    private function getExpectedMovie(): Movie
    {
        $expectedMovie = new Movie();
        $expectedMovie
            ->setId(1)
            ->setTitle('Les Évadés')
            ->setBackdropPath('/kXfqcdQKsToO0OUXHcrrNCHDBzO.jpg')
            ->setOverview('En 1947, Andy Dufresne, un jeune banquier, est condamné à la prison à vie pour le meurtre de sa femme et de son amant.')
            ->setPosterPath('/rL1YQLqUtHK3HdQyenHvuOCuWzO.jpg')
            ->setReleaseDate('1994-09-23')
            ->setVoteAverage(8.714)
            ->setVoteCount(22179)
        ;

        return $expectedMovie;
    }

    private function getGenreListResults(): string
    {
        return '
        {
            "genres": [
                {
                    "id": 28,
                    "name": "Action"
                },
                {
                    "id": 12,
                    "name": "Aventure"
                },
                {
                    "id": 16,
                    "name": "Animation"
                },
                {
                    "id": 35,
                    "name": "Comédie"
                },
                {
                    "id": 80,
                    "name": "Crime"
                },
                {
                    "id": 99,
                    "name": "Documentaire"
                },
                {
                    "id": 18,
                    "name": "Drame"
                },
                {
                    "id": 10751,
                    "name": "Familial"
                },
                {
                    "id": 14,
                    "name": "Fantastique"
                },
                {
                    "id": 36,
                    "name": "Histoire"
                },
                {
                    "id": 27,
                    "name": "Horreur"
                },
                {
                    "id": 10402,
                    "name": "Musique"
                },
                {
                    "id": 9648,
                    "name": "Mystère"
                },
                {
                    "id": 10749,
                    "name": "Romance"
                },
                {
                    "id": 878,
                    "name": "Science-Fiction"
                },
                {
                    "id": 10770,
                    "name": "Téléfilm"
                },
                {
                    "id": 53,
                    "name": "Thriller"
                },
                {
                    "id": 10752,
                    "name": "Guerre"
                },
                {
                    "id": 37,
                    "name": "Western"
                }
            ]
        }';
    }

    private function getMovieListResults(): string
    {
        return '
        {
            "page": 1,
            "results": [
                {
                    "adult": false,
                    "backdrop_path": "/kXfqcdQKsToO0OUXHcrrNCHDBzO.jpg",
                    "genre_ids": [
                        16,
                        28
                    ],
                    "id": 1,
                    "original_language": "en",
                    "original_title": "The Shawshank Redemption",
                    "overview": "En 1947, Andy Dufresne, un jeune banquier, est condamné à la prison à vie pour le meurtre de sa femme et de son amant.",
                    "popularity": 117.258,
                    "poster_path": "/rL1YQLqUtHK3HdQyenHvuOCuWzO.jpg",
                    "release_date": "1994-09-23",
                    "title": "Les Évadés",
                    "video": false,
                    "vote_average": 8.714,
                    "vote_count": 22179
                },
                {
                    "adult": false,
                    "backdrop_path": "/htuuuEwAvDVECMpb0ltLLyZyDDt.jpg",
                    "genre_ids": [
                        16,
                        28
                    ],
                    "id": 238,
                    "original_language": "en",
                    "original_title": "The Godfather",
                    "overview": "En 1947, Andy Dufresne, un jeune banquier, est condamné à la prison à vie pour le meurtre de sa femme et de son amant.",
                    "popularity": 105.701,
                    "poster_path": "/wnDNKCeBQzioXYQrXcSyrmRHVxf.jpg",
                    "release_date": "1972-03-14",
                    "title": "Le Parrain",
                    "video": false,
                    "vote_average": 8.7,
                    "vote_count": 16494
                },
                {
                    "adult": false,
                    "backdrop_path": "/90ez6ArvpO8bvpyIngBuwXOqJm5.jpg",
                    "genre_ids": [
                        16,
                        28,
                        10749
                    ],
                    "id": 19404,
                    "original_language": "hi",
                    "original_title": "दिलवाले दुल्हनिया ले जायेंगे",
                    "overview": "En 1947, Andy Dufresne, un jeune banquier, est condamné à la prison à vie pour le meurtre de sa femme et de son amant.",
                    "popularity": 50.197,
                    "poster_path": "/2CAL2433ZeIihfX1Hb2139CX0pW.jpg",
                    "release_date": "1995-10-19",
                    "title": "Dilwale Dulhania Le Jayenge",
                    "video": false,
                    "vote_average": 8.7,
                    "vote_count": 3820
                }
            ],
            "total_pages": 514,
            "total_results": 10275
        }';
    }

    private function getVideoListResults(): string
    {
        return '
        {
            "id": 1,
            "results": [
                {
                    "iso_639_1": "fr",
                    "iso_3166_1": "FR",
                    "name": "Les évadés (VF) - Bande Annonce",
                    "key": "dSL2Ec_0fUs",
                    "site": "YouTube",
                    "size": 1080,
                    "type": "Trailer",
                    "official": false,
                    "published_at": "2014-01-21T11:31:14.000Z",
                    "id": "5ca7b57d0e0a264c73f06e98"
                },
                {
                    "iso_639_1": "fr",
                    "iso_3166_1": "FR",
                    "name": "Les évadés ( bande annonce VOST )",
                    "key": "45Sss4oNd5k",
                    "published_at": "2012-01-15T16:38:28.000Z",
                    "site": "YouTube",
                    "size": 720,
                    "type": "Trailer",
                    "official": false,
                    "id": "533ec653c3a368544800024c"
                },
                {
                    "iso_639_1": "fr",
                    "iso_3166_1": "FR",
                    "name": "Vimeo video",
                    "key": "77777777",
                    "published_at": "2012-01-15T16:38:28.000Z",
                    "site": "Vimeo",
                    "size": 720,
                    "type": "Trailer",
                    "official": false,
                    "id": "123456789poiuytrre"
                }
            ]
        }';
    }

    private function getMovieDetailResult(): string
    {
        return '
        {
            "adult": false,
            "backdrop_path": "/kXfqcdQKsToO0OUXHcrrNCHDBzO.jpg",
            "belongs_to_collection": null,
            "budget": 25000000,
            "genres": [
                {
                    "id": 18,
                    "name": "Drame"
                },
                {
                    "id": 80,
                    "name": "Crime"
                }
            ],
            "homepage": "",
            "id": 1,
            "imdb_id": "tt0111161",
            "original_language": "en",
            "original_title": "The Shawshank Redemption",
            "overview": "En 1947, Andy Dufresne, un jeune banquier, est condamné à la prison à vie pour le meurtre de sa femme et de son amant.",
            "popularity": 117.258,
            "poster_path": "/rL1YQLqUtHK3HdQyenHvuOCuWzO.jpg",
            "production_companies": [
                {
                    "id": 97,
                    "logo_path": "/7znWcbDd4PcJzJUlJxYqAlPPykp.png",
                    "name": "Castle Rock Entertainment",
                    "origin_country": "US"
                }
            ],
            "production_countries": [
                {
                    "iso_3166_1": "US",
                    "name": "United States of America"
                }
            ],
            "release_date": "1994-09-23",
            "revenue": 28341469,
            "runtime": 140,
            "spoken_languages": [
                {
                    "english_name": "English",
                    "iso_639_1": "en",
                    "name": "English"
                }
            ],
            "status": "Released",
            "tagline": "Même à Shawshank on n enferme pas l espoir…",
            "title": "Les Évadés",
            "video": false,
            "vote_average": 8.714,
            "vote_count": 22179
        }';
    }
}