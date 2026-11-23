# BibliotecaORM

Biblioteca própria de Mapeamento Objeto-Relacional (ORM) desenvolvida em **PHP puro** com **PDO** e **MySQL**, sem utilização de frameworks ou ORMs de terceiros.

Projeto elaborado para a avaliação prática da disciplina de **Programação II** — Curso de Ciência da Computação (**UNOESC**).

---

## Objetivo

Demonstrar, de forma integrada, a aplicação dos pilares de Programação Orientada a Objetos (encapsulamento, herança, abstração, interfaces e polimorfismo), relacionamentos entre classes e persistência relacional segura com MySQL e PDO, mantendo separação clara entre **Domínio** e **Infraestrutura**.

---

## Estrutura do Projeto

```text
biblioteca-orm/
├── sql/
│   └── schema.sql                # Criação do banco, tabelas, PKs e FKs
├── src/
│   ├── Config/
│   │   └── Conexao.php           # Fábrica de conexão PDO via variáveis de ambiente
│   ├── Contract/
│   │   └── RepositoryInterface.php # Contrato comum dos repositórios
│   ├── Entity/
│   │   ├── Autor.php             # Entidade Autor encapsulada e validada
│   │   └── Livro.php             # Entidade Livro com coleção de autores
│   ├── Repository/
│   │   ├── AbstractRepository.php # Comportamentos compartilhados, whitelist e transação
│   │   ├── AutorRepository.php    # Persistência e hidratação de Autor
│   │   └── LivroRepository.php    # CRUD de Livro, vínculo N:N e operação transacional
│   └── Service/
│       └── RelatorioCatalogo.php  # Serviço polimórfico baseado em RepositoryInterface
├── autoload.php                  # Autoloader PSR-4 simplificado em PHP puro
├── demo.php                      # Script reproduzível com as 7 evidências exigidas
└── README.md                     # Documentação do projeto
```

---

## Requisitos Atendidos

- **R1 — Modelo Relacional:** Banco `biblioteca_orm` com tabelas `autores`, `livros` e tabela associativa `livros_autores`, contendo chaves primárias (PK), chaves estrangeiras (FK) e integridade referencial (`ON DELETE RESTRICT`).
- **R2 — Encapsulamento e Entidades:** `Livro` e `Autor` com atributos privados, validações nos construtores/setters e proteção contra alteração indevida de `id` após definição.
- **R3 — Contrato:** Interface `RepositoryInterface` definindo `buscar(int)`, `listar()`, `salvar(object)` e `excluir(int)`.
- **R4 — Herança e Abstração:** `AbstractRepository` centraliza consulta por ID, listagem, exclusão, validação de tabela e controle de transação; `LivroRepository` e `AutorRepository` especializam tabela, mapeamento e hidratação.
- **R5 — Polimorfismo:** `RelatorioCatalogo::imprimirTodos(RepositoryInterface $repositorio, string $tituloSecao)` opera com ambos os repositórios sem verificar classes concretas.
- **R6 — CRUD:** Operações completas (`INSERT`, `SELECT` por ID, listagem, `UPDATE` e `DELETE`) implementadas via PDO.
- **R7 — Hidratação:** Conversão de registros do MySQL em instâncias reais de `Livro` e `Autor`, preservando tipos e identidade.
- **R8 — Relacionamento N:N:** Persistência e recuperação de autores por livro via `livros_autores`, utilizando mapa de identidade por carregamento para evitar duplicação de instâncias de `Autor` em memória.
- **R9 — Segurança e Transações:** Uso exclusivo de prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`), lista branca (*whitelist*) para nomes de tabelas e gravação de livro + vínculos em transação com `rollBack()` em caso de falha.
- **R10 — Organização e Segurança de Credenciais:** Separação por responsabilidades, script reproduzível e nenhuma senha real versionada no repositório.

---

## Pré-requisitos

- **PHP 8.0** ou superior (com a extensão `pdo_mysql` habilitada)
- **MySQL 8.0+** ou **MariaDB 10.4+**
- Terminal (PowerShell, Bash ou CMD) para execução via CLI

Para verificar o PHP e a extensão PDO no terminal:

```bash
php -v
php -m | grep pdo_mysql
```

*(No Windows PowerShell, use `php -m | Select-String pdo_mysql`.)*

---

## Configuração do Ambiente

### 1. Criar o banco de dados

Importe o arquivo `sql/schema.sql` no seu servidor MySQL:

```bash
mysql -u root -p < sql/schema.sql
```

*(Alternativamente, abra e execute o conteúdo de `sql/schema.sql` no MySQL Workbench, DBeaver ou HeidiSQL.)*

### 2. Configurar as variáveis de ambiente

A classe `Conexao` lê as credenciais diretamente das variáveis de ambiente para evitar credenciais fixas no código-fonte:

| Variável   | Padrão           | Descrição                        |
| ---------- | ---------------- | -------------------------------- |
| `DB_HOST`  | `127.0.0.1`      | Endereço do servidor MySQL       |
| `DB_PORT`  | `3306`           | Porta do servidor MySQL          |
| `DB_NAME`  | `biblioteca_orm` | Nome do banco de dados           |
| `DB_USER`  | `root`           | Usuário do banco de dados        |
| `DB_PASS`  | *(vazio)*        | Senha do usuário do banco        |

---

## Como Executar a Demonstração

Execute o script `demo.php` informando suas credenciais locais (caso sejam diferentes dos valores padrão):

### Windows (PowerShell)

```powershell
$env:DB_HOST="127.0.0.1"
$env:DB_PORT="3306"
$env:DB_NAME="biblioteca_orm"
$env:DB_USER="root"
$env:DB_PASS="sua_senha_aqui"
php demo.php
```

### Linux / macOS (Bash/Zsh)

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=biblioteca_orm DB_USER=root DB_PASS=sua_senha_aqui php demo.php
```

### Ambiente local padrão (`root` sem senha)

```bash
php demo.php
```

---

## Evidências Executadas pelo `demo.php`

Ao rodar `php demo.php`, o script limpa os dados de teste anteriores (tornando a execução reproduzível) e executa sequencialmente as 7 evidências solicitadas:

1. **Cadastro de Livro e Autores:** Persiste dois autores e um livro, exibindo os IDs gerados pelo `AUTO_INCREMENT` do MySQL.
2. **Busca por ID e Hidratação:** Recupera o livro pelo ID e comprova a reconstrução da instância `BibliotecaORM\Entity\Livro`.
3. **Atualização (UPDATE):** Altera título e ano de publicação, persiste e realiza nova consulta confirmando a mudança no banco.
4. **Associação N:N:** Vincula os dois autores ao livro na tabela `livros_autores` e reconstrói a coleção de autores no objeto `Livro`.
5. **Demonstração Polimórfica:** Invoca `RelatorioCatalogo::imprimirTodos()` passando `AutorRepository` e `LivroRepository` através da mesma interface `RepositoryInterface`.
6. **Transação com Erro Intencional e Rollback:** Tenta salvar um novo livro vinculado a um `autor_id` inexistente (`999999`) dentro de `salvarComAutores()`, captura a `PDOException` e comprova que o livro não foi persistido após o `rollBack()`.
7. **Exclusão Controlada e Integridade Referencial:** Demonstra o bloqueio de exclusão direta de um livro com vínculos ativos devido à restrição `ON DELETE RESTRICT`, seguido da remoção controlada dos vínculos e exclusão bem-sucedida do livro.

---

## Decisões Arquiteturais e Limitações Conhecidas

- **Integridade referencial (`ON DELETE RESTRICT`):** Optou-se por restringir a exclusão em cascata automática na tabela `livros_autores` para impedir perdas silenciosas de vínculos e exigir desvinculação explícita antes da remoção de um registro principal.
- **Segurança contra SQL Injection:** Todos os valores externos são enviados via *prepared statements* nativos, e o nome da tabela utilizado em `AbstractRepository` passa por validação em lista branca (`TABELAS_PERMITIDAS`).
- **Limitação conhecida (N+1 na listagem):** O método `LivroRepository::listar()` carrega os autores de cada livro por consulta individual, mantendo o código enxuto e didático. Em bases maiores, a melhoria natural seria o carregamento em lote (*eager loading* com cláusula `IN`).