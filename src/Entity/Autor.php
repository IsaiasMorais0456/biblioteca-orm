<?php

declare(strict_types=1);

namespace BibliotecaORM\Entity;

use InvalidArgumentException;
use LogicException;

class Autor
{
    private ?int $id = null;
    private string $nome;
    private string $nacionalidade;

    public function __construct(string $nome, string $nacionalidade, ?int $id = null)
    {
        $this->setNome($nome);
        $this->setNacionalidade($nacionalidade);

        if ($id !== null) {
            $this->atribuirId($id);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function atribuirId(int $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('O ID do autor já foi definido e não pode ser alterado.');
        }

        if ($id <= 0) {
            throw new InvalidArgumentException('O ID do autor deve ser um número positivo.');
        }

        $this->id = $id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $nome = trim($nome);
        if ($nome === '') {
            throw new InvalidArgumentException('O nome do autor não pode ser vazio.');
        }

        $this->nome = $nome;
    }

    public function getNacionalidade(): string
    {
        return $this->nacionalidade;
    }

    public function setNacionalidade(string $nacionalidade): void
    {
        $nacionalidade = trim($nacionalidade);
        if ($nacionalidade === '') {
            throw new InvalidArgumentException('A nacionalidade não pode ser vazia.');
        }

        $this->nacionalidade = $nacionalidade;
    }

    public function __toString(): string
    {
        return sprintf('Autor #%s: %s (%s)', $this->id ?? 'novo', $this->nome, $this->nacionalidade);
    }
}