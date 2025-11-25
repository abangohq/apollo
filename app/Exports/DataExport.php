<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class DataExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $headers;

    /**
     * Create a new export instance.
     *
     * @param Collection $data
     * @param array $headers
     * @return void
     */
    public function __construct(Collection $data, array $headers)
    {
        $this->data = $data;
        $this->headers = $headers;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->headers;
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $result = [];
        
        foreach ($this->headers as $header) {
            $result[] = data_get($row, $header);
        }
        
        return $result;
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (headers) as bold
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    /**
     * @return string
     */
    public function title(): string
    {
        return 'Data Export ' . date('Y-m-d');
    }
}