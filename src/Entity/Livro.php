<?php

declare(strict_types=1);

namespace BibliotecaORM\Entity;

use InvalidArgumentException;
use LogicException;

class Livro
{
    private ?int $id = null;
    private string $titulo;
    private int $anoPublicacao;
    private string $isbn;

    /** @var array<int|string, Autor> */
    private array $autores = [];

    public function __construct(string $titulo, int $anoPublicacao, string $isbn, ?int $id = null)
    {
        $this->setTitulo($titulo);
        $this->setAnoPublicacao($anoPublicacao);
        $this->setIsbn($isbn);

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
            throw new LogicException('O ID do livro já foi definido e não pode ser alterado.');
        }

        if ($id <= 0) {
            throw new InvalidArgumentException('O ID do livro deve ser um número positivo.');
        }

        $this->id = $id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): void
    {
        $titulo = trim($titulo);
        if ($titulo === '') {
            throw new InvalidArgumentException('O título do livro não pode ser vazio.');
        }

        $this->titulo = $titulo;
    }

    public function getAnoPublicacao(): int
    {
        return $this->anoPublicacao;
    }

    public function setAnoPublicacao(int $anoPublicacao): void
    {
        $anoAtual = (int) date('Y');
        if ($anoPublicacao < 1450 || $anoPublicacao > $anoAtual + 1) {
            throw new InvalidArgumentException('Ano de publicação inválido.');
        }

        $this->anoPublicacao = $anoPublicacao;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): void
    {
        $isbn = trim($isbn);
        if ($isbn === '') {
            throw new InvalidArgumentException('O ISBN não pode ser vazio.');
        }

        $this->isbn = $isbn;
    }

    public function adicionarAutor(Autor $autor): void
    {
        $chave = $autor->getId() ?? spl_object_id($autor);

        if (!isset($this->autores[$chave])) {
            $this->autores[$chave] = $autor;
        }
    }

    /** @return Autor[] */
    public function getAutores(): array
    {
        return array_values($this->autores);
    }

    public function __toString(): string
    {
        $nomes = array_map(fn(Autor $a): string => $a->getNome(), $this->autores);
        $listaAutores = $nomes !== [] ? implode(', ', $nomes) : 'Sem autores';

        return sprintf(
            'Livro #%s: "%s" (%d) | ISBN: %s | Autores: %s',
            $this->id ?? 'novo',
            $this->titulo,
            $this->anoPublicacao,
            $this->isbn,
            $listaAutores
        );
    }
}