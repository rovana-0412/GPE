<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RapportPatrimoineExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $indicateurs) {}

    public function array(): array
    {
        return array_map(
            static fn (array $row): array => [$row['Indicateur'], $row['Valeur']],
            $this->indicateurs,
        );
    }

    public function headings(): array
    {
        return ['Indicateur', 'Valeur'];
    }

    public function title(): string
    {
        return 'Rapport GPE';
    }
}
