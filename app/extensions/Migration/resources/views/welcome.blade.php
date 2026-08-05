@extends('migration::partials.layouts.master')

@section('template_title')
    @lang('Titan Migration Engine')
@endsection

@section('container')
    <div class="mx-auto w-4/5">
        <h4 class="mb-20 mt-0 font-body text-[26px]">{{ __('Welcome to Titan Migration Engine') }}</h4>
        <p class="mb-9 text-[75px] leading-none">🪄</p>
        <h1 class="mb-10 mt-0 font-body text-[45px]">{{ __('Let’s start.') }}</h1>
        <p class="mb-10 text-[20px]">{{ __('Migrate data into Titan while preserving the existing Davinci migration workflow during the universal-engine upgrade.') }}</p>
        <p class="mb-10 text-[20px]">{{ __('This foundation release retains the legacy importer. Use only reviewed source exports and follow the migration documentation.') }}</p>
    </div>
    <a class="flex items-center justify-center gap-2 rounded-xl p-2 font-medium shadow-[0_4px_10px_rgba(0,0,0,0.05)] transition-all duration-300 hover:scale-105 hover:bg-black hover:text-white" href="{{ route('migration::start') }}">
        {{ __('Next') }}
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6l-6 6"></path></svg>
    </a>
@endsection
