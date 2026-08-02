@push('script')
    <script>
        const APS_TEMPLATE_ROUTES = {
            generate: '{{ route('dashboard.user.ai-photoshoot.templates.generate') }}',
            checkStatus: '{{ url('dashboard/user/ai-photoshoot/photo_shoots/status') }}',
            editImage: '{{ route('dashboard.user.ai-photoshoot.edit_image.index') }}',
            createVideo: '{{ route('dashboard.user.ai-photoshoot.create_video.index') }}',
            csrfToken: '{{ csrf_token() }}',
        };

        const APS_TEMPLATES = @json(config('ai-photoshoot.templates', []));
        const APS_MAX_TEMPLATES = {{ (int) config('ai-photoshoot.max_images', 4) }};

        document.addEventListener('alpine:init', () => {
            Alpine.data('templatePhotoApp', () => ({
                templates: APS_TEMPLATES,
                maxTemplates: APS_MAX_TEMPLATES,
                selectedTemplateSlugs: [],
                productImage: null,
                productPreview: null,
                ratio: @json(\App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::normalizeRatioForActiveModel($settings->ratio ?? null)),
                dragOver: false,
                generating: false,
                pendingIds: [],
                completedIds: [],
                pollAttempts: 0,
                pollTimer: null,
                results: [],

                init() {},

                isTemplateSelected(slug) {
                    return this.selectedTemplateSlugs.includes(slug);
                },

                templateOrder(slug) {
                    const idx = this.selectedTemplateSlugs.indexOf(slug);
                    return idx === -1 ? '' : idx + 1;
                },

                toggleTemplate(slug) {
                    const idx = this.selectedTemplateSlugs.indexOf(slug);
                    if (idx === -1) {
                        if (this.selectedTemplateSlugs.length >= this.maxTemplates) {
                            window.toastr?.warning('{{ __('You can select up to') }} ' + this.maxTemplates + ' {{ __('templates') }}');
                            return;
                        }
                        this.selectedTemplateSlugs.push(slug);
                    } else {
                        this.selectedTemplateSlugs.splice(idx, 1);
                    }
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

                resetResults() {
                    this.results = [];
                    this.pendingIds = [];
                    this.completedIds = [];
                },

                async submit() {
                    if (!this.productImage) {
                        window.toastr?.warning('{{ __('Please upload a product image') }}');
                        return;
                    }
                    if (this.selectedTemplateSlugs.length === 0) {
                        window.toastr?.warning('{{ __('Please pick at least one template') }}');
                        return;
                    }

                    this.generating = true;
                    this.results = [];
                    this.completedIds = [];

                    const formData = new FormData();
                    formData.append('product_image', this.productImage);
                    this.selectedTemplateSlugs.forEach(slug => formData.append('templates[]', slug));
                    formData.append('ratio', this.ratio);
                    formData.append('_token', APS_TEMPLATE_ROUTES.csrfToken);

                    try {
                        const response = await fetch(APS_TEMPLATE_ROUTES.generate, {
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

                        this.pendingIds = (data.ids || []).filter(Boolean);
                        if (!this.pendingIds.length) {
                            this.generating = false;
                            window.toastr?.error('{{ __('No generations were started.') }}');
                            return;
                        }
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
                    if (!this.generating || this._checking) return;
                    this._checking = true;

                    this.pollAttempts += 1;
                    if (this.pollAttempts > 90) {
                        this.stopPolling();
                        this.generating = false;
                        this._checking = false;
                        window.toastr?.error('{{ __('Generation timeout. Please try again.') }}');
                        return;
                    }

                    const stillPending = [];
                    let anyFailed = false;

                    await Promise.all(this.pendingIds.map(async (id) => {
                        try {
                            const response = await fetch(`${APS_TEMPLATE_ROUTES.checkStatus}/${id}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });
                            const data = await response.json();
                            const status = (data.status || '').toLowerCase();

                            if (status === 'completed') {
                                this.completedIds.push(id);
                                (data.results || []).forEach(r => {
                                    if (!this.results.some(x => x.id === r.id)) {
                                        this.results.push(r);
                                    }
                                });
                            } else if (status === 'failed') {
                                anyFailed = true;
                            } else {
                                stillPending.push(id);
                            }
                        } catch (error) {
                            console.error('Status check error:', error);
                            stillPending.push(id);
                        }
                    }));

                    this.pendingIds = stillPending;

                    if (this.pendingIds.length === 0 && this.generating) {
                        this.stopPolling();
                        this.generating = false;
                        if (this.results.length > 0) {
                            window.toastr?.success('{{ __('Photoshoot generated successfully!') }}');
                        }
                        if (anyFailed && this.results.length === 0) {
                            window.toastr?.error('{{ __('Generation failed. Please try again.') }}');
                        } else if (anyFailed) {
                            window.toastr?.warning('{{ __('Some images failed to generate.') }}');
                        }
                    }

                    this._checking = false;
                },

                editResult(result) {
                    sessionStorage.setItem('editImageData', JSON.stringify({
                        url: result.image_url,
                        id: result.id,
                        fileName: `image_${result.id}.jpg`
                    }));
                    window.location.href = APS_TEMPLATE_ROUTES.editImage;
                },

                videoResult(result) {
                    sessionStorage.setItem('apsCreateVideoData', JSON.stringify({
                        url: result.image_url,
                        id: result.id,
                        fileName: `image_${result.id}.jpg`
                    }));
                    window.location.href = APS_TEMPLATE_ROUTES.createVideo + '/' + result.id;
                },
            }));
        });
    </script>
@endpush
