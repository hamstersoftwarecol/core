@if ($rendered)
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($rendered as $html)
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">{!! $html !!}</div>
        @endforeach
    </div>
@endif
