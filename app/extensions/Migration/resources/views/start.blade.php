@extends('migration::partials.layouts.master')

@section('template_title')
    @lang('Titan Migration Engine')
@endsection

@section('container')
    <form class="migration-form group/form flex flex-col gap-6" action="{{ route('migration::migrate') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <h4 class="mb-6 mt-0 font-body text-[26px]">{{ __('Start Migration') }}</h4>
        <p class="mb-9 text-[75px] leading-none">🪄</p>
        <div>
            <x-forms.input class="flex h-11 items-center justify-center gap-2 rounded-lg border border-transparent p-2 font-medium shadow-sm transition-all duration-300" id="provider" label="{{ __('Select the source system you want to migrate from') }}" name="provider" type="select" size="md">
                @foreach($providers as $provider)
                    <option value="{{ $provider->enum()->value }}">{{ $provider->getName() }}</option>
                @endforeach
            </x-forms.input>
            <div id="capabilitiesBox" class="hidden">
                <div class="my-6 rounded-lg border p-4 shadow-sm">
                    <h5 class="mb-3 text-lg font-semibold">@lang('What can be migrated?')</h5>
                    <ul id="capabilitiesList" class="m-auto list-inside list-disc space-y-1 text-left text-sm text-gray-700"></ul>
                </div>
                <x-forms.input class="mb-5 flex items-center justify-center gap-2 rounded-lg border border-transparent p-2 font-medium shadow-sm transition-all duration-300" id="sql_file" size="lg" label="{{ __('Upload the database dump (.sql) file') }}" name="sql_file" type="file" accept=".sql" />
                <x-forms.input class="mb-5 flex items-center justify-center gap-2 rounded-lg border border-transparent p-2 font-medium shadow-sm transition-all duration-300" id="env_file" size="lg" label="{{ __('Upload the environment (.env) file when required') }}" name="env_file" type="file" />
                <x-button class="rounded-lg shadow-sm" size="lg" onclick="{{ $app_is_demo ? 'return toastr.info(\'This feature is disabled in Demo version.\')' : '' }}" type="{{ $app_is_demo ? 'button' : 'submit' }}" variant="primary">{{ __('Migrate Data') }}</x-button>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const capabilitiesMap = @json($capabilities);
                const providerSelect = document.getElementById('provider');
                const capabilitiesBox = document.getElementById('capabilitiesBox');
                const capabilitiesList = document.getElementById('capabilitiesList');
                const renderCapabilities = () => {
                    const capabilities = capabilitiesMap[providerSelect.value] || [];
                    capabilitiesList.innerHTML = '';
                    capabilities.forEach((capability) => {
                        const item = document.createElement('li');
                        item.textContent = capability;
                        capabilitiesList.appendChild(item);
                    });
                    capabilitiesBox.classList.toggle('hidden', !providerSelect.value);
                };
                renderCapabilities();
                providerSelect.addEventListener('change', renderCapabilities);
            });
        </script>
    </form>
@endsection
