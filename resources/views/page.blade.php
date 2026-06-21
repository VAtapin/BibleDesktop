<x-public-layout :title="$page->title.' - Bible Desktop'">
    <article class="cms-page">
        <h1>{{ $page->title }}</h1>
        @if ($page->excerpt)
            <p class="cms-page-excerpt">{{ $page->excerpt }}</p>
        @endif
        <div class="cms-page-content">
            {!! $page->renderedContent() !!}
        </div>
    </article>
</x-public-layout>
