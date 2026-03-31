<?php

declare(strict_types=1);

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'includes', "definitions.php"]);
require_once joinPath(__DIR__, "CalcoloReportistica.php");

class Esame
{
    private static ?int $lode = null;
    public string $nome;
    public int $voto;
    public int $cfu;
    public bool $faMedia;
    public bool $inf;


    public function __construct(array $esameJSON, bool $faMedia, bool $inf = false)
    {
        $this->nome = $esameJSON["DES"];
        $this->cfu = (int)$esameJSON["PESO"];
        $this->faMedia = $faMedia;
        $this->inf = $inf;

        $this->voto = match ($esameJSON["VOTO"]) {
            "30L" => Esame::getLode(),
            "30  e lode" => Esame::getLode(),
            null => 0,
            default => (int)$esameJSON["VOTO"]
        };
    }

    public static function getLode(): int
    {
        if (self::$lode === null) {
            self::$lode = CalcoloReportistica::getInstance()->getLode();
        }
        return self::$lode;
    }
}