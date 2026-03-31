<?php

declare(strict_types=1);

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'includes', "definitions.php"]);
require_once joinPath(__DIR__, "Esame.php");
require_once joinPath(__DIR__, "GestioneCarrieraStudente.php");
require_once joinPath(__DIR__, "FiltroEsami.php");
require_once joinPath(__DIR__, "EsamiInfo.php");

class CarrieraIngInf extends CarrieraLaureando
{
    public bool $hasBonus = false;
    public ?int $annoImmatricolazione = null;
    public float $mediaInformatica = 0;

    public function __construct(int|string $matricola, string $cdl, string $dataLaurea)
    {
        $this->matricola = $matricola;
        $this->cdl = $cdl;
        $this->dataLaurea = new DateTime($dataLaurea);


        $rawResult = json_decode(GestioneCarrieraStudente::restituisciCarrieraStudente($matricola), true);

        $fileFiltroEsami = FiltroEsami::getIstance()->getFilter($matricola, $cdl);
        $esamiInfo = EsamiInfo::getIstance();

        $listEsami = $rawResult['Esami']['Esame'] ?? [];

        if (!is_array($listEsami) || array_keys($listEsami) !== range(0, count($listEsami) - 1)) {
            $listEsami = [$listEsami];
        }

        foreach ($listEsami as $esameJSON) {
            if ($this->annoImmatricolazione === null) {
                $this->annoImmatricolazione = (int)$esameJSON["ANNO_IMM"];
            }

            $descr = $esameJSON['DES'] ?? null;
            if (is_array($descr)) { // caso {"@nil":"true"} o simili
                $descr = null;
            }
            if (!is_string($descr) || trim($descr) === '') {
                continue;
            }


            if (is_numeric(
                    array_search($esameJSON["DES"], $fileFiltroEsami["no-cdl"], true)
                ) || $esameJSON["SOVRAN_FLG"] === 1) {
                continue;
            }

            $this->esami[] = new Esame(
                $esameJSON,
                !is_numeric(array_search($esameJSON["DES"], $fileFiltroEsami['no-avg'])),
                is_numeric(array_search($esameJSON["DES"], $esamiInfo->esami))
            );
        }

        $this->checkBonus();

        $cfuInf = 0;
        foreach ($this->esami as $esame) {
            $this->cfuTotali += $esame->cfu;

            if (!$esame->faMedia) {
                continue;
            }

            $this->cfuMedia += $esame->cfu;
            $this->mediaPesata += $esame->voto * $esame->cfu;

            if ($esame->inf) {
                $cfuInf += $esame->cfu;
                $this->mediaInformatica += $esame->voto * $esame->cfu;
            }
        }

        if ($this->cfuMedia !== 0) {
            $this->mediaPesata /= $this->cfuMedia;
        }

        if ($cfuInf !== 0) {
            $this->mediaInformatica /= $cfuInf;
        }

        $this->mediaPesata = round($this->mediaPesata, 3);
        $this->mediaInformatica = round($this->mediaInformatica, 3);
    }


    private function checkBonus(): void
    {
        if ($this->annoImmatricolazione !== null) {
            $dataLimite = new DateTime(($this->annoImmatricolazione + 4) . "-03-01");

            $this->hasBonus = $this->dataLaurea < $dataLimite;
        }

        // Applico il bonus
        if ($this->hasBonus) {
            $lowerExam = null;
            foreach ($this->esami as $esame) {
                if (!$esame->faMedia) {
                    continue;
                }
                if ($lowerExam === null) {
                    $lowerExam = $esame;
                    continue;
                }

                if ($esame->voto <= $lowerExam->voto) {
                    if ($esame->voto < $lowerExam->voto || $esame->cfu > $lowerExam->cfu) {
                        $lowerExam = $esame;
                    }
                }
            }
            if ($lowerExam !== null) {
                $lowerExam->faMedia = false;
            }
        }
    }

}
