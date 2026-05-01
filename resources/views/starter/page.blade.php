<x-layouts.base :title="$title" :metaDescription="$metaDescription">
    <article class="section">
        <div class="container max-w-3xl">
            <div class="prose prose-gray max-w-none">
                {!! $html !!}
            </div>
        </div>
    </article>
</x-layouts.base>
