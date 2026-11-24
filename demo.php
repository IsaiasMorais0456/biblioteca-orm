<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use BibliotecaORM\Config\Conexao;
use BibliotecaORM\Entity\Autor;
use BibliotecaORM\Entity\Livro;
use BibliotecaORM\Repository\AutorRepository;
use BibliotecaORM\Repository\LivroRepository;
use BibliotecaORM\Service\RelatorioCatalogo;

/*
 * Cada etapa da demonstração guarda suas mensagens em $secoes.
 * No final do script, o HTML percorre esse array e monta a página.
 * Tipos de mensagem: 'ok' (sucesso), 'erro' (erro esperado/capturado), 'info' (informação).
 */
$secoes = [];

function registrar(array &$secoes, string $titulo, string $tipo, string $texto): void
{
    $secoes[$titulo][] = ['tipo' => $tipo, 'texto' => $texto];
}

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$erroGeral = null;

try {
    $pdo = Conexao::criar();

    // Limpeza inicial apenas para tornar a demonstração reproduzível em múltiplas execuções
    $pdo->exec('DELETE FROM livros_autores');
    $pdo->exec('DELETE FROM livros');
    $pdo->exec('DELETE FROM autores');

    $autorRepo = new AutorRepository($pdo);
    $livroRepo = new LivroRepository($pdo);
    $relatorio = new RelatorioCatalogo();

    // 1. Cadastro
    $s = '1. Cadastro de autores e livro';
    $autor1 = $autorRepo->salvar(new Autor('Erich Gamma', 'Suiço'));
    $autor2 = $autorRepo->salvar(new Autor('Richard Helm', 'Australiano'));
    $livro = $livroRepo->salvar(new Livro('Padrões de Projetos', 1994, '978-8573076103'));

    registrar($secoes, $s, 'ok', "Autor 1 salvo com ID: {$autor1->getId()}");
    registrar($secoes, $s, 'ok', "Autor 2 salvo com ID: {$autor2->getId()}");
    registrar($secoes, $s, 'ok', "Livro salvo com ID: {$livro->getId()}");

    // 2. Busca e hidratação
    $s = '2. Busca de livro por ID e hidratação';
    $livroBuscado = $livroRepo->buscar((int) $livro->getId());
    registrar($secoes, $s, 'info', 'Classe hidratada: ' . get_class($livroBuscado));
    registrar($secoes, $s, 'ok', (string) $livroBuscado);

    // 3. Atualização
    $s = '3. Atualização e nova consulta';
    $livroBuscado->setTitulo('Padrões de Projetos: Soluções Reutilizáveis');
    $livroBuscado->setAnoPublicacao(2000);
    $livroRepo->salvar($livroBuscado);

    $livroAtualizado = $livroRepo->buscar((int) $livro->getId());
    registrar($secoes, $s, 'ok', 'Após UPDATE: ' . $livroAtualizado);

    // 4. Associação N:N
    $s = '4. Associação N:N (2 autores em 1 livro)';
    $livroRepo->vincularAutor((int) $livro->getId(), (int) $autor1->getId());
    $livroRepo->vincularAutor((int) $livro->getId(), (int) $autor2->getId());

    $livroComAutores = $livroRepo->buscar((int) $livro->getId());
    registrar($secoes, $s, 'ok', (string) $livroComAutores);
    registrar($secoes, $s, 'info', 'Quantidade de autores reconstruídos: ' . count($livroComAutores->getAutores()));

    // 5. Polimorfismo: o mesmo método recebe repositórios diferentes.
    // A saída de imprimirTodos() é capturada com ob_start() para ser exibida no HTML.
    $s = '5. Demonstração polimórfica';
    foreach ([[$autorRepo, 'Catálogo de Autores'], [$livroRepo, 'Catálogo de Livros']] as [$repo, $tituloCatalogo]) {
        ob_start();
        $relatorio->imprimirTodos($repo, $tituloCatalogo);
        $linhas = array_filter(array_map('trim', explode(PHP_EOL, (string) ob_get_clean())));
        foreach ($linhas as $linha) {
            str_starts_with($linha, '---')
                ? registrar($secoes, $s, 'titulo', trim($linha, '- '))
                : registrar($secoes, $s, 'item', ltrim($linha, '* '));
        }
    }

    // 6. Transação com rollback
    $s = '6. Transação com erro intencional e rollback';
    $livroRollback = new Livro('Livro Que Não Deve Persistir', 2026, '978-0000000001');
    try {
        // ID 999999 não existe em `autores`, forçando erro de chave estrangeira
        $livroRepo->salvarComAutores($livroRollback, [(int) $autor1->getId(), 999999]);
    } catch (PDOException $ex) {
        registrar($secoes, $s, 'erro', 'Erro capturado na transação (rollback acionado): ' . $ex->getMessage());
    }

    $verificacaoRollback = $livroRollback->getId() !== null
        ? $livroRepo->buscar($livroRollback->getId())
        : null;
    registrar(
        $secoes,
        $s,
        $verificacaoRollback === null ? 'ok' : 'erro',
        'Livro existe no banco após rollback? ' . ($verificacaoRollback === null ? 'NÃO (rollback confirmado)' : 'SIM')
    );

    // 7. Exclusão e restrição de FK
    $s = '7. Exclusão controlada e restrição de chave estrangeira';
    try {
        $livroRepo->excluir((int) $livro->getId());
    } catch (PDOException $ex) {
        registrar($secoes, $s, 'erro', 'Exclusão direta bloqueada pela FK (ON DELETE RESTRICT): ' . $ex->getMessage());
    }

    $livroRepo->desvincularAutores((int) $livro->getId());
    $excluido = $livroRepo->excluir((int) $livro->getId());
    registrar(
        $secoes,
        $s,
        $excluido ? 'ok' : 'erro',
        'Após desvincular autores, livro excluído? ' . ($excluido ? 'SIM' : 'NÃO')
    );
} catch (Throwable $ex) {
    // Erro inesperado (ex.: banco fora do ar): mostrado no topo da página
    $erroGeral = get_class($ex) . ': ' . $ex->getMessage();
}

$rotulos = ['ok' => 'OK', 'erro' => 'ERRO ESPERADO', 'info' => 'INFO'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca ORM - Demonstração</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f1f4f8;
            color: #1f2933;
        }
        header {
            background: #1e3a5f;
            color: #fff;
            padding: 24px 16px;
            text-align: center;
        }
        header h1 { margin: 0 0 4px; font-size: 1.6rem; }
        header p { margin: 0; opacity: .8; }
        main { max-width: 900px; margin: 24px auto; padding: 0 16px; }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card h2 {
            margin: 0;
            padding: 12px 16px;
            font-size: 1.05rem;
            background: #e3eaf3;
            border-bottom: 1px solid #d3dce8;
        }
        .card ul { list-style: none; margin: 0; padding: 8px 16px 12px; }
        .card li { padding: 8px 0; border-bottom: 1px solid #eef1f5; display: flex; gap: 10px; align-items: flex-start; }
        .card li:last-child { border-bottom: none; }
        .texto { flex: 1; word-break: break-word; }
        .badge {
            flex-shrink: 0;
            font-size: .7rem;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 12px;
            margin-top: 2px;
        }
        .ok .badge   { background: #d6f5e0; color: #1b7a3d; }
        .erro .badge { background: #fde2e1; color: #b42318; }
        .info .badge { background: #dde9fb; color: #1d4f91; }
        .erro .texto { color: #7a271a; font-family: Consolas, monospace; font-size: .85rem; }
        li.titulo { font-weight: bold; color: #1e3a5f; padding-top: 12px; }
        li.item::before { content: "•"; color: #1e3a5f; }
        .falha {
            background: #fde2e1;
            color: #7a271a;
            border-left: 5px solid #b42318;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        footer { text-align: center; color: #6b7785; font-size: .85rem; padding: 0 16px 24px; }
    </style>
</head>
<body>
<header>
    <h1>📚 Biblioteca ORM</h1>
    <p>Demonstração do mapeamento objeto-relacional com PDO</p>
</header>

<main>
    <?php if ($erroGeral !== null): ?>
        <div class="falha">
            <strong>A demonstração foi interrompida por um erro inesperado:</strong><br>
            <?= e($erroGeral) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($secoes as $titulo => $mensagens): ?>
        <section class="card">
            <h2><?= e($titulo) ?></h2>
            <ul>
                <?php foreach ($mensagens as $m): ?>
                    <li class="<?= e($m['tipo']) ?>">
                        <?php if (isset($rotulos[$m['tipo']])): ?>
                            <span class="badge"><?= $rotulos[$m['tipo']] ?></span>
                        <?php endif; ?>
                        <span class="texto"><?= e($m['texto']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</main>

<footer>Executado em <?= date('d/m/Y H:i:s') ?></footer>
</body>
</html>
