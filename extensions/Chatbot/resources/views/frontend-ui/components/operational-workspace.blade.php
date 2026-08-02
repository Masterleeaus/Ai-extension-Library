<div
    class="titan-operational-workspace col-start-1 col-end-1 row-start-1 row-end-1 h-full w-full overflow-y-auto pb-28"
    data-titan-operational
    data-template="{{ $titanShell['slug'] }}"
    data-schema='@json($titanShell)'
    x-show="currentView === 'welcome' && titanNavigation?.slug !== 'generic'"
    x-cloak
></div>
