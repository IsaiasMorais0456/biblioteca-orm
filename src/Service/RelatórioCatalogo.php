<?php

declare(strict_types=1);

namespace BibliotecaORM\Service;

use BibliotecaORM\Contract\RepositoryInterface;

class RelatorioCatalogo
{
    public function imprimirTodos(RepositoryInterface $repositorio, string $tituloSecao): void
    {
        echo "--- {$tituloSecao} ---" . PHP_EOL;

        $itens = $repositorio->listar();

        if ($itens === []) {
            echo '(nenhum registro encontrado)' . PHP_EOL;
            return;
        }

        foreach ($itens as $item) {
            echo ' * ' . $item . PHP_EOL;
        }
    }
}