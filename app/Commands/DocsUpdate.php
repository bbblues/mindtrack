<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Atualiza o hash do commit e a data de verificacao no
 * docs/STATE_OF_PROJECT.md, evitando drift de documentacao.
 *
 * Uso: php spark docs:update
 */
class DocsUpdate extends BaseCommand
{
    protected $group       = 'Docs';
    protected $name        = 'docs:update';
    protected $usage       = 'docs:update';
    protected $description = 'Atualiza o hash do commit e a data em docs/STATE_OF_PROJECT.md.';

    /**
     * @param array<array-key, string|null> $params
     *
     * @return int
     */
    public function run(array $params)
    {
        $file = ROOTPATH . 'docs/STATE_OF_PROJECT.md';

        if (! is_file($file)) {
            CLI::error('Arquivo nao encontrado: ' . $file);

            return EXIT_ERROR;
        }

        $hash    = trim((string) shell_exec('git rev-parse --short HEAD 2>&1'));
        $subject = trim((string) shell_exec('git log -1 --pretty=%s 2>&1'));

        if ($hash === '' || preg_match('/fatal|not a git/i', $hash)) {
            CLI::error('Nao foi possivel obter o hash do git. Rode dentro do repositorio.');

            return EXIT_ERROR;
        }

        $markdown = file_get_contents($file);

        if ($markdown === false) {
            CLI::error('Falha ao ler ' . $file);

            return EXIT_ERROR;
        }

        $date = date('d/m/Y');

        $markdown = preg_replace(
            '/> Última verificação: \*\*[^*]*\*\*\./',
            '> Última verificação: **' . $date . '**.',
            $markdown,
            1
        );

        $markdown = preg_replace(
            '/último commit: `[0-9a-f]+` \(`.*?`\)/s',
            'último commit: `' . $hash . '` (`' . $subject . '`)',
            $markdown,
            1
        );

        if (file_put_contents($file, $markdown) === false) {
            CLI::error('Falha ao gravar ' . $file);

            return EXIT_ERROR;
        }

        CLI::write(sprintf('STATE_OF_PROJECT.md atualizado — commit %s (%s).', $hash, $date), 'green');

        return EXIT_SUCCESS;
    }
}
