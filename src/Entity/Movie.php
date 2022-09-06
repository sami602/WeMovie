<?php

namespace App\Entity;

class Movie
{
    /**
     * @var int The IMDB ID
     */
    private int $id;

    /**
     * @var string The title of the movie
     */
    private string  $title;

    /**
     * @var string The description of the movie
     */
    private string $overview;

    /**
     * @var string The link to the poster image
     */
    private ?string $posterPath;

    /**
     * @var string The link to the poster image
     */
    private ?string $backdropPath;

    /**
     * @var \DateTime The date the movie was released
     */
    private \DateTime $releaseDate;

    /**
     * @var float The rating value of the movie
     */
    private float $voteAverage;

    /**
     * @var int The number of votes
     */
    private int $voteCount;

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getOverview(): string
    {
        return $this->overview;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function getBackdropPath(): ?string
    {
        return $this->backdropPath;
    }

    public function getVoteAverage(): float
    {
        return $this->voteAverage;
    }

    public function getVoteCount(): int
    {
        return $this->voteCount;
    }

    public function setId(int $id): Movie
    {
        $this->id = $id;

        return $this;
    }

    public function setTitle(string $title): Movie
    {
        $this->title = $title;

        return $this;
    }

    public function setOverview(?string $overview): Movie
    {
        $this->overview = $overview;

        return $this;
    }

    public function setBackdropPath(?string $backdropPath): Movie
    {
        $this->backdropPath = $backdropPath;

        return $this;
    }

    public function setPosterPath(?string $posterPath): Movie
    {
        $this->posterPath = $posterPath;

        return $this;
    }

    public function setVoteAverage(float $voteAverage): Movie
    {
        $this->voteAverage = $voteAverage;

        return $this;
    }

    public function setVoteCount(int $voteCount): Movie
    {
        $this->voteCount = $voteCount;

        return $this;
    }

    public function getReleaseDate(): \DateTime
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(string $releaseDate): Movie
    {
        $this->releaseDate = new \DateTime($releaseDate);

        return $this;
    }
}