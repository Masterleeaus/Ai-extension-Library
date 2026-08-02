@push('script')
    <script>
        const APS_CUSTOM_ROUTES = {
            generate: '{{ route('dashboard.user.ai-photoshoot.custom.generate') }}',
            checkStatus: '{{ url('dashboard/user/ai-photoshoot/photo_shoots/status') }}',
            editImage: '{{ route('dashboard.user.ai-photoshoot.edit_image.index') }}',
            createVideo: '{{ route('dashboard.user.ai-photoshoot.create_video.index') }}',
            csrfToken: '{{ csrf_token() }}',
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('customPhotoApp', () => ({
                productImage: null,
                productPreview: null,
                prompt: '',
                ratio: @json(\App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::normalizeRatioForActiveModel($settings->ratio ?? null)),
                numImages: {{ (int) ($settings->num_images ?? 1) }},
                dragOver: false,
                generating: false,
                currentRecordId: null,
                pollAttempts: 0,
                pollTimer: null,
                results: [],

                init() {
                    // no-op
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) this.processFile(file);
                },

                handleFileDrop(event) {
                    this.dragOver = false;
                    const file = event.dataTransfer.files[0];
                    if (file) this.processFile(file);
                },

                processFile(file) {
                    if (!file.type.startsWith('image/')) {
                        window.toastr?.error('{{ __('Please upload an image file') }}');
                        return;
                    }
                    if (file.size > {{ $apsMaxUploadMb }} * 1024 * 1024) {
                        window.toastr?.error('{{ __('File size must be less than :size MB', ['size' => $apsMaxUploadMb]) }}');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.productImage = file;
                        this.productPreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                },

                removeProduct() {
                    this.productImage = null;
                    this.productPreview = null;
                    if (this.$refs.productInput) {
                        this.$refs.productInput.value = '';
                    }
                },

                async submit() {
                    if (!this.prompt.trim()) {
                        window.toastr?.warning('{{ __('Please describe your photoshoot') }}');
                        return;
                    }

                    this.generating = true;
                    this.results = [];

                    const formData = new FormData();
                    formData.append('prompt', this.prompt);
                    formData.append('ratio', this.ratio);
                    formData.append('num_images', this.numImages);
                    formData.append('_token', APS_CUSTOM_ROUTES.csrfToken);

                    if (this.productImage) {
                        formData.append('product_image', this.productImage);
                    }

                    try {
                        const response = await fetch(APS_CUSTOM_ROUTES.generate, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || '{{ __('Generation failed') }}');
                        }

                        this.currentRecordId = data.id;
                        this.startPolling();
                    } catch (error) {
                        console.error('Generation error:', error);
                        window.toastr?.error(error.message || '{{ __('Failed to start generation') }}');
                        this.generating = false;
                    }
                },

                startPolling() {
                    this.pollAttempts = 0;
                    if (this.pollTimer) clearInterval(this.pollTimer);

                    this.pollTimer = setInterval(() => this.checkStatus(), 3000);
                },

                stopPolling() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },

                async checkStatus() {
                    if (!this.generating) return;

                    this.pollAttempts += 1;

                    if (this.pollAttempts > 90) {
                        this.stopPolling();
                        this.generating = false;
                        window.toastr?.error('{{ __('Generation timeout. Please try again.') }}');
                        return;
                    }

                    try {
                        const response = await fetch(`${APS_CUSTOM_ROUTES.checkStatus}/${this.currentRecordId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();

                        if (!this.generating) return;

                        const status = (data.status || '').toLowerCase();
                        if (status === 'completed') {
                            this.stopPolling();
                            this.generating = false;
                            this.results = data.results || [];
                            window.toastr?.success('{{ __('Photoshoot generated successfully!') }}');
                        } else if (status === 'failed') {
                            this.stopPolling();
                            this.generating = false;
                            window.toastr?.error(data.message || '{{ __('Generation failed. Please try again.') }}');
                        }
                    } catch (error) {
                        console.error('Status check error:', error);
                    }
                },

                editResult(result) {
                    sessionStorage.setItem('editImageData', JSON.stringify({
                        url: result.image_url,
                        id: result.id,
                        fileName: `image_${result.id}.jpg`
                    }));
                    window.location.href = APS_CUSTOM_ROUTES.editImage;
                },

                videoResult(result) {
                    sessionStorage.setItem('apsCreateVideoData', JSON.stringify({
                        url: result.image_url,
                        id: result.id,
                        fileName: `image_${result.id}.jpg`
                    }));
                    window.location.href = APS_CUSTOM_ROUTES.createVideo + '/' + result.id;
                },
            }));
        });
    </script>
@endpush
