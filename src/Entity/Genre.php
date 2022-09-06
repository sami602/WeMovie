<?php

namespace App\Entity;

class Genre
{
    private int $id;
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setId(int $id): Genre
    {
        $this->id = $id;

        return $this;
    }

    public function setName(string $name): Genre
    {
        $this->name = $name;

        return $this;
    }
}