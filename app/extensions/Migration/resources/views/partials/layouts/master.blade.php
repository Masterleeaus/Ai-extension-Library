@props([
    'stepShow' => $stepShow ?? true,
])
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @if (trim($__env->yieldContent('template_title')))
            @yield('template_title')
        @endif
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/favicon.ico">
    @php
        $link = 'resources/views/' . get_theme() . '/scss/landing-page.scss';
    @endphp
    @vite($link)
    @yield('style')
    <script>
        window.Laravel = <?php echo json_encode(['csrfToken' => csrf_token()]); ?>
    </script>
</head>
<body class="bg-white font-body font-normal text-[#272D38] antialiased">
<div class="container py-6">
    <div class="text-center">
        <svg class="mx-auto mb-9 mix-blend-luminosity" width="54" height="54" viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M23.1975 34.5546L23.7743 34.4375C24.347 34.32 24.347 33.157 23.7743 33.0395L23.1975 32.9223C21.76 32.631 20.635 31.506 20.3437 30.0685L20.1949 29.4601C20.08 28.8874 18.912 28.8874 18.7969 29.4601L18.6798 30.037C18.3885 31.4745 17.2635 32.5995 15.826 32.8908L15.2176 33.0395C14.6448 33.157 14.6448 34.32 15.2176 34.4375L15.7944 34.5546C17.2319 34.846 18.3569 35.971 18.6482 37.4085L18.7969 38.0168C18.912 38.5896 20.08 38.5896 20.1949 38.0168L20.3121 37.44C20.6034 36.0025 21.7284 34.8775 23.1975 34.5546Z" fill="#5391E4"/>
            <path d="M37.178 24.1095L39.3007 23.6791C40.3 23.47 40.3 22.05 39.3007 21.8404L37.178 21.41C35.12 20.99 33.45 19.32 33.0292 17.2612L32.5988 15.1385C32.39 14.14 30.97 14.14 30.7616 15.1385L30.3312 17.2612C29.91 19.32 28.24 20.99 26.1824 21.41L24.0597 21.8404C23.06 22.05 23.06 23.47 24.0597 23.6791L26.1824 24.1095C28.24 24.53 29.91 26.2 30.3312 28.2583L30.7616 30.381C30.97 31.38 32.39 31.38 32.5988 30.381L33.0292 28.2583C33.45 26.2 35.12 24.53 37.178 24.1095Z" fill="#8D65E9"/>
        </svg>
        @if (session('message') || session()->has('errors'))
            <div class="relative mx-auto w-1/2 px-10 text-start">
                <div class="mb-8 mt-3 flex flex-col">
                    @if (session('message'))
                        <p class="alert text-center" style="color:red;"><strong>{{ is_array(session('message')) ? session('message')['message'] : session('message') }}</strong></p>
                    @endif
                    @if (session()->has('errors'))
                        <div class="relative z-10 w-full rounded-lg bg-red-100 p-5 text-sm font-medium" id="error_alert">
                            <button class="close absolute -end-4 -top-4 flex size-8 items-center justify-center rounded-full bg-red-900 text-white" id="close_alert" type="button" aria-label="Close errors">×</button>
                            <h4 class="mb-2">{{ trans('installer_messages.forms.errorTitle') }}</h4>
                            <ol class="ms-[24px] list-inside list-decimal ps-2">
                                @foreach ($errors->all() as $error)
                                    <li class="mb-[2px] last:mb-0">{{ $error }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        <div class="relative mx-auto w-full md:w-1/2 lg:w-1/2">
            <div class="relative rounded-lg px-10 py-8 shadow-sm backdrop-blur-md backdrop-saturate-150">
                @yield('container')
            </div>
        </div>
    </div>
</div>
@yield('scripts')
<script>
    const errorAlert = document.getElementById('error_alert');
    const closeAlert = document.getElementById('close_alert');
    if (closeAlert) closeAlert.onclick = () => { errorAlert.style.display = 'none'; };
</script>
</body>
</html>
