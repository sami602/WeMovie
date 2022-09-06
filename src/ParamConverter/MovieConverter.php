<?php

namespace App\ParamConverter;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class MovieConverter implements ParamConverterInterface
{
    public function __construct(private MovieRepository $movieRepository)
    {
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $movie = $this->movieRepository->getMovie($request->get('id'));
        $request->attributes->set('movie', $movie);

        return $movie !== null;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === Movie::class;
    }
}
