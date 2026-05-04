<x-layouts.base :title="$title" :metaDescription="$metaDescription">
    <x-page-header :title="$title" :description="$metaDescription" />

    <article class="section">
        <div class="container max-w-3xl">
            <div class="prose">
                {!! $html !!}
            </div>
        </div>
    </article>
</x-layouts.base>
