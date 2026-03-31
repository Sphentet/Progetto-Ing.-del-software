<?php

declare(strict_types=1);

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'includes', "definitions.php"]);

class EsamiInfo
{
    private static ?EsamiInfo $instance = null;

    public array $esami;

    public function __construct()
    {
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $filePath = joinPath(CONFIG_PATH, "esami_inf.json");

        if (!file_exists($filePath)) {
            throw new Exception("File di configurazione non trovato: " . $filePath);
        }

        $jsonContent = file_get_contents($filePath);
        $rawContent = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

        $this->esami = array_values(array_unique($rawContent['esami_info']));
    }

    public static function getIstance(): EsamiInfo
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}