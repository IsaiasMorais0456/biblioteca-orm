<?php

declare(strict_types=1);

namespace BibliotecaORM\Contract;

interface RepositoryInterface
{
    public function buscar(int $id): ?object;

    /** @return object[] */
    public function listar(): array;

    public function salvar(object $entidade): object;

    public function excluir(int $id): bool;
}