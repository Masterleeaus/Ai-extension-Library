@pushOnce('script')
    <script>
        window.__csAiTemplatePlugin = {
            data: {
                generatingTemplate: false,
                generateAiReference: false,
                generateAiReferenceAspectRatio: localStorage.getItem('cs-aspect-ratio') || '1:1',
                lastGeneratedReferenceImage: null,
            },
            methods: {
                async generateTemplate(event) {
                    const formData = new FormData(event.target);
                    const description = formData.get('template_description')?.trim();

                    if (!description) {
                        toastr.error(magicai_localize?.please_fill_the_fields || 'Please enter a description');
                        return;
                    }

                    this.generatingTemplate = true;

                    this.switchView('editor');

                    this.resetCanvas();

                    if (this.$refs.templateDescription) {
                        this.$refs.templateDescription.disabled = true
                    }

                    try {
                        const res = await fetch('/dashboard/user/creative-suite/ai/generate-template', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                            }
                        });

                        const submitData = await res.json();

                        if (submitData.status === 'error') {
                            toastr.error(submitData.message);
                            return;
                        }

                        if (submitData.task_id) {
                            await this.pollTemplateGeneration(submitData.task_id);
                        } else if (submitData.data?.nodes && submitData.data?.stage) {
                            this.handleTemplateResult(submitData);
                        } else {
                            toastr.error('Failed to generate template. Please try again.');
                        }
                    } catch (error) {
                        console.error('Template generation error:', error);
                        toastr.error('Failed to generate template. Please try again.');
                    } finally {
                        this.generatingTemplate = false;
                    }
                },

                async resumePendingTemplateGeneration() {
                    const taskId = localStorage.getItem('cs-pending-template-task');

                    if (!taskId) return;

                    this.generatingTemplate = true;

                    try {
                        await this.pollTemplateGeneration(taskId);
                    } finally {
                        this.generatingTemplate = false;
                    }
                },

                async pollTemplateGeneration(taskId) {
                    localStorage.setItem('cs-pending-template-task', taskId);

                    const maxAttempts = 180;
                    let attempts = 0;

                    while (attempts < maxAttempts) {
                        await new Promise(r => setTimeout(r, 5000));
                        attempts++;

                        try {
                            const res = await fetch(`/dashboard/user/creative-suite/ai/generate-template/${taskId}/status`, {
                                headers: {
                                    'Accept': 'application/json'
                                },
                            });

                            const data = await res.json();

                            if (data.status === 'processing') {
                                if (data.reference_image && !this.lastGeneratedReferenceImage) {
                                    this.lastGeneratedReferenceImage = data.reference_image;
                                    toastr.info('AI Reference image generated. The template is still being processed...', {
                                        timeOut: 3000
                                    });
                                }
                                continue;
                            }

                            if (data.status === 'error') {
                                toastr.error(data.message || 'Template generation failed.');
                                localStorage.removeItem('cs-pending-template-task');
                                if (this.$refs.templateDescription) {
                                    this.$refs.templateDescription.disabled = false
                                }
                                return;
                            }

                            if (data.status === 'success') {
                                localStorage.removeItem('cs-pending-template-task');
                                this.handleTemplateResult(data);
                                if (this.$refs.templateDescription) {
                                    this.$refs.templateDescription.disabled = false
                                }
                                return;
                            }
                        } catch (e) {
                            console.error('Polling error:', e);
                        }
                    }

                    localStorage.removeItem('cs-pending-template-task');
                    toastr.error('Template generation timed out. Please try again.');
                },

                async handleTemplateResult(data) {
                    if (data.data?.nodes && data.data?.stage) {
                        this.importData({
                            data: data.data
                        });
                        toastr.success(data.message || 'Template generated successfully');

                        if (data.reference_image) {
                            this.lastGeneratedReferenceImage = data.reference_image;
                            this.showReferenceFrame = true;
                        }

                        if (data.description) {
                            this.currentDocName = data.description;
                        }

                        if (data.image_tasks?.length) {
                            await this.pollTemplatePlaceholderImages(data.image_tasks);
                        }

                        // Wait for all node fill-pattern images to finish loading before saving,
                        // otherwise the preview thumbnail renders before images are ready.
                        await this.waitForNodeImagesLoaded();

                        this.saveDocument();
                    }
                },

                waitForNodeImagesLoaded() {
                    // fillSource is a custom Konva attr — read via getAttr()
                    const nodesWithImages = this.nodes.filter(n => n.getAttr('fillSource'));

                    const promises = nodesWithImages.map(node => {
                        return new Promise(resolve => {
                            let attempts = 0;
                            const check = () => {
                                const img = node.fillPatternImage();
                                if ((img && img.complete && img.naturalWidth > 0) || attempts++ > 80) {
                                    resolve();
                                } else {
                                    setTimeout(check, 250);
                                }
                            };
                            check();
                        });
                    });

                    return Promise.all(promises);
                },

                async pollTemplatePlaceholderImages(imageTasks) {
                    const promises = imageTasks.map(task => {
                        const node = this.nodes.find(n => n.id() === task.node_id);

                        if (!node) return Promise.resolve();

                        return this.pollSinglePlaceholderImage(task.task_id, node);
                    });

                    await Promise.allSettled(promises);
                },

                async pollSinglePlaceholderImage(taskId, node) {
                    for (let i = 0; i < 90; i++) {
                        await new Promise(r => setTimeout(r, 3000));

                        try {
                            const res = await fetch(`/dashboard/user/creative-suite/ai/editor/${taskId}/status`, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });

                            const data = await res.json();

                            if (data.data?.status === 'COMPLETED' && data.data?.output) {
                                await this.setNodeFillPattern({
                                    node,
                                    url: data.data.output,
                                    setSize: false
                                });
                                this.addToHistory();
                                return;
                            }

                            if (data.data?.status === 'FAILED') {
                                console.warn('Placeholder image failed for node', node.id());
                                return;
                            }
                        } catch (error) {
                            console.error('Placeholder image poll error:', error);
                        }
                    }
                },
            },
            init(component) {
                component.resumePendingTemplateGeneration();
                component.$watch('generateAiReferenceAspectRatio', val => localStorage.setItem('cs-aspect-ratio', val));
            }
        };
    </script>
@endpushOnce
