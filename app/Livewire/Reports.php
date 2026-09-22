<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.livewire')]
class Reports extends Component
{
    public string $period = 'daily';
    public string $date;
    public string $startDate;
    public string $endDate;
    public string $categoryId = '';

    public function mount(): void
    {
        $this->date = today()->format('Y-m-d');
        $this->startDate = $this->date;
        $this->endDate = $this->date;
    }

    public function downloadPdf()
    {
        [$startDate, $endDate] = $this->dateRange();
        $sales = $this->salesForRange($startDate, $endDate);
        $metrics = $this->metricsFor($sales);
        $filename = 'sales-report-'.$startDate->format('Y-m-d').'-'.$endDate->format('Y-m-d').'.pdf';
        $pdf = Pdf::loadView('reports.pdf', [
            'sales' => $sales,
            'period' => ucfirst($this->period),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'categoryName' => $this->categoryName(),
            ...$metrics,
        ])->setPaper('a4');

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function render()
    {
        [$startDate, $endDate] = $this->dateRange();
        $sales = $this->salesForRange($startDate, $endDate);
        $metrics = $this->metricsFor($sales);

        return view('livewire.reports', [
            'sales' => $sales,
            'categories' => Category::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(),
            'categoryName' => $this->categoryName(),
            ...$metrics,
        ]);
    }

    private function salesForRange(CarbonImmutable $startDate, CarbonImmutable $endDate)
    {
        return Sale::where('tenant_id', auth()->user()->tenant_id)
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->endOfDay(),
            ])
            ->when($this->categoryId !== '', fn ($query) => $query->whereHas('items.product', fn ($query) => $query->where('category_id', $this->categoryId)))
            ->with('user', 'items.product.category')
            ->latest()
            ->get()
            ->each(function (Sale $sale): void {
                if ($this->categoryId !== '') {
                    $sale->setRelation('items', $sale->items->where('product.category_id', (int) $this->categoryId)->values());
                }
            });
    }

    private function metricsFor($sales): array
    {
        $revenue = $sales->sum(fn (Sale $sale) => $sale->items->sum('total_price'));

        return [
            'transactions' => $sales->count(),
            'revenue' => $revenue,
            'average' => $sales->count() > 0 ? $revenue / $sales->count() : 0,
        ];
    }

    private function categoryName(): string
    {
        if ($this->categoryId === '') {
            return 'All categories';
        }

        return Category::where('tenant_id', auth()->user()->tenant_id)->find($this->categoryId)?->name ?? 'All categories';
    }

    private function dateRange(): array
    {
        if ($this->period === 'custom') {
            $startDate = CarbonImmutable::parse($this->startDate);
            $endDate = CarbonImmutable::parse($this->endDate);

            return $startDate->greaterThan($endDate)
                ? [$endDate, $startDate]
                : [$startDate, $endDate];
        }

        $date = CarbonImmutable::parse($this->date);

        return match ($this->period) {
            'weekly' => [$date->startOfWeek(), $date->endOfWeek()],
            'monthly' => [$date->startOfMonth(), $date->endOfMonth()],
            default => [$date, $date],
        };
    }
}