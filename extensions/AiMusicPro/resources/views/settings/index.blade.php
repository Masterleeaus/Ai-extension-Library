@extends('panel.layout.settings')
@section('title', __('AI Music Pro Settings'))
@section('titlebar_actions', '')

@section('settings')
	<form
		method="post"
		enctype="multipart/form-data"
		action=""
	>
		@csrf
		<x-form-step
			auto-increment
			label="{{ __('AI Music Pro Provider') }}"
		>
		</x-form-step>
		<x-card
			class="mb-2 max-md:text-center"
			size="lg"
		>
			<div class="space-y-4">
				<x-forms.input
					id="ai_music_pro_default_provider"
					name="ai_music_pro_default_provider"
					type="select"
					size="lg"
					label="{{ __('Default Music Provider') }}"
					tooltip="{{ __('Select the default AI provider for music generation.') }}"
				>
					<option
						value="elevenlabs"
						@selected(setting('ai_music_pro_default_provider', 'elevenlabs') === 'elevenlabs')
					>
						{{ __('ElevenLabs') }}
					</option>
					<option
						value="lyria3clip"
						@selected(setting('ai_music_pro_default_provider', 'elevenlabs') === 'lyria3clip')
					>
						{{ __('Google Lyria 3 Clip (~30s clips)') }}
					</option>
					<option
						value="lyria3pro"
						@selected(setting('ai_music_pro_default_provider', 'elevenlabs') === 'lyria3pro')
					>
						{{ __('Google Lyria 3 Pro (full songs ~2min)') }}
					</option>
				</x-forms.input>

				<x-alert variant="info">
					<ul class="list-disc ps-4 text-xs">
						<li>
							<strong>{{ __('ElevenLabs') }}:</strong>
							{{ __('Uses ElevenLabs music_v1 model. Supports custom duration (10-300s) and music styles.') }}
						</li>
						<li>
							<strong>{{ __('Google Lyria 3 Clip') }}:</strong>
							{{ __('Generates ~30 second music clips via Gemini API. Best for short loops and previews.') }}
						</li>
						<li>
							<strong>{{ __('Google Lyria 3 Pro') }}:</strong>
							{{ __('Generates full-length songs (~2 minutes) with verses, choruses, and bridges via Gemini API.') }}
						</li>
					</ul>
				</x-alert>
			</div>
		</x-card>

		<x-button
			class="w-full"
			size="lg"
			type="submit"
		>
			{{ __('Save') }}
		</x-button>
	</form>
@endsection
