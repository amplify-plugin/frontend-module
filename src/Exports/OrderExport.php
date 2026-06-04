<?php

namespace Amplify\Frontend\Exports;

use Amplify\ErpApi\Collections\OrderCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrderExport implements FromView, ShouldAutoSize, WithEvents
{
    public function __construct(public OrderCollection $orders)
    {
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("system::report.order", [
            "orders" => $this->orders,
        ]);
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $range = "A1:{$highestColumn}{$highestRow}";

                // Global styling
                $sheet->getStyle($range)->applyFromArray([
                    "borders" => [
                        "allBorders" => [
                            "borderStyle" => Border::BORDER_THIN,
                            "color" => ["rgb" => "000000"],
                        ],
                    ],
                    "font" => [
                        "color" => ["rgb" => "000000"],
                    ],
                    "alignment" => [
                        "horizontal" => Alignment::HORIZONTAL_LEFT,
                    ],
                ]);

                // Header styling
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    "font" => [
                        "bold" => true,
                        "color" => ["rgb" => "000000"],
                    ],
                    "fill" => [
                        "fillType" => Fill::FILL_SOLID,
                        "startColor" => ["rgb" => "FFFF00"],
                    ],
                ]);

                // Apply fixed width and text format to all columns
                foreach (range("A", $highestColumn) as $column) {
                    // Width ≈ 120 pixels
                    $sheet->getColumnDimension($column)->setWidth(17);

                    // Force text format
                    $sheet
                        ->getStyle("{$column}1:{$column}{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode("@");

                    // Left align
                    $sheet
                        ->getStyle("{$column}1:{$column}{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            },
        ];
    }
}
