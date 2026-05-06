<div class="grid gap-8 lg:grid-cols-2">
    <div>
        <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Project</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($this->records as $record)
                        <tr wire:key="record-{{ $record['id'] }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $record['name'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $record['category'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $record['status'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ $record['score'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($this->lastPage() > 1)
            <div class="mt-4 flex items-center justify-between text-sm">
                <p class="text-gray-500">
                    Page {{ $page }} of {{ $this->lastPage() }}
                    <span class="text-gray-400">({{ $this->totalRecords }} records)</span>
                </p>
                <div class="flex gap-2">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($page <= 1)
                        class="btn btn-secondary px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled($page >= $this->lastPage())
                        class="btn btn-secondary px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-8">
        <div class="card">
            <h2 class="text-lg font-semibold text-gray-900">Score breakdown</h2>
            <p class="mt-1 text-sm text-gray-500">Bars reflect the current page of records.</p>

            <div class="mt-6 space-y-4">
                @foreach ($this->records as $record)
                    <div wire:key="bar-{{ $record['id'] }}">
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $record['name'] }}</span>
                            <span class="text-gray-500">{{ $record['score'] }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                            <div
                                class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                                style="width: {{ $record['score'] }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2 class="text-lg font-semibold text-gray-900">By category</h2>
            <p class="mt-1 text-sm text-gray-500">Totals from the full dataset in <code class="text-xs">storage/app/private/data/records.json</code>.</p>

            <div class="mt-6 space-y-3">
                @php $max = $this->categoryTotals->max() ?: 1; @endphp
                @foreach ($this->categoryTotals as $category => $count)
                    <div wire:key="category-{{ $category }}">
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $category }}</span>
                            <span class="text-gray-500">{{ $count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                            <div
                                class="h-2 rounded-full bg-emerald-500"
                                style="width: {{ round(($count / $max) * 100) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
