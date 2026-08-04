/**
 * Creative Suite Annotations — registers itself as a global plugin
 * (window.__csAnnotationsPlugin) which the host Creative Suite Alpine
 * component reads at runtime. This file is loaded by a Blade partial only
 * when the extension is installed, mirroring the CreativeSuiteAITemplate
 * plugin pattern (window.__csAiTemplatePlugin). The host therefore has no
 * build-time dependency on this extension.
 *
 * Tools:
 *   - comment: each click drops a numbered pin with its own per-pin prompt
 *     entered through an inline tooltip. On submit, all pins + prompts are
 *     burned into a copy of the source image as numbered markers and sent
 *     in a single AI request. The model is asked to apply each prompt at
 *     its corresponding marker and remove the markers from the output.
 *   - brush: multiple strokes accumulate into one annotation with a single
 *     shared prompt entered in the floating popup.
 *   - rect / oval / lasso: single-shape annotations with a single prompt.
 *   - replace-text: detects text blocks via a vision model and lets the
 *     user edit each detected block in place.
 */

const ANNOTATION_LAYER_NAME = 'lqd-cs-annotations-layer';
const ANNOTATION_NODE_NAME = 'lqd-cs-annotation';
const MULTI_MARK_TOOLS = [ 'comment', 'brush', 'replace-text' ];
// Tools whose marks are individually-clickable pins (vs. a continuous shape).
const PIN_TOOLS = [ 'comment', 'replace-text' ];

window.__csAnnotationsPlugin = {
	data: {
		annotationMode: false,
		annotationTool: null,
		annotationColor: '#ff3366',
		annotationBrushSize: 20,
		annotations: [],
		activeAnnotationId: null,
		pendingAnnotationId: null,
		activePinId: null,
		annotationPopup: null,
		annotationsLayer: null,
		annotationTargetNode: null,
		annotationSubmitting: false,
		annotationAnalyzing: false,
		_annotationDrawState: null,
		_annotationListenersBound: false,
		_lastTouchStartAt: 0,
		_annotationHistory: [],
		_annotationFutureStack: [],
		maxAnnotationHistorySize: 50,

		canUndoAnnotation() { return this._annotationHistory.length > 0; },
		canRedoAnnotation() { return this._annotationFutureStack.length > 0; },

		activeAnnotation() {
			return this.annotations.find(a => a.id === this.activeAnnotationId) ?? null;
		},

		pendingAnnotation() {
			return this.annotations.find(a => a.id === this.pendingAnnotationId) ?? null;
		},

		hasPendingAnnotation() {
			return !!this.pendingAnnotation()?.marks.length;
		},

		canSubmitPending() {
			const annotation = this.pendingAnnotation();
			if (!annotation || !annotation.marks.length) return false;
			if (annotation.tool === 'comment') {
				return annotation.marks.some(m => m.prompt?.trim());
			}
			if (annotation.tool === 'replace-text') {
				return annotation.marks.some(m => this._isReplaceTextEdited(m));
			}
			return true;
		},

		canSubmitReplaceText() {
			return this.canSubmitPending();
		},

		/**
		 * A replace-text pin counts as "edited" when the textarea value differs
		 * from the originally-detected text AND is non-empty. Manual pins start
		 * with originalText === '' so any non-empty prompt is an edit.
		 */
		_isReplaceTextEdited(mark) {
			const cur = (mark.prompt ?? '').trim();
			const orig = (mark.originalText ?? '').trim();
			return cur !== '' && cur !== orig;
		},

		activePin() {
			const id = this.activePinId;
			if (!id) return null;
			for (const annotation of this.annotations) {
				const mark = annotation.marks.find(m => m.id === id);
				if (mark) return { annotation, mark };
			}
			return null;
		},

		initAnnotations() {
			if (!this.stage || this._annotationListenersBound) return;

			this.annotationsLayer = new Konva.Layer({ name: ANNOTATION_LAYER_NAME, listening: true });
			this.stage.add(this.annotationsLayer);

			// Konva's documented free-drawing pattern: listen to mousedown +
			// touchstart, dispatch the same handler. iOS dispatches a synthesized
			// mousedown ~300-700ms after every touchstart for compatibility, so we
			// stamp the touchstart time and skip mousedowns within that window.
			this.stage.on('mousedown.csa touchstart.csa', e => this._csaPointerDown(e));
			this.stage.on('mousemove.csa touchmove.csa', e => this._csaPointerMove(e));
			this.stage.on('mouseup.csa touchend.csa', e => this._csaPointerUp(e));
			// Window-level safety net: if the user lifts their finger / mouse
			// outside the canvas the stage event won't fire and the draw state
			// would stay stuck. These also bind to touchcancel for completeness.
			this._csaWindowUp = () => this._csaPointerUp();
			window.addEventListener('mouseup', this._csaWindowUp);
			window.addEventListener('touchend', this._csaWindowUp);
			window.addEventListener('touchcancel', this._csaWindowUp);

			this.$watch('annotationTool', (tool, prevTool) => {
				this._csaPointerUp();
				// Switching tool wipes any in-progress annotations (and their history).
				if (prevTool !== tool) {
					this._discardAllAnnotations();
					this._clearAnnotationHistory();
				}
				this.activePinId = null;
				this.annotationPopup = null;
				this._updateCursor();
			});
			this.$watch('annotationMode', mode => {
				if (!mode) this._teardownAnnotations();
				this._updateCursor();
			});
			this.$watch('annotationColor', color => {
				this.annotations.forEach(annotation => {
					annotation.marks.forEach(mark => this._applyColorToMark(mark, annotation.tool, color));
				});
				this.annotationsLayer?.batchDraw();
			});

			this._annotationListenersBound = true;
		},

		enterAnnotationMode() {
			const target = this.selectedNodes.find(n => n.getClassName() === 'Image')
			?? this.selectedNodes[0];

			if (!target) {
				toastr.warn(window?.magicai_localize?.select_image_first ?? 'Select an image first.');
				return;
			}

			this.annotationTargetNode = target;
			this._annotationDraggableSnapshot = this.nodes.map(node => ({
				node,
				draggable: node.draggable(),
			}));
			this.nodes.forEach(node => node.draggable(false));

			this.annotationMode = true;
			this.annotationTool = 'rect';

			this.selectionTransformer?.nodes([]);
			this.selectionTransformer?.hide();
			this.selectionTransformer?.getLayer()?.batchDraw();
		},

		exitAnnotationMode() {
			this.annotationMode = false;
		},

		_teardownAnnotations() {
			this.annotationTool = null;
			this.activeAnnotationId = null;
			this.pendingAnnotationId = null;
			this.activePinId = null;
			this.annotationPopup = null;
			this._annotationDrawState = null;
			this._clearAnnotationHistory();

			this._annotationDraggableSnapshot?.forEach(({ node, draggable }) => {
				if (node && !node.isDestroyed?.()) {
					node.draggable(draggable);
				}
			});
			this._annotationDraggableSnapshot = null;
			this.annotationTargetNode = null;

			this.annotations.forEach(a => a.marks.forEach(m => m.node.destroy()));
			this.annotations = [];
			this.annotationsLayer?.batchDraw();

			this.selectionTransformer?.show();
			this.selectionTransformer?.getLayer()?.batchDraw();
		},

		_updateCursor() {
			if (!this.container) return;

			if (this.annotationMode && this.annotationTool) {
				this.container.style.cursor = this.annotationTool === 'comment' ? 'pointer' : 'crosshair';
			} else if (!this.annotationMode) {
				this.container.style.cursor = '';
			}
		},

		_fillFor(color) {
			return color + '40';
		},

		_applyColorToMark(mark, tool, color) {
			if (tool === 'comment') {
				mark.node.findOne('Circle')?.fill(color);
				return;
			}
			mark.node.stroke?.(color);
			if (tool !== 'brush') {
				mark.node.fill?.(this._fillFor(color));
			}
		},

		_csaPointerDown(event) {
			if (!this.annotationMode || !this.annotationTool) return;
			if (this.annotationSubmitting) return;
			if (this.isPanning || this.isSpacePressed) return;

			const evt = event?.evt;
			const isTouch = event.type === 'touchstart';
			const now = Date.now();

			// iOS dispatches a synthesized mousedown ~300-700ms after every real
			// touchstart for compatibility. Drop those — otherwise a single tap
			// runs this handler twice and we'd create a duplicate mark (extra
			// pin, ghost rectangle, etc.).
			if (isTouch) {
				this._lastTouchStartAt = now;
			} else if (this._lastTouchStartAt && now - this._lastTouchStartAt < 1500) {
				return;
			}

			// Multi-touch on mobile (pinch / two-finger pan) — the editor's own
			// pinch-zoom handler owns the gesture, so abort whatever we were
			// drawing and don't start a new mark.
			const touchCount = evt?.touches?.length ?? 0;
			if (touchCount >= 2) {
				this._abortDrawing();
				return;
			}

			// Defensive: if a draw is already in flight (e.g., touchstart fired
			// then a stray duplicate down arrived before touchend), ignore it.
			if (this._annotationDrawState) return;

			// Ignore non-primary mouse buttons.
			if (!isTouch && typeof evt?.button === 'number' && evt.button !== 0) return;

			// A visible popup blocks new pointers (pin tooltip, rect/oval/lasso
			// prompt) — except for brush, which is meant to keep painting while
			// its prompt popup stays on screen.
			if (this.annotationPopup && this.annotationTool !== 'brush') return;

			const target = event.target;
			// For pin tools (comment, replace-text), defer to the existing pin's
			// click handler when the pointer lands on an existing pin so it
			// reopens that pin's tooltip. For brush/rect/oval/lasso we always
			// want to start a fresh stroke or shape, even on top of existing
			// marks (otherwise brush can't continue across painted areas).
			if (
				PIN_TOOLS.includes(this.annotationTool)
			&& target?.getLayer
			&& target.getLayer() === this.annotationsLayer
			) {
				return;
			}

			event.cancelBubble = true;

			// When brush starts a new stroke while the prompt popup is already
			// open, drop any lingering input focus first. Otherwise on mobile
			// the on-screen keyboard stays up, eats subsequent touches, and
			// makes additional strokes look broken.
			if (this.annotationTool === 'brush' && this.annotationPopup) {
				const active = document.activeElement;
				if (active && typeof active.blur === 'function') {
					active.blur();
				}
			}

			const pos = this.stage.getPointerPosition();
			if (!pos) return;

			const tool = this.annotationTool;
			const isMulti = MULTI_MARK_TOOLS.includes(tool);
			const existingPending = isMulti ? this.pendingAnnotation() : null;
			const annotation = existingPending ?? this._createAnnotation(tool);
			const isNewAnnotation = !existingPending;

			const node = this._createKonvaShape(tool, pos);
			if (!node) return;

			const mark = {
				id: `mark-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
				node,
				prompt: '',
				// Manual replace-text pins start with no detected text — the
				// user types the replacement in the empty textarea.
				originalText: '',
			};

			this._attachMarkClickHandler(mark, annotation);

			annotation.marks.push(mark);
			this.annotationsLayer.add(node);

			if (PIN_TOOLS.includes(tool)) {
				this._renumberPins(annotation);
			}

			this._annotationDrawState = { annotation, mark, tool, isNewAnnotation, startX: pos.x, startY: pos.y };

			if (isMulti) {
				this.pendingAnnotationId = annotation.id;
			}

			if (PIN_TOOLS.includes(tool)) {
				this._annotationDrawState = null;
				this._recordAddMark(annotation, mark, isNewAnnotation);
				this._scheduleActivatePin(mark);
			}

			this.annotationsLayer.batchDraw();
		},

		_scheduleActivatePin(mark) {
		// Wait for the pointer release (so we're past the hold), then schedule
		// the activation as a macrotask. setTimeout(0) inside the up handler
		// runs *after* the synthesized click event finishes propagating, so
		// Alpine's @click.outside on the popup won't fire on the same click
		// that created or selected the pin.
			const onRelease = () => {
				setTimeout(() => this._activatePin(mark), 0);
			};
			window.addEventListener('mouseup', onRelease, { once: true });
			window.addEventListener('touchend', onRelease, { once: true });
		},

		_createAnnotation(tool) {
			const id = `csa-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
			const annotation = { id, tool, marks: [], prompt: '' };
			this.annotations.push(annotation);
			return annotation;
		},

		_createKonvaShape(tool, pos) {
			const baseAttrs = {
				name: ANNOTATION_NODE_NAME,
				stroke: this.annotationColor,
				strokeWidth: 2,
				fill: this._fillFor(this.annotationColor),
				listening: true,
			};

			switch (tool) {
				case 'comment':
				case 'replace-text':
					return this._createPinNode(pos);
				case 'rect':
					return new Konva.Rect({ ...baseAttrs, x: pos.x, y: pos.y, width: 0, height: 0 });
				case 'oval':
					return new Konva.Ellipse({ ...baseAttrs, x: pos.x, y: pos.y, radiusX: 0, radiusY: 0 });
				case 'lasso':
					return new Konva.Line({ ...baseAttrs, points: [ pos.x, pos.y ], closed: true });
				case 'brush':
					return new Konva.Line({
						...baseAttrs,
						points: [ pos.x, pos.y ],
						closed: false,
						lineCap: 'round',
						lineJoin: 'round',
						strokeWidth: this.annotationBrushSize,
						fill: undefined,
					});
			}
			return null;
		},

		_createPinNode(pos) {
			const group = new Konva.Group({
				x: pos.x,
				y: pos.y,
				name: ANNOTATION_NODE_NAME,
				listening: true,
			});

			const circle = new Konva.Circle({
				x: 0,
				y: 0,
				radius: 20,
				fill: this.annotationColor,
				stroke: '#ffffff',
				strokeWidth: 2.5,
				shadowColor: 'black',
				shadowBlur: 5,
				shadowOpacity: 0.3,
			});

			const label = new Konva.Text({
				x: -18,
				y: -10,
				width: 36,
				text: '0',
				fontSize: 18,
				fontStyle: 'bold',
				fill: '#ffffff',
				align: 'center',
				verticalAlign: 'middle',
				listening: false,
			});

			group.add(circle);
			group.add(label);

			return group;
		},

		_attachMarkClickHandler(mark, annotation) {
			mark.node.on('click tap', evt => {
				if (!this.annotationMode) return;
				evt.cancelBubble = true;

				if (PIN_TOOLS.includes(annotation.tool)) {
				// Defer past the DOM click event so @click.outside doesn't close
				// the popup we're about to open.
					setTimeout(() => this._activatePin(mark), 0);
					return;
				}

				if (MULTI_MARK_TOOLS.includes(annotation.tool) && annotation.id !== this.pendingAnnotationId) {
					this._activateAnnotation(annotation.id);
				}
			});
		},

		_activatePin(mark) {
			this.activePinId = mark.id;
			this._positionPopupAtPin(mark);
			this.$nextTick(() => this._focusPopupTextarea());
		},

		/**
	 * The popup uses a Blade `<x-forms.input>` wrapper, so the user-passed
	 * x-ref may bind to the container instead of the actual `<textarea>`.
	 * Query the textarea directly to make focus reliable.
	 */
		_focusPopupTextarea() {
			const popup = document.querySelector('.lqd-cs-annotations-popup');
			const textarea = popup?.querySelector('textarea');
			if (!textarea) return;
			textarea.focus();
			// Place the caret at the end if the textarea already has content.
			const len = textarea.value?.length ?? 0;
			try {
				textarea.setSelectionRange(len, len);
			} catch {}
		},

		_positionPopupAtPin(mark) {
			const rect = mark.node.getClientRect();
			const contentRect = this.konvajsContent?.getBoundingClientRect();
			if (!contentRect) return;

			const zoom = this.zoomLevel / 100;
			const anchorRight = contentRect.left + (rect.x + rect.width) * zoom + 8;
			const anchorLeft = contentRect.left + rect.x * zoom;
			const anchorTop = contentRect.top + rect.y * zoom;

			const { x, y } = this._clampPopupPosition({ anchorLeft, anchorRight, anchorTop });
			this.annotationPopup = { pinId: mark.id, x, y };
		},

		_renumberPins(annotation) {
			if (!PIN_TOOLS.includes(annotation.tool)) return;
			annotation.marks.forEach((mark, i) => {
				mark.node.findOne('Text')?.text(String(i + 1));
			});
			this.annotationsLayer?.batchDraw();
		},

		_csaPointerMove(event) {
			const drawing = this._annotationDrawState;
			if (!drawing) return;

			// Abort drawing if a second finger lands mid-stroke (pinch starts).
			const touchCount = event?.evt?.touches?.length ?? 0;
			if (touchCount >= 2) {
				this._abortDrawing();
				return;
			}

			const pos = this.stage.getPointerPosition();
			if (!pos) return;

			const node = drawing.mark.node;

			switch (drawing.tool) {
				case 'rect':
					node.x(Math.min(drawing.startX, pos.x));
					node.y(Math.min(drawing.startY, pos.y));
					node.width(Math.abs(pos.x - drawing.startX));
					node.height(Math.abs(pos.y - drawing.startY));
					break;
				case 'oval': {
					const w = Math.abs(pos.x - drawing.startX);
					const h = Math.abs(pos.y - drawing.startY);
					node.x((drawing.startX + pos.x) / 2);
					node.y((drawing.startY + pos.y) / 2);
					node.radiusX(w / 2);
					node.radiusY(h / 2);
					break;
				}
				case 'lasso':
				case 'brush':
					node.points([ ...node.points(), pos.x, pos.y ]);
					break;
			}

			this.annotationsLayer.batchDraw();
		},

		_csaPointerUp() {
			const drawing = this._annotationDrawState;
			this._annotationDrawState = null;

			if (!drawing) return;

			const { annotation, mark, tool, isNewAnnotation } = drawing;
			const node = mark.node;
			const rect = node.getClientRect();

			if (rect.width < 4 && rect.height < 4) {
				node.destroy();
				annotation.marks = annotation.marks.filter(m => m.id !== mark.id);
				if (!annotation.marks.length) {
					this.annotations = this.annotations.filter(a => a.id !== annotation.id);
					if (annotation.id === this.pendingAnnotationId) {
						this.pendingAnnotationId = null;
					}
				}
				this.annotationsLayer?.batchDraw();
				return;
			}

			this._recordAddMark(annotation, mark, isNewAnnotation);

			if (MULTI_MARK_TOOLS.includes(tool)) {
			// Brush has no per-mark UI like comment pins do, so open the
			// shared prompt popup automatically on the first stroke. The
			// popup stays anchored at the bbox of that first stroke and the
			// user can keep painting while it's open.
				if (tool === 'brush' && this.activeAnnotationId !== annotation.id) {
					this._completeAnnotation(annotation);
				}
				return;
			}

			this._completeAnnotation(annotation);
		},

		_abortDrawing() {
			const drawing = this._annotationDrawState;
			this._annotationDrawState = null;

			if (!drawing) return;

			const { annotation, mark } = drawing;
			mark.node.destroy();
			annotation.marks = annotation.marks.filter(m => m.id !== mark.id);
			if (!annotation.marks.length) {
				this.annotations = this.annotations.filter(a => a.id !== annotation.id);
				if (annotation.id === this.pendingAnnotationId) {
					this.pendingAnnotationId = null;
				}
			}
			this.annotationsLayer?.batchDraw();
		},

		completePendingAnnotation() {
			const annotation = this.pendingAnnotation();
			if (!annotation || !annotation.marks.length) return;

			this.pendingAnnotationId = null;
			this.activePinId = null;
			this.activeAnnotationId = annotation.id;

			if (PIN_TOOLS.includes(annotation.tool)) {
				this.submitActiveAnnotation();
				return;
			}

			this._completeAnnotation(annotation);
		},

		_discardPendingAnnotation() {
			const annotation = this.pendingAnnotation();
			if (!annotation) return;

			annotation.marks.forEach(mark => mark.node.destroy());
			this.annotations = this.annotations.filter(a => a.id !== annotation.id);
			this.pendingAnnotationId = null;
			this.annotationsLayer?.batchDraw();
		},

		_discardAllAnnotations() {
			this.annotations.forEach(a => a.marks.forEach(m => m.node.destroy()));
			this.annotations = [];
			this.pendingAnnotationId = null;
			this.activeAnnotationId = null;
			this.activePinId = null;
			this.annotationPopup = null;
			this.annotationsLayer?.batchDraw();
		},

		// --- Undo / redo ---

		_pushHistoryOp(op) {
			this._annotationHistory.push(op);
			if (this._annotationHistory.length > this.maxAnnotationHistorySize) {
				const dropped = this._annotationHistory.shift();
				this._destroyOpResources(dropped);
			}
			// Any new action invalidates the redo branch.
			this._annotationFutureStack.forEach(o => this._destroyOpResources(o));
			this._annotationFutureStack = [];
		},

		_clearAnnotationHistory() {
			[ ...this._annotationHistory, ...this._annotationFutureStack ].forEach(op => this._destroyOpResources(op));
			this._annotationHistory = [];
			this._annotationFutureStack = [];
		},

		/**
	 * Hard-destroy any Konva nodes that are referenced by an op but no
	 * longer attached to the canvas — these would leak otherwise when an
	 * op falls off the bottom of the history.
	 */
		_destroyOpResources(op) {
			op?.disposable?.forEach(node => {
				if (node && !node.getLayer()) {
					node.destroy?.();
				}
			});
		},

		undoAnnotation() {
			const op = this._annotationHistory.pop();
			if (!op) return;
			op.undo();
			this._annotationFutureStack.push(op);
		},

		redoAnnotation() {
			const op = this._annotationFutureStack.pop();
			if (!op) return;
			op.redo();
			this._annotationHistory.push(op);
		},

		_recordAddMark(annotation, mark, isNewAnnotation) {
			const op = {
				disposable: [ mark.node ],
				redo: () => {
					if (isNewAnnotation && !this.annotations.find(a => a.id === annotation.id)) {
						this.annotations.push(annotation);
					}
					if (!annotation.marks.some(m => m.id === mark.id)) {
						annotation.marks.push(mark);
					}
					if (!mark.node.getLayer()) {
						this.annotationsLayer.add(mark.node);
					}
					if (PIN_TOOLS.includes(annotation.tool)) this._renumberPins(annotation);
					if (MULTI_MARK_TOOLS.includes(annotation.tool)) {
						this.pendingAnnotationId = annotation.id;
					}
					this.annotationsLayer?.batchDraw();
				},
				undo: () => {
				// Close any popup tied to this annotation/mark to avoid stale UI.
					if (this.activePinId === mark.id) {
						this.activePinId = null;
						this.annotationPopup = null;
					}
					if (this.activeAnnotationId === annotation.id) {
						this.activeAnnotationId = null;
						this.annotationPopup = null;
					}

					annotation.marks = annotation.marks.filter(m => m.id !== mark.id);
					mark.node.remove();

					if (!annotation.marks.length) {
						this.annotations = this.annotations.filter(a => a.id !== annotation.id);
						if (this.pendingAnnotationId === annotation.id) this.pendingAnnotationId = null;
					} else if (PIN_TOOLS.includes(annotation.tool)) {
						this._renumberPins(annotation);
					}

					this.annotationsLayer?.batchDraw();
				},
			};
			this._pushHistoryOp(op);
		},

		_recordRemoveMark(annotation, mark) {
		// Capture the mark's position in the array so undo can put it back
		// where it was (otherwise renumbering would shift comment pins).
		// Use ID-based lookup since Alpine reactivity wraps array members in
		// proxies, so identity comparison against the raw mark would fail.
			const originalIndex = annotation.marks.findIndex(m => m.id === mark.id);
			const op = {
				disposable: [ mark.node ],
				redo: () => {
					annotation.marks = annotation.marks.filter(m => m.id !== mark.id);
					mark.node.remove();
					if (!annotation.marks.length) {
						this.annotations = this.annotations.filter(a => a.id !== annotation.id);
						if (this.pendingAnnotationId === annotation.id) this.pendingAnnotationId = null;
					} else if (PIN_TOOLS.includes(annotation.tool)) {
						this._renumberPins(annotation);
					}
					this.activePinId = null;
					this.annotationPopup = null;
					this.annotationsLayer?.batchDraw();
				},
				undo: () => {
					if (!this.annotations.find(a => a.id === annotation.id)) {
						this.annotations.push(annotation);
					}
					if (!annotation.marks.some(m => m.id === mark.id)) {
						const insertAt = Math.min(originalIndex, annotation.marks.length);
						annotation.marks.splice(insertAt, 0, mark);
					}
					if (!mark.node.getLayer()) {
						this.annotationsLayer.add(mark.node);
					}
					if (PIN_TOOLS.includes(annotation.tool)) this._renumberPins(annotation);
					if (MULTI_MARK_TOOLS.includes(annotation.tool)) {
						this.pendingAnnotationId = annotation.id;
					}
					this.annotationsLayer?.batchDraw();
				},
			};
			op.redo();
			this._pushHistoryOp(op);
		},


		_completeAnnotation(annotation) {
			this._activateAnnotation(annotation.id);

			this.$nextTick(() => this._focusPopupTextarea());
		},

		_activateAnnotation(id) {
			const annotation = this.annotations.find(a => a.id === id);
			if (!annotation) return;

			this.activeAnnotationId = id;
			this._positionPopup(annotation);
		},

		_annotationBounds(annotation) {
			if (!annotation.marks.length) {
				return { x: 0, y: 0, width: 0, height: 0 };
			}

			const rects = annotation.marks.map(mark => mark.node.getClientRect({ relativeTo: this.layer }));
			const x = Math.min(...rects.map(r => r.x));
			const y = Math.min(...rects.map(r => r.y));
			const right = Math.max(...rects.map(r => r.x + r.width));
			const bottom = Math.max(...rects.map(r => r.y + r.height));

			return { x, y, width: right - x, height: bottom - y };
		},

		_positionPopup(annotation) {
			const bounds = this._annotationBounds(annotation);
			const contentRect = this.konvajsContent?.getBoundingClientRect();
			if (!contentRect) return;

			const zoom = this.zoomLevel / 100;
			const anchorRight = contentRect.left + (bounds.x + bounds.width) * zoom + 8;
			const anchorLeft = contentRect.left + bounds.x * zoom;
			const anchorTop = contentRect.top + bounds.y * zoom;

			const { x, y } = this._clampPopupPosition({ anchorLeft, anchorRight, anchorTop });
			this.annotationPopup = { annotationId: annotation.id, x, y };
		},

		/**
	 * Decide a popup position that stays inside the viewport. Tries the
	 * preferred right-of-anchor placement first, flips to the left side
	 * when there's no room, then clamps to viewport edges. Used for both
	 * the shared prompt popup and per-pin tooltips.
	 */
		_clampPopupPosition({ anchorLeft, anchorRight, anchorTop }) {
			const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
			const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
			// Approximate popup dimensions — w-72 + a bit for action row + padding.
			const popupWidth = Math.min(288, viewportWidth - 24);
			const popupHeight = 200;
			const margin = 12;

			let x = anchorRight;
			if (x + popupWidth > viewportWidth - margin) {
			// Not enough room on the right — try the left of the anchor.
				x = anchorLeft - popupWidth - 8;
			}
			x = Math.max(margin, Math.min(x, viewportWidth - popupWidth - margin));

			let y = anchorTop;
			y = Math.max(margin, Math.min(y, viewportHeight - popupHeight - margin));

			return { x, y };
		},

		setActivePinPrompt(value) {
			const found = this.activePin();
			if (!found) return;
			found.mark.prompt = value;
		},

		confirmActivePin() {
			this.activePinId = null;
			this.annotationPopup = null;
		},

		removeActivePin() {
			const found = this.activePin();
			if (!found) return;

			this._recordRemoveMark(found.annotation, found.mark);
		},

		setActiveAnnotationPrompt(value) {
			const annotation = this.activeAnnotation();
			if (!annotation) return;

			annotation.prompt = value;
		},

		/**
	 * Hide the popup without destroying the annotation. Used when the
	 * popup gets in the user's way (mostly during brush). Activated state
	 * (`activeAnnotationId`) stays set so `maximizePopup()` can bring it
	 * back at the same anchor.
	 */
		minimizePopup() {
			this.annotationPopup = null;
		},

		maximizePopup() {
			const annotation = this.activeAnnotation();
			if (!annotation) return;

			this._positionPopup(annotation);
			this.$nextTick(() => this._focusPopupTextarea());
		},

		canMaximizePopup() {
			return this.annotationTool === 'brush'
			&& !!this.activeAnnotationId
			&& !this.annotationPopup;
		},

		cancelActiveAnnotation() {
			const annotation = this.activeAnnotation();
			if (annotation) {
				annotation.marks.forEach(mark => mark.node.destroy());
				this.annotations = this.annotations.filter(a => a.id !== annotation.id);
				this.annotationsLayer?.batchDraw();
			}

			this.activeAnnotationId = null;
			this.annotationPopup = null;
			// Cancel removes everything related to this annotation; history would
			// be inconsistent against destroyed nodes, so just reset it.
			this._clearAnnotationHistory();
		},

		async submitActiveAnnotation() {
			const annotation = this.activeAnnotation();
			const target = this.annotationTargetNode;

			if (!annotation || !target) return;
			if (this.annotationSubmitting) return;
			if (!this._isAnnotationReadyForSubmit(annotation)) return;

			const fillSource = target.getAttr('fillSource');
			const sourceImage = target.fillPatternImage();

			if (!fillSource || !sourceImage) {
				toastr.error('No source image attached to this node.');
				return;
			}

			this.annotationSubmitting = true;

			try {
				const naturalImage = await this._loadImage(fillSource);
				const transform = this._stageToImageTransform(target, naturalImage);

				let filledPins;
				if (annotation.tool === 'comment') {
					filledPins = annotation.marks.filter(m => m.prompt?.trim());
				} else if (annotation.tool === 'replace-text') {
					filledPins = annotation.marks.filter(m => this._isReplaceTextEdited(m));
				} else {
					filledPins = annotation.marks;
				}

				if (PIN_TOOLS.includes(annotation.tool) && !filledPins.length) {
					toastr.error(annotation.tool === 'replace-text'
						? 'Edit at least one detected text block before submitting.'
						: 'Add a prompt to at least one pin before submitting.');
					return;
				}

				const imageBlob = await this._buildSourceImageBlob(annotation, naturalImage, transform, filledPins);
				const maskBlob = await this._buildMaskBlob(annotation, naturalImage, transform, filledPins);

				const formData = new FormData();
				formData.append('image_reference', imageBlob, 'source.png');
				formData.append('mask', maskBlob, 'mask.png');
				formData.append('tool', annotation.tool);

				if (annotation.tool === 'comment') {
				// Per-pin prompts are sent raw; the backend wraps them with
				// marker instructions before forwarding to the AI provider.
					formData.append(
						'pin_prompts',
						JSON.stringify(filledPins.map(pin => pin.prompt.trim()))
					);
				} else if (annotation.tool === 'replace-text') {
				// {original_text, new_text} pairs — the backend builds the
				// "Marker N: replace X with Y" prompt server-side.
					formData.append(
						'text_edits',
						JSON.stringify(filledPins.map(pin => ({
							original_text: (pin.originalText ?? '').trim(),
							new_text: pin.prompt.trim(),
						})))
					);
				} else {
					formData.append('prompt', annotation.prompt.trim());
				}

				const response = await this._postAnnotation(formData);

				// Treat both our explicit error envelope and Laravel's
				// 422 validation response shape as a hard stop — otherwise
				// `initial.id` ends up undefined and the poll loop 404s.
				if (response.status === 'error' || response.errors) {
					const validation = response.errors
						? Object.values(response.errors).flat().join('\n')
						: null;
					toastr.error(validation || response.message || 'Failed to submit annotation.');
					return;
				}

				const initial = response.data ?? {};
				if (!initial.id) {
					toastr.error(response.message || 'Failed to submit annotation.');
					return;
				}

				let outputUrl = (initial.status === 'COMPLETED' && initial.output) ? initial.output : null;

				if (!outputUrl) {
					outputUrl = await this._waitForOutput(initial.id);
				}

				await this.setNodeFillPattern({ node: target, url: outputUrl });
				this.layer?.batchDraw();

				annotation.marks.forEach(mark => mark.node.destroy());
				this.annotations = this.annotations.filter(a => a.id !== annotation.id);
				this.activeAnnotationId = null;
				this.annotationPopup = null;
				this.annotationsLayer?.batchDraw();
				this._clearAnnotationHistory();
				toastr.success('Annotation applied.');
			} catch (err) {
				console.error(err);
				toastr.error(err?.message ?? 'Failed to submit annotation.');
			} finally {
				this.annotationSubmitting = false;
			}
		},

		_isAnnotationReadyForSubmit(annotation) {
			if (annotation.tool === 'comment') {
				return annotation.marks.some(m => m.prompt?.trim());
			}
			if (annotation.tool === 'replace-text') {
				return annotation.marks.some(m => this._isReplaceTextEdited(m));
			}
			return !!annotation.prompt?.trim();
		},

		_stageToImageTransform(target, naturalImage) {
			return {
				scaleX: naturalImage.naturalWidth / target.width(),
				scaleY: naturalImage.naturalHeight / target.height(),
				tx: target.x(),
				ty: target.y(),
			};
		},

		_toSourceXY(stageX, stageY, t) {
			return {
				x: (stageX - t.tx) * t.scaleX,
				y: (stageY - t.ty) * t.scaleY,
			};
		},

		/** Inverse of _toSourceXY — natural-image pixel coords back to stage. */
		_sourceToStageXY(sourceX, sourceY, t) {
			return {
				x: (sourceX / t.scaleX) + t.tx,
				y: (sourceY / t.scaleY) + t.ty,
			};
		},

		/**
	 * Full-size source image. For comments we burn numbered red markers into
	 * a copy so per-pin prompts can be tied to specific locations.
	 */
		async _buildSourceImageBlob(annotation, naturalImage, transform, filledPins) {
			const canvas = document.createElement('canvas');
			canvas.width = naturalImage.naturalWidth;
			canvas.height = naturalImage.naturalHeight;
			const ctx = canvas.getContext('2d');
			ctx.drawImage(naturalImage, 0, 0);

			if (PIN_TOOLS.includes(annotation.tool)) {
				const radius = this._markerRadius(naturalImage);
				filledPins.forEach((pin, i) => {
					const stagePos = pin.node.position();
					const { x, y } = this._toSourceXY(stagePos.x, stagePos.y, transform);
					this._drawMarkerOnCanvas(ctx, x, y, i + 1, radius);
				});
			}

			return new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
		},

		/**
	 * Full-size mask. Source image is the base; annotation regions are
	 * punched out to alpha=0. This matches OpenAI's image-edit mask format
	 * (transparent = "edit here").
	 */
		async _buildMaskBlob(annotation, naturalImage, transform, filledPins) {
			const canvas = document.createElement('canvas');
			canvas.width = naturalImage.naturalWidth;
			canvas.height = naturalImage.naturalHeight;
			const ctx = canvas.getContext('2d');
			ctx.drawImage(naturalImage, 0, 0);

			ctx.globalCompositeOperation = 'destination-out';
			ctx.fillStyle = 'rgba(0,0,0,1)';
			ctx.strokeStyle = 'rgba(0,0,0,1)';

			switch (annotation.tool) {
				case 'comment':
				case 'replace-text': {
					const radius = this._maskPinRadius(naturalImage);
					filledPins.forEach(pin => {
						const stagePos = pin.node.position();
						const { x, y } = this._toSourceXY(stagePos.x, stagePos.y, transform);
						ctx.beginPath();
						ctx.arc(x, y, radius, 0, Math.PI * 2);
						ctx.fill();
					});
					break;
				}
				case 'rect': {
					annotation.marks.forEach(mark => {
						const node = mark.node;
						const tl = this._toSourceXY(node.x(), node.y(), transform);
						ctx.fillRect(tl.x, tl.y, node.width() * transform.scaleX, node.height() * transform.scaleY);
					});
					break;
				}
				case 'oval': {
					annotation.marks.forEach(mark => {
						const node = mark.node;
						const c = this._toSourceXY(node.x(), node.y(), transform);
						ctx.beginPath();
						ctx.ellipse(c.x, c.y, node.radiusX() * transform.scaleX, node.radiusY() * transform.scaleY, 0, 0, Math.PI * 2);
						ctx.fill();
					});
					break;
				}
				case 'lasso': {
					annotation.marks.forEach(mark => {
						this._tracePoints(ctx, mark.node.points(), transform);
						ctx.closePath();
						ctx.fill();
					});
					break;
				}
				case 'brush': {
					annotation.marks.forEach(mark => {
						ctx.lineWidth = mark.node.strokeWidth() * Math.max(transform.scaleX, transform.scaleY);
						ctx.lineCap = 'round';
						ctx.lineJoin = 'round';
						this._tracePoints(ctx, mark.node.points(), transform);
						ctx.stroke();
					});
					break;
				}
			}

			ctx.globalCompositeOperation = 'source-over';

			return new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
		},

		_tracePoints(ctx, points, transform) {
			ctx.beginPath();
			for (let i = 0; i < points.length; i += 2) {
				const { x, y } = this._toSourceXY(points[i], points[i + 1], transform);
				if (i === 0) ctx.moveTo(x, y);
				else ctx.lineTo(x, y);
			}
		},

		_markerRadius(naturalImage) {
			return Math.max(18, Math.min(48, Math.floor(Math.min(naturalImage.naturalWidth, naturalImage.naturalHeight) * 0.025)));
		},

		_maskPinRadius(naturalImage) {
		// Roughly 8% of the smaller side, with sensible bounds. Gives the AI
		// enough room around each pin to actually make a change.
			return Math.max(60, Math.min(220, Math.floor(Math.min(naturalImage.naturalWidth, naturalImage.naturalHeight) * 0.08)));
		},

		_drawMarkerOnCanvas(ctx, x, y, num, radius) {
			ctx.save();
			ctx.beginPath();
			ctx.arc(x, y, radius, 0, Math.PI * 2);
			ctx.fillStyle = '#ff2424';
			ctx.fill();
			ctx.lineWidth = Math.max(2, radius / 8);
			ctx.strokeStyle = '#ffffff';
			ctx.stroke();

			ctx.fillStyle = '#ffffff';
			ctx.font = `bold ${Math.floor(radius * 1.1)}px sans-serif`;
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillText(String(num), x, y);
			ctx.restore();
		},

		_loadImage(src) {
			return new Promise((resolve, reject) => {
				const img = new Image();
				img.crossOrigin = 'anonymous';
				img.onload = () => resolve(img);
				img.onerror = reject;
				img.src = src;
			});
		},

		async _postAnnotation(formData) {
			const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
			const res = await fetch('/dashboard/user/creative-suite-annotations/edit', {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'X-CSRF-TOKEN': csrf ?? '',
				},
				body: formData,
				credentials: 'same-origin',
			});

			return res.json();
		},

		async _postAnalyze(imageBlob) {
			const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
			const formData = new FormData();
			formData.append('image', imageBlob, 'source.png');

			const res = await fetch('/dashboard/user/creative-suite-annotations/analyze', {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'X-CSRF-TOKEN': csrf ?? '',
				},
				body: formData,
				credentials: 'same-origin',
			});

			return res.json();
		},

		/**
	 * Replace-Text tool: send the source image to the configured vision
	 * model, get back text blocks, and drop a numbered comment-style pin at
	 * each block's center pre-filled with the detected text. The user then
	 * edits the texts they want changed and clicks Submit.
	 */
		async analyzeImage() {
			if (this.annotationAnalyzing || this.annotationSubmitting) return;

			const target = this.annotationTargetNode;
			if (!target) {
				toastr.error('No image selected.');
				return;
			}

			const fillSource = target.getAttr('fillSource');
			if (!fillSource) {
				toastr.error('No source image attached to this node.');
				return;
			}

			// Re-analyze guard: if the user has already edited any pins, warn
			// before we throw their work away.
			const pending = this.pendingAnnotation();
			const hasEdits = pending?.tool === 'replace-text'
			&& pending.marks.some(m => this._isReplaceTextEdited(m));
			if (hasEdits && !window.confirm(window?.magicai_localize?.replace_text_reanalyze_confirm
			?? 'Re-analyze will discard your current edits. Continue?')) {
				return;
			}

			this.annotationAnalyzing = true;

			try {
				const naturalImage = await this._loadImage(fillSource);
				const transform = this._stageToImageTransform(target, naturalImage);

				// Build a clean source-image blob (no markers — analyze runs on
				// the original image).
				const canvas = document.createElement('canvas');
				canvas.width = naturalImage.naturalWidth;
				canvas.height = naturalImage.naturalHeight;
				canvas.getContext('2d').drawImage(naturalImage, 0, 0);
				const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));

				const response = await this._postAnalyze(blob);

				if (response.status === 'error' || response.errors) {
					const validation = response.errors
						? Object.values(response.errors).flat().join('\n')
						: null;
					toastr.error(validation || response.message || 'Analyze failed.');
					return;
				}

				const blocks = response.data?.blocks ?? [];

				// Wipe any existing pins (analyze is exclusive — the user picks a
				// fresh detection set every time they click Analyze).
				this._discardAllAnnotations();
				this._clearAnnotationHistory();

				const annotation = this._createAnnotation('replace-text');
				this.pendingAnnotationId = annotation.id;

				for (const block of blocks) {
				// Coordinates come back on a 0..1000 normalised grid (see
				// CreativeSuiteAnnotationsVisionService::VISION_INSTRUCTION).
				// Map to natural-image pixels first, then to stage coords.
					const sx = (block.x + block.width / 2) / 1000 * naturalImage.naturalWidth;
					const sy = (block.y + block.height / 2) / 1000 * naturalImage.naturalHeight;
					const stagePos = this._sourceToStageXY(sx, sy, transform);
					const node = this._createPinNode(stagePos);

					const mark = {
						id: `mark-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
						node,
						prompt: block.text,         // pre-fill textarea with detected text
						originalText: block.text,   // immutable baseline for "did the user edit?"
					};

					this._attachMarkClickHandler(mark, annotation);
					annotation.marks.push(mark);
					this.annotationsLayer.add(node);
				}

				this._renumberPins(annotation);
				this.annotationsLayer.batchDraw();

				if (!blocks.length) {
					toastr.info('No text detected. You can still drop pins manually.');
				}
			} catch (err) {
				console.error(err);
				toastr.error(err?.message ?? 'Analyze failed.');
			} finally {
				this.annotationAnalyzing = false;
			}
		},

		async _waitForOutput(taskId, attempts = 90, interval = 3000) {
			for (let i = 0; i < attempts; i++) {
				await new Promise(r => setTimeout(r, interval));

				const res = await fetch(`/dashboard/user/creative-suite-annotations/edit/${taskId}/status`, {
					headers: { 'Accept': 'application/json' },
					credentials: 'same-origin',
				});
				const json = await res.json();
				const data = json?.data;

				if (data?.status === 'COMPLETED' && data?.output) return data.output;
				if (data?.status === 'FAILED') {
					const reason = data?.payload?.error_message;
					throw new Error(reason ? reason : 'Edit failed.');
				}
			}

			throw new Error('Edit timed out.');
		},
	},
	init(component) {
		// initAnnotations() needs the Konva stage to attach its own layer to,
		// so it must run AFTER the host's initiateStage() finishes. The host
		// calls plugin init hooks early in its own init() (before stage
		// setup), so we either fire immediately (if the stage is already up
		// for some reason) or wait for the stageInitiated flag to flip.
		if (component.stage) {
			component.initAnnotations();
			return;
		}

		const stop = component.$watch('stageInitiated', initiated => {
			if (! initiated) return;
			component.initAnnotations?.();
			stop?.();
		});
	},
};
