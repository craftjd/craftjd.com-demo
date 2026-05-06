<?php

namespace App\Livewire;

use App\Starter\Services\FlatFileStore;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RecordsTable extends Component
{
    public int $page = 1;

    public int $perPage = 5;

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->lastPage()) {
            $this->page++;
        }
    }

    #[Computed]
    public function records(): Collection
    {
        return collect($this->allRecords())
            ->forPage($this->page, $this->perPage)
            ->values();
    }

    #[Computed]
    public function categoryTotals(): Collection
    {
        return collect($this->allRecords())
            ->groupBy('category')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc();
    }

    #[Computed]
    public function totalRecords(): int
    {
        return count($this->allRecords());
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->totalRecords / $this->perPage));
    }

    protected function allRecords(): array
    {
        return app(FlatFileStore::class)->all('records');
    }
}
