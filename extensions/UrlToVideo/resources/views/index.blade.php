@extends('panel.layout.app', [
    'disable_tblr' => true,
    'disable_titlebar' => true,
])

@push('before-head-close')
    <script>
        localStorage.setItem('lqdNavbarShrinked', 'true');
    </script>
@endpush

@section('title', __('Url To Video'))

@section('content')
    <div>
        <div
            class="lqd-external-chatbot-edit"
            x-data="aiInflucencerData"
        >
            @include('url-to-video::create-video.create-video-window', ['overlay' => false])
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiInflucencerData', () => ({
                createVideoWindowKey: 0,
                init() {
                    Alpine.store('aiInflucencerData', this);
                    this.toggleWindow();
                },
                // when open or close the window, change some neccessary css.
                toggleWindow(open = true) {
                    Alpine.nextTick(() => {
                        this.createVideoWindowKey = 1;
                    })
                }
            }))
        });
    </script>
@endpush
