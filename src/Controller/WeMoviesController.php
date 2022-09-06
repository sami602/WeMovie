<?php

namespace App\Controller;

use App\Form\GenresType;
use App\Entity\Genre;
use App\Entity\Movie;
use App\Repository\MovieRepository;
use App\Service\ImageConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class WeMoviesController extends AbstractController
{
    public function __construct (private MovieRepository $movieRepository, private ImageConfigurationService $imageConfigurationService)
    {
    }

    /**
     * @Route(path="/", name="home")
     */
    public function index(Request $request): Response
    {
        $topRatedMovie = $this->movieRepository->getTopRatedMovie();
        $genresForm = $this->createForm(GenresType::class);
        $genresForm->handleRequest($request);
        $genres = [];

        if ($genresForm->isSubmitted() && $genresForm->isValid()) {
            $genres = array_map(function (Genre $genre) {
                return $genre->getId();
            }, $genresForm->getData()['genres']);
        }

        $movies = $this->movieRepository->getMoviesByGenre($genres);

        return $this->render('home/index.html.twig', [
            'top_rated_movie' => $topRatedMovie,
            'videos' => $topRatedMovie ? $this->movieRepository->getMovieYoutubeVideos($topRatedMovie->getId()) : [],
            'genresForm' => $genresForm->createView(),
            'image_base_url' => $this->imageConfigurationService->getImageBaseUrl(),
            'movies' => $movies,
        ]);
    }

    /**
     * @Route(path="/movies/{id}", name="movie")
     * @ParamConverter(name="id", class="App\Entity\Movie", converter="MovieConverter")
     */
    public function movie(Movie $movie): Response
    {
        $videos = $this->movieRepository->getMovieYoutubeVideos($movie->getId());

        return $this->render('movie/modal.html.twig', [
            'movie' => $movie,
            'videos' => $videos,
            'image_base_url' => $this->imageConfigurationService->getImageBaseUrl(),
        ]);
    }

    /**
     * @Route(path="/search", name="search")
     */
    public function search(Request $request)
    {
        $query = $request->query->get('q');
        $movies = [];

        if (!empty($query)) {
            $movies = $this->movieRepository->search($query);
        }

        return $this->render('home/search_list.html.twig', [
            'movies' => $movies,
        ]);
    }
}