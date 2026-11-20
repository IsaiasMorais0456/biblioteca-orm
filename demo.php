<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use BibliotecaORM\Config\Conexao;
use BibliotecaORM\Entity\Autor;
use BibliotecaORM\Entity\Livro;
use BibliotecaORM\Repository\AutorRepository;
use BibliotecaORM\Repository\LivroRepository;
use BibliotecaORM\Service\RelatorioCatalogo;

$pdo = Conexao::criar();

// Limpeza inicial apenas para tornar a demonstração reproduzível em múltiplas execuções
$pdo->exec('DELETE FROM livros_autores');
$pdo->exec('DELETE FROM livros');
$pdo->exec('DELETE FROM autores');

$autorRepo = new AutorRepository($pdo);
$livroRepo = new LivroRepository($pdo);
$relatorio = new RelatorioCatalogo();

echo "=== 1. CADASTRO DE AUTORES E LIVRO ===" . PHP_EOL;
$autor1 = $autorRepo->salvar(new Autor('Erich Gamma', 'Suiço'));
$autor2 = $autorRepo->salvar(new Autor('Richard Helm', 'Australiano'));
$livro = $livroRepo->salvar(new Livro('Padrões de Projetos', 1994, '978-8573076103'));

echo "Autor 1 salvo com ID: {$autor1->getId()}" . PHP_EOL;
echo "Autor 2 salvo com ID: {$autor2->getId()}" . PHP_EOL;
echo "Livro salvo com ID: {$livro->getId()}" . PHP_EOL . PHP_EOL;

echo "=== 2. BUSCA DE LIVRO POR ID E HIDRATAÇÃO ===" . PHP_EOL;
$livroBuscado = $livroRepo->buscar((int) $livro->getId());
echo 'Classe hidratada: ' . get_class($livroBuscado) . PHP_EOL;
echo $livroBuscado . PHP_EOL . PHP_EOL;

echo "=== 3. ATUALIZAÇÃO E NOVA CONSULTA ===" . PHP_EOL;
$livroBuscado->setTitulo('Padrões de Projetos: Soluções Reutilizáveis');
$livroBuscado->setAnoPublicacao(2000);
$livroRepo->salvar($livroBuscado);

$livroAtualizado = $livroRepo->buscar((int) $livro->getId());
echo 'Após UPDATE: ' . $livroAtualizado . PHP_EOL . PHP_EOL;

echo "=== 4. ASSOCIAÇÃO N:N (2 AUTORES EM 1 LIVRO) ===" . PHP_EOL;
$livroRepo->vincularAutor((int) $livro->getId(), (int) $autor1->getId());
$livroRepo->vincularAutor((int) $livro->getId(), (int) $autor2->getId());

$livroComAutores = $livroRepo->buscar((int) $livro->getId());
echo $livroComAutores . PHP_EOL;
echo 'Quantidade de autores reconstruídos: ' . count($livroComAutores->getAutores()) . PHP_EOL . PHP_EOL;

echo "=== 5. DEMONSTRAÇÃO POLIMÓRFICA ===" . PHP_EOL;
$relatorio->imprimirTodos($autorRepo, 'Catálogo de Autores');
$relatorio->imprimirTodos($livroRepo, 'Catálogo de Livros');
echo PHP_EOL;

echo "=== 6. TRANSAÇÃO COM ERRO INTENCIONAL E ROLLBACK ===" . PHP_EOL;
$livroRollback = new Livro('Livro Que Não Deve Persistir', 2026, '978-0000000001');
try {
    // ID 999999 não existe em `autores`, forçando erro de chave estrangeira
    $livroRepo->salvarComAutores($livroRollback, [(int) $autor1->getId(), 999999]);
} catch (PDOException $e) {
    echo 'Erro capturado na transação (rollback acionado): ' . $e->getMessage() . PHP_EOL;
}

$verificacaoRollback = $livroRollback->getId() !== null
    ? $livroRepo->buscar($livroRollback->getId())
    : null;
echo 'Livro existe no banco após rollback? ' . ($verificacaoRollback === null ? 'NÃO (rollback confirmado)' : 'SIM') . PHP_EOL . PHP_EOL;

echo "=== 7. EXCLUSÃO CONTROLADA E RESTRIÇÃO DE CHAVE ESTRANGEIRA ===" . PHP_EOL;
try {
    $livroRepo->excluir((int) $livro->getId());
} catch (PDOException $e) {
    echo 'Exclusão direta bloqueada pela FK (ON DELETE RESTRICT): ' . $e->getMessage() . PHP_EOL;
}

$livroRepo->desvincularAutores((int) $livro->getId());
$excluido = $livroRepo->excluir((int) $livro->getId());
echo 'Após desvincular autores, livro excluído? ' . ($excluido ? 'SIM' : 'NÃO') . PHP_EOL;