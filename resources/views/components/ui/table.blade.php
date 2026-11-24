@props([
    'headers' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200 bg-white shadow-xs']) }}>
    <table class="w-full table-fixed divide-y divide-slate-200 text-left text-sm responsive-card-table">
        @if(isset($header) || $headers)
            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
                @if(isset($header))
                    {{ $header }}
                @elseif($headers)
                    <tr>
                        @foreach($headers as $th)
                            <th scope="col" class="px-3 py-3.5">
                                {{ $th }}
                            </th>
                        @endforeach
                    </tr>
                @endif
            </thead>
        @endif
        <tbody class="divide-y divide-slate-100 text-slate-800">
            {{ $slot }}
        </tbody>
        @if(isset($footer))
            <tfoot class="bg-slate-50 border-t border-slate-200 font-medium text-slate-600">
                {{ $footer }}
            </tfoot>
        @endif
    </table>
</div>
