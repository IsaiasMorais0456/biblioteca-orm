<?php

declare(strict_types=1);

namespace BibliotecaORM\Repository;

use BibliotecaORM\Entity\Autor;
use InvalidArgumentException;

class AutorRepository extends AbstractRepository
{
    protected function getTabela(): string
    {
        return 'autores';
    }

    protected function hidratar(array $linha): Autor
    {
        return new Autor(
            (string) $linha['nome'],
            (string) $linha['nacionalidade'],
            (int) $linha['id']
        );
    }

    public function salvar(object $entidade): Autor
    {
        if (!$entidade instanceof Autor) {
            throw new InvalidArgumentException('AutorRepository aceita apenas instâncias de Autor.');
        }

        if ($entidade->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO autores (nome, nacionalidade) VALUES (:nome, :nacionalidade)'
            );
            $stmt->execute([
                ':nome' => $entidade->getNome(),
                ':nacionalidade' => $entidade->getNacionalidade(),
            ]);

            $entidade->atribuirId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE autores SET nome = :nome, nacionalidade = :nacionalidade WHERE id = :id'
            );
            $stmt->execute([
                ':nome' => $entidade->getNome(),
                ':nacionalidade' => $entidade->getNacionalidade(),
                ':id' => $entidade->getId(),
            ]);
        }

        return $entidade;
    }
}