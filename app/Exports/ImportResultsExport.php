<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportResultsExport implements FromArray, WithHeadings
{
    protected array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function array(): array
    {
        return $this->results;
    }

    public function headings(): array
    {
        return count($this->results) > 0 ? array_keys($this->results[0]) : [];
    }
}
