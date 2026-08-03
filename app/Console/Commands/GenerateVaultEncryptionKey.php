<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVaultEncryptionKey extends Command
{
    protected $signature = 'vault:key {--show : Display the key instead of updating the environment file} {--force : Replace an existing key}';

    protected $description = 'Generate a base64-encoded vault encryption key';

    public function handle(): int
    {
        $key = 'base64:'.base64_encode(random_bytes(32));
        if ($this->option('show')) {
            $this->line($key);

            return self::SUCCESS;
        }

        $path = app()->environmentFilePath();
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->error('The environment file could not be read.');

            return self::FAILURE;
        }

        if (preg_match('/^VAULT_ENCRYPTION_KEY=.+$/m', $contents) && ! $this->option('force')) {
            $this->error('A vault encryption key already exists. Use --force to replace it.');

            return self::FAILURE;
        }

        $replacement = 'VAULT_ENCRYPTION_KEY='.$key;
        $updated = preg_match('/^VAULT_ENCRYPTION_KEY=.*$/m', $contents)
            ? preg_replace('/^VAULT_ENCRYPTION_KEY=.*$/m', $replacement, $contents)
            : rtrim($contents).PHP_EOL.$replacement.PHP_EOL;

        if (! is_string($updated) || file_put_contents($path, $updated) === false) {
            $this->error('The environment file could not be updated.');

            return self::FAILURE;
        }

        $this->components->info('Vault encryption key set successfully.');
        $this->warn('Store this key separately from the database and never commit it.');

        return self::SUCCESS;
    }
}
