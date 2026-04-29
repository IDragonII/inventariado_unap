<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActasExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Código',
            'Denominación',
            'Marca',
            'Modelo',
            'Número Serie',
            'Área',
            'Responsable',
            'DNI Responsable',
            'Número Acta',
            'Fecha'
        ];
    }
}