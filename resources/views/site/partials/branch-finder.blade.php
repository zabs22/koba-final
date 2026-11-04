{{-- "Choose myself" or "Find the nearest café" for a form's branch select ($for: order|contact, $select: the select's id).
     The locator (/find-a-cafe) sends the choice back as ?branch=…; resources/js/site/branch-finder.js keeps the rest of the form. --}}
@php $t = trans('koba.locator'); @endphp
<div class="branch-finder" data-branch-finder="{{ $for }}" data-select="{{ $select }}"
     data-msg-found="{{ $t['found'] }}" data-msg-found-estimated="{{ $t['found_estimated'] }}" data-msg-picked="{{ $t['found_picked'] }}">
    <div class="branch-finder__modes" role="group" aria-label="{{ $t['how'] }}">
        <button class="branch-finder__mode is-on" type="button" data-branch-self aria-pressed="true">
            <svg viewBox="0 0 24 24" width="17" height="17" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            {{ $t['choose_myself'] }}
        </button>
        <a class="branch-finder__mode" href="{{ route('locator', ['for' => $for]) }}" data-branch-near>
            <svg viewBox="0 0 24 24" width="17" height="17" aria-hidden="true"><circle cx="12" cy="12" r="3.2" fill="currentColor"/><circle cx="12" cy="12" r="7.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 1.8v3M12 19.2v3M1.8 12h3M19.2 12h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            {{ $t['find_nearest'] }}
        </a>
    </div>
    <p class="branch-finder__found" data-branch-found role="status" hidden></p>
</div>
