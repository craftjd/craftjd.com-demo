<?php

namespace App\View\Components;

use App\Services\MainSiteJoesClient;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ChangelogModal extends Component
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function entries(): array
    {
        try {
            /** @var MainSiteJoesClient $client */
            $client = app(MainSiteJoesClient::class);

            return $client->fetchCompleted(10);
        } catch (\Throwable) {
            return [];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.changelog-modal', [
            'entries' => $this->entries(),
        ]);
    }
}
