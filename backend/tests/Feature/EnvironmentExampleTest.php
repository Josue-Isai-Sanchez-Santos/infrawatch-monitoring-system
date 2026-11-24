<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvironmentExampleTest extends TestCase
{
    /**
     * @return array<string, list<string>>
     */
    private function getVariables(): array
    {
        $file = file(base_path('.env.example'), FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($file);

        $variables = [];

        foreach ($file as $line) {
            if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches) === 1) {
                $variables[$matches[1]][] = $matches[2];
            }
        }

        return $variables;
    }

    public function test_no_variable_is_declared_more_than_once(): void
    {
        foreach ($this->getVariables() as $name => $values) {
            $this->assertCount(1, $values, "Variable duplicada: {$name}");
        }
    }

    public function test_broadcasting_uses_reverb_once(): void
    {
        $variables = $this->getVariables();

        $this->assertSame(['reverb'], $variables['BROADCAST_CONNECTION'] ?? null);
    }

    public function test_local_postgresql_configuration_is_explicit(): void
    {
        $variables = $this->getVariables();

        foreach ([
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'infrawatch',
            'DB_USERNAME' => 'infrawatch_user',
            'DB_PASSWORD' => 'infrawatch_password',
        ] as $name => $value) {
            $this->assertSame([$value], $variables[$name] ?? null, $name);
        }
    }
}
