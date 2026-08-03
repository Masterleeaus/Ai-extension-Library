@php
    $apsMaxUploadMb = \App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::getMaxUploadSizeMb();
@endphp
@push('script')
    <script>
        const APS_MAX_UPLOAD_MB = {{ $apsMaxUploadMb }};

        const APS_BG_ROUTES = {
            generate: '{{ route('dashboard.user.ai-photoshoot.replace-background.generate') }}',
            checkStatus: '{{ url('dashboard/user/ai-photoshoot/photo_shoots/status') }}',
            editImage: '{{ route('dashboard.user.ai-photoshoot.edit_image.index') }}',
            createVideo: '{{ route('dashboard.user.ai-photoshoot.create_video.index') }}',
            backgroundsLoad: '{{ route('dashboard.user.ai-photoshoot.background.load') }}',
            backgroundsUpload: '{{ route('dashboard.user.ai-photoshoot.background.upload') }}',
            backgroundsCreate: '{{ route('dashboard.user.ai-photoshoot.background.create') }}',
            backgroundsStatusBase: '{{ url('dashboard/user/ai-photoshoot/backgrounds/load/status') }}',
            backgroundsDeleteBase: '{{ url('dashboard/user/ai-photoshoot/backgrounds/delete') }}',
            csrfToken: '{{ csrf_token() }}',
        };

        const APS_BACKGROUNDS = @json(config('ai-photoshoot.backgrounds', []));

        document.addEventListener('alpine:init', () => {
            Alpine.data('replaceBgApp', () => ({
                backgrounds: APS_BACKGROUNDS,
                userBackgrounds: [],
                loadingBackgrounds: false,
                selectedBackgroundType: null, // 'preset' | 'uploaded'
                selectedBackgroundSlug: null,
                selectedBackgroundId: null,
                productImage: null,
                productPreview: null,
                ratio: @json(\App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry::normalizeRatioForActiveModel($settings->ratio ?? null)),
                numImages: {{ (int) ($settings->num_images ?? 1) }},
                dragOver: false,
                generating: false,
                currentRecordId: null,
                pollAttempts: 0,
                pollTimer: null,
                results: [],

                // Modal state (universal Upload/Create modal)
                modalOpen: false,
                modalMode: 'upload', // 'upload' | 'create'
                modalDragOver: false,
                uploadFile: null,
                uploadPreview: null,
                uploadFileName: '',
                uploadForm: { name: '', category: '' },
                createForm: { description: '' },
                uploading: false,
                creating: false,

                init() {
                    // Load the user's uploaded background library so the
                    // "My Backgrounds" section is populated from page load.
                    this.loadUserBackgrounds();
                },

                openUploadModal() {
                    this.modalMode = 'upload';
                    this.modalOpen = true;
                    document.body.style.overflow = 'hidden';
                },

                openCreateModal() {
                    this.modalMode = 'create';
                    this.modalOpen = true;
                    document.body.style.overflow = 'hidden';
                },

                closeModal() {
                    this.modalOpen = false;
                    document.body.style.overflow = '';
                    this.resetUpload();
                },

                resetUpload() {
                    this.uploadFile = null;
                    this.uploadPreview = null;
                    this.uploadFileName = '';
                    this.uploadForm = { name: '', category: '' };
                    if (this.$refs.modalFileInput) {
                        this.$refs.modalFileInput.value = '';
                    }
                },

                handleModalFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) this.processModalFile(file);
                },

                handleModalFileDrop(event) {
                    this.modalDragOver = false;
                    const file = event.dataTransfer.files[0];
                    if (file) this.processModalFile(file);
                },

                processModalFile(file) {
                    if (!file.type.startsWith('image/')) {
                        window.toastr?.error('{{ __('Please upload an image file') }}');
                        return;
                    }
                    if (file.size > APS_MAX_UPLOAD_MB * 1024 * 1024) {
                        window.toastr?.error('{{ __('File size must be less than :size MB', ['size' => $apsMaxUploadMb]) }}');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.uploadFile = file;
                        this.uploadFileName = file.name;
                        this.uploadPreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                },

                async submitUpload() {
                    if (!this.uploadFile) {
                        window.toastr?.warning('{{ __('Please choose a background image first.') }}');
                        return;
                    }
                    if (this.uploading) return;

                    this.uploading = true;

                    const formData = new FormData();
                    formData.append('background_image', this.uploadFile);
                    if (this.uploadForm.name) formData.append('name', this.uploadForm.name);
                    if (this.uploadForm.category) formData.append('category', this.uploadForm.category);
                    formData.append('_token', APS_BG_ROUTES.csrfToken);

                    try {
                        const response = await fetch(APS_BG_ROUTES.backgroundsUpload, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || '{{ __('Upload failed') }}');
                        }
                        if (data.background) {
                            this.userBackgrounds.unshift(data.background);
                            // Auto-select the newly uploaded background
                            this.selectUserBackground(data.background);
                        }
                        window.toastr?.success(data.message || '{{ __('Background uploaded successfully') }}');
                        this.closeModal();
                    } catch (error) {
                        console.error('Upload error:', error);
                        window.toastr?.error(error.message || '{{ __('Failed to upload background') }}');
                    } finally {
                        this.uploading = false;
                    }
                },

                async submitCreate() {
                    const description = (this.createForm.description || '').trim();
                    if (description.length < 10) {
                        window.toastr?.error('{{ __('Please provide a description (at least 10 characters).') }}');
                        return;
                    }
                    if (this.creating) return;

                    this.creating = true;

                    const formData = new FormData();
                    formData.append('background_description', description);
                    formData.append('_token', APS_BG_ROUTES.csrfToken);

                    try {
                        const response = await fetch(APS_BG_ROUTES.backgroundsCreate, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || '{{ __('Background generation failed.') }}');
                        }

                        if (data.background) {
                            this.userBackgrounds = [data.background, ...this.userBackgrounds];
                            window.toastr?.success(data.message || '{{ __('Background generation started') }}');
                            this.pollBackgroundStatus(data.background.id);
                        }

                        this.createForm = { description: '' };
                        this.closeModal();
                    } catch (error) {
                        console.error('Create error:', error);
                        window.toastr?.error(error.message || '{{ __('Failed to create background') }}');
                    } finally {
                        this.creating = false;
                    }
                },

                pollBackgroundStatus(id) {
                    if (!id) return;
                    if (!this._bgPolling) this._bgPolling = {};
                    if (this._bgPolling[id]) return;
                    this._bgPolling[id] = true;

                    const url = `${APS_BG_ROUTES.backgroundsStatusBase}/${id}`;
                    let resolved = false;

                    const finish = (interval) => {
                        resolved = true;
                        clearInterval(interval);
                        delete this._bgPolling[id];
                    };

                    const interval = setInterval(async () => {
                        if (resolved) return;
                        try {
                            const response = await fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });
                            const data = await response.json();
                            if (resolved) return;

                            const status = (data.status || '').toLowerCase();

                            if (status === 'completed' && data.background) {
                                finish(interval);
                                this.userBackgrounds = this.userBackgrounds.map(bg => bg.id === id ? data.background : bg);
                                window.toastr?.success('{{ __('Background generated successfully!') }}');
                            } else if (status === 'failed') {
                                finish(interval);
                                this.userBackgrounds = this.userBackgrounds.filter(bg => bg.id !== id);
                                window.toastr?.error(data.message || '{{ __('Background generation failed') }}');
                            }
                        } catch (error) {
                            if (resolved) return;
                            finish(interval);
                            console.error('Background status check error:', error);
                        }
                    }, 3000);

                    setTimeout(() => {
                        if (resolved) return;
                        finish(interval);
                    }, 360000);
                },

                hasBackgroundSelected() {
                    return this.selectedBackgroundType !== null
                        && (this.selectedBackgroundSlug || this.selectedBackgroundId);
                },

                selectPresetBackground(slug) {
                    if (this.selectedBackgroundType === 'preset' && this.selectedBackgroundSlug === slug) {
                        this.selectedBackgroundType = null;
                        this.selectedBackgroundSlug = null;
                        return;
                    }
                    this.selectedBackgroundType = 'preset';
                    this.selectedBackgroundSlug = slug;
                    this.selectedBackgroundId = null;
                },

                selectUserBackground(bg) {
                    if (this.selectedBackgroundType === 'uploaded' && this.selectedBackgroundId === bg.id) {
                        this.selectedBackgroundType = null;
                        this.selectedBackgroundId = null;
                        return;
                    }
                    this.selectedBackgroundType = 'uploaded';
                    this.selectedBackgroundId = bg.id;
                    this.selectedBackgroundSlug = null;
                },

                handleProductSelect(event) {
                    const file = event.target.files[0];
                    if (file) this.processProductFile(file);
                },

                handleProductDrop(event) {
                    this.dragOver = false;
                    const file = event.dataTransfer.files[0];
                    if (file) this.processProductFile(file);
                },

                processProductFile(file) {
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

                async loadUserBackgrounds() {
                    this.loadingBackgrounds = true;
                    try {
                        const response = await fetch(APS_BG_ROUTES.backgroundsLoad, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.userBackgrounds = data.backgrounds || [];
                        }
                    } catch (error) {
                        console.error('Failed to load backgrounds:', error);
                    } finally {
                        this.loadingBackgrounds = false;
                    }
                },

                async deleteUserBackground(id) {
                    if (!confirm('{{ __('Delete this background?') }}')) return;

                    try {
                        const response = await fetch(`${APS_BG_ROUTES.backgroundsDeleteBase}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': APS_BG_ROUTES.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.userBackgrounds = this.userBackgrounds.filter(bg => bg.id !== id);
                            if (this.selectedBackgroundType === 'uploaded' && this.selectedBackgroundId === id) {
                                this.selectedBackgroundType = null;
                                this.selectedBackgroundId = null;
                            }
                        }
                    } catch (error) {
                        console.error('Delete error:', error);
                    }
                },

                resetResults() {
                    this.results = [];
                },

                async submit() {
                    if (!this.productImage) {
                        window.toastr?.warning('{{ __('Please upload a product image') }}');
                        return;
                    }
                    if (!this.hasBackgroundSelected()) {
                        window.toastr?.warning('{{ __('Please pick or upload a background') }}');
                        return;
                    }

                    this.generating = true;
                    this.results = [];

                    const formData = new FormData();
                    formData.append('product_image', this.productImage);
                    formData.append('ratio', this.ratio);
                    formData.append('num_images', this.numImages);
                    formData.append('_token', APS_BG_ROUTES.csrfToken);

                    if (this.selectedBackgroundType === 'preset') {
                        formData.append('background_id', this.selectedBackgroundSlug);
                    } else if (this.selectedBackgroundType === 'uploaded') {
                        formData.append('background_id', `user-${this.selectedBackgroundId}`);
                    }

                    try {
                        const response = await fetch(APS_BG_ROUTES.generate, {
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
                        const response = await fetch(`${APS_BG_ROUTES.checkStatus}/${this.currentRecordId}`, {
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
                    window.location.href = APS_BG_ROUTES.editImage;
                },

                videoResult(result) {
                    sessionStorage.setItem('apsCreateVideoData', JSON.stringify({
                        url: result.image_url,
                        id: result.id,
                        fileName: `image_${result.id}.jpg`
                    }));
                    window.location.href = APS_BG_ROUTES.createVideo + '/' + result.id;
                },
            }));
        });
    </script>
@endpush
