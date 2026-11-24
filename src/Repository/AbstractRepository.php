<?php

declare(strict_types=1);

namespace BibliotecaORM\Repository;

use BibliotecaORM\Contract\RepositoryInterface;
use InvalidArgumentException;
use PDO;
use Throwable;

abstract class AbstractRepository implements RepositoryInterface
{
    private const TABELAS_PERMITIDAS = ['livros', 'autores'];

    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract protected function getTabela(): string;

    /** @param array<string, mixed> $linha */
    abstract protected function hidratar(array $linha): object;

    protected function getTabelaSegura(): string
    {
        $tabela = $this->getTabela();

        if (!in_array($tabela, self::TABELAS_PERMITIDAS, true)) {
            throw new InvalidArgumentException("Identificador de tabela não permitido: {$tabela}");
        }

        return $tabela;
    }

    public function buscar(int $id): ?object
    {
        $tabela = $this->getTabelaSegura();
        $stmt = $this->pdo->prepare("SELECT * FROM {$tabela} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $this->hidratar($linha) : null;
    }

    /** @return object[] */
    public function listar(): array
    {
        $tabela = $this->getTabelaSegura();
        $stmt = $this->pdo->query("SELECT * FROM {$tabela} ORDER BY id ASC");

        $entidades = [];
        foreach ($stmt->fetchAll() as $linha) {
            $entidades[] = $this->hidratar($linha);
        }

        return $entidades;
    }

    public function excluir(int $id): bool
    {
        $tabela = $this->getTabelaSegura();
        $stmt = $this->pdo->prepare("DELETE FROM {$tabela} WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    protected function executarEmTransacao(callable $operacao): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $resultado = $operacao();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}