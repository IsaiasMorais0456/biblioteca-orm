<?php

declare(strict_types=1);

namespace BibliotecaORM\Repository;

use BibliotecaORM\Entity\Autor;
use BibliotecaORM\Entity\Livro;
use InvalidArgumentException;

class LivroRepository extends AbstractRepository
{
    protected function getTabela(): string
    {
        return 'livros';
    }

    protected function hidratar(array $linha): Livro
    {
        return new Livro(
            (string) $linha['titulo'],
            (int) $linha['ano_publicacao'],
            (string) $linha['isbn'],
            (int) $linha['id']
        );
    }

    public function buscar(int $id): ?Livro
    {
        /** @var ?Livro $livro */
        $livro = parent::buscar($id);

        if ($livro !== null) {
            $mapaAutores = [];
            $this->carregarAutores($livro, $mapaAutores);
        }

        return $livro;
    }

    /** @return Livro[] */
    public function listar(): array
    {
        /** @var Livro[] $livros */
        $livros = parent::listar();

        // Compartilha instâncias de Autor dentro do mesmo carregamento (evita duplicação)
        $mapaAutores = [];
        foreach ($livros as $livro) {
            $this->carregarAutores($livro, $mapaAutores);
        }

        return $livros;
    }

    public function salvar(object $entidade): Livro
    {
        if (!$entidade instanceof Livro) {
            throw new InvalidArgumentException('LivroRepository aceita apenas instâncias de Livro.');
        }

        if ($entidade->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO livros (titulo, ano_publicacao, isbn) VALUES (:titulo, :ano, :isbn)'
            );
            $stmt->execute([
                ':titulo' => $entidade->getTitulo(),
                ':ano' => $entidade->getAnoPublicacao(),
                ':isbn' => $entidade->getIsbn(),
            ]);

            $entidade->atribuirId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE livros SET titulo = :titulo, ano_publicacao = :ano, isbn = :isbn WHERE id = :id'
            );
            $stmt->execute([
                ':titulo' => $entidade->getTitulo(),
                ':ano' => $entidade->getAnoPublicacao(),
                ':isbn' => $entidade->getIsbn(),
                ':id' => $entidade->getId(),
            ]);
        }

        return $entidade;
    }

    public function vincularAutor(int $livroId, int $autorId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO livros_autores (livro_id, autor_id) VALUES (:livro_id, :autor_id)'
        );
        $stmt->execute([
            ':livro_id' => $livroId,
            ':autor_id' => $autorId,
        ]);
    }

    /** @param int[] $autorIds */
    public function salvarComAutores(Livro $livro, array $autorIds): Livro
    {
        return $this->executarEmTransacao(function () use ($livro, $autorIds): Livro {
            $this->salvar($livro);

            foreach (array_unique($autorIds) as $autorId) {
                $this->vincularAutor((int) $livro->getId(), (int) $autorId);
            }

            $mapaAutores = [];
            $this->carregarAutores($livro, $mapaAutores);

            return $livro;
        });
    }

    public function desvincularAutores(int $livroId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM livros_autores WHERE livro_id = :livro_id');
        $stmt->execute([':livro_id' => $livroId]);
    }

    /** @param array<int, Autor> $mapaAutores */
    private function carregarAutores(Livro $livro, array &$mapaAutores): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.nome, a.nacionalidade
             FROM autores a
             INNER JOIN livros_autores la ON la.autor_id = a.id
             WHERE la.livro_id = :livro_id
             ORDER BY a.id ASC'
        );
        $stmt->execute([':livro_id' => $livro->getId()]);

        foreach ($stmt->fetchAll() as $linha) {
            $autorId = (int) $linha['id'];

            if (!isset($mapaAutores[$autorId])) {
                $mapaAutores[$autorId] = new Autor(
                    (string) $linha['nome'],
                    (string) $linha['nacionalidade'],
                    $autorId
                );
            }

            $livro->adicionarAutor($mapaAutores[$autorId]);
        }
    }
}