        {{-- Photo & Location Section --}}
        <div class="pt-3 border-t border-slate-100">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Photo &amp; Location</span>
          <div class="grid grid-cols-2 gap-3">
            <div>
              @if($report->photos->isNotEmpty())
                @php $firstPhoto = $report->photos->first(); @endphp
                <div class="relative group">
                  <img src="{{ route('admin.reports.photos.show', [$report, $firstPhoto]) }}"
                       alt="Resident report photo"
                       data-lightbox-src="{{ route('admin.reports.photos.show', [$report, $firstPhoto]) }}"
                       class="rounded-xl object-cover h-36 w-full cursor-zoom-in border border-slate-200">
                  @if($report->photos->count() > 1)
                    <span class="absolute bottom-1.5 left-1.5 rounded-md bg-slate-900/75 text-white text-[10px] font-bold px-2 py-0.5">
                      +{{ $report->photos->count() - 1 }} more
                    </span>
                  @endif
                </div>
                @if($report->photos->count() > 1)
                  <div class="flex gap-1.5 mt-1.5 overflow-x-auto">
                    @foreach($report->photos->skip(1) as $extraPhoto)
                      <img src="{{ route('admin.reports.photos.show', [$report, $extraPhoto]) }}"
                           alt="Additional resident report photo"
                           data-lightbox-src="{{ route('admin.reports.photos.show', [$report, $extraPhoto]) }}"
                           class="h-10 w-10 rounded-lg object-cover cursor-zoom-in border border-slate-200 flex-shrink-0">
                    @endforeach
                  </div>
                @endif
              @elseif($report->photo_path)
                <img src="{{ route('admin.reports.photo', $report) }}"
                     alt="Resident report photo"
                     data-lightbox-src="{{ route('admin.reports.photo', $report) }}"
                     class="rounded-xl object-cover h-36 w-full cursor-zoom-in border border-slate-200">
              @else
                <div class="h-36 rounded-xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-400 italic text-center px-2">
                  No photo attached
                </div>
              @endif
            </div>

            <div>
              @if($report->lat !== null && $report->lng !== null)
                <div id="report-location-map" class="relative z-0 isolate h-36 rounded-xl overflow-hidden border border-slate-200"
                     data-lat="{{ $report->lat }}" data-lng="{{ $report->lng }}"
                     role="img" aria-label="Map showing the reported location"></div>
              @else
                <div class="h-36 rounded-xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-400 italic text-center px-2">
                  No map pin shared
                </div>
              @endif
            </div>
          </div>

          <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
            <div>
              <strong>Purok:</strong>
              @if($residentPurok)
                <span>{{ $residentPurok->name }}</span>
              @else
                <span class="italic text-slate-400">Not provided</span>
              @endif
              <span class="mx-1 text-slate-300">·</span>
              <strong>Landmark:</strong>
              @if(trim((string) $residentLandmark) !== '')
                <span>{{ $residentLandmark }}</span>
              @else
                <span class="italic text-slate-400">Not provided</span>
              @endif
            </div>
            @if($report->lat !== null && $report->lng !== null)
              <div class="mt-1.5 text-[11px] text-slate-500">
                <span class="font-semibold text-slate-600">Original resident-submitted incident coordinates:</span>
                <span class="font-mono text-slate-700">{{ number_format($report->lat, 6) }}, {{ number_format($report->lng, 6) }}</span>
              </div>
            @else
              <div class="mt-1.5 text-[11px] text-slate-400 italic">
                No GPS coordinates submitted by resident.
              </div>
            @endif
            @if($googleMapsUrl)
              <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline font-semibold inline-flex items-center gap-1">
                Open in Google Maps <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i>
              </a>
            @endif
          </div>
        </div>