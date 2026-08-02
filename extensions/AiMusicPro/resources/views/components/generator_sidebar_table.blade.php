<x-table>
	<x-slot:head>
		<tr>
			<th>
				{{ __('File') }}
			</th>
			<th>
				{{ __('Info') }}
			</th>
			<th>
				{{ __('Play') }}
			</th>
			<th class="text-end">
				{{ __('Action') }}
			</th>
		</tr>
	</x-slot:head>
	<x-slot:body>
		@forelse ($music ?? [] as $song)
			<tr class="text-2xs">
				<td class="flex items-center gap-3">
					<x-button
						class="relative z-10 size-9"
						size="none"
						variant="secondary"
					>
						<x-tabler-music class="size-4" />
					</x-button>
					{{ $song->workbook_title }}
				</td>
				<td>
					<span>{{ $song->created_at->format('M d, Y') }}
{{--						<span class="opacity-60">--}}
{{--							{{$song->duration ?? 0}} {{ __('seconds') }}--}}
{{--						</span>--}}
					</span>
				</td>
				<td
					class="data-audio mt-3 flex items-center"
					data-audio="/uploads/{{ $song->file_path }}"
				>
					<button type="button">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="9"
							height="9"
							viewBox="0 0 24 24"
							stroke-width="1.5"
							stroke="currentColor"
							fill="none"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<path
								stroke="none"
								d="M0 0h24v24H0z"
								fill="none"
							></path>
							<path
								d="M6 4v16a1 1 0 0 0 1.524 .852l13 -8a1 1 0 0 0 0 -1.704l-13 -8a1 1 0 0 0 -1.524 .852z"
								stroke-width="0"
								fill="currentColor"
							></path>
						</svg>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="10"
							height="10"
							viewBox="0 0 24 24"
							stroke-width="1.5"
							stroke="currentColor"
							fill="none"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<path
								stroke="none"
								d="M0 0h24v24H0z"
								fill="none"
							></path>
							<path
								d="M9 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
								stroke-width="0"
								fill="currentColor"
							></path>
							<path
								d="M17 4h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h2a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2z"
								stroke-width="0"
								fill="currentColor"
							></path>
						</svg>
					</button>
					<div class="audio-preview grow"></div>
					<span>0:00</span>
				</td>
				<td class="whitespace-nowrap text-end">
					<x-button
						class="relative z-10 size-9"
						size="none"
						variant="ghost-shadow"
						hover-variant="primary"
						href="/uploads/{{ $song->file_path }}"
						download="{{ $song->file_path }}"
						target="_blank"
						title="{{ __('Download') }}"
					>
						<x-tabler-download class="size-4" />
					</x-button>
					<x-button
						class="relative z-10 size-9"
						size="none"
						variant="danger"
						href="{{  (route('dashboard.user.ai-music-pro.delete', $song->id)) }}"
						onclick="return confirm('Are you sure?')"
						title="{{ __('Delete') }}"
					>
						<x-tabler-x class="size-4" />
					</x-button>
				</td>
			</tr>
		@empty
			<tr>
				<td colspan="6">{{ __('No entries created yet.') }}</td>
			</tr>
		@endforelse

	</x-slot:body>

</x-table>

<div class="float-right m-4">
	{{ $music->links('pagination::bootstrap-5-alt') }}
</div>


<svg
	class="hidden"
	width="13"
	height="16"
	viewBox="0 0 13 16"
	fill="none"
	xmlns="http://www.w3.org/2000/svg"
>
	<path
		id="music-icon"
		d="M12.0065 1.08453C11.9013 1.00353 11.7789 0.948008 11.6487 0.922314C11.5185 0.89662 11.3841 0.901455 11.2561 0.93644L4.63803 2.71546C4.4084 2.77714 4.20577 2.91338 4.06201 3.10276C3.91824 3.29215 3.84149 3.52394 3.84381 3.76169V11.2656C3.84381 11.4894 3.69905 11.6826 3.47492 11.758L3.47094 11.7597L1.74438 12.3573C1.08397 12.5781 0.656312 13.1914 0.656312 13.9189C0.653893 14.2148 0.722713 14.5069 0.856959 14.7706C0.991205 15.0343 1.18694 15.2618 1.42762 15.4339C1.73756 15.6591 2.11068 15.7806 2.49377 15.7812C2.69419 15.7809 2.89323 15.748 3.08313 15.6839L3.09575 15.6796L3.8209 15.416C4.13546 15.311 4.40927 15.1101 4.60392 14.8417C4.79858 14.5732 4.90431 14.2505 4.90631 13.9189V6.8798C4.90631 6.63775 5.06469 6.44119 5.31006 6.37943L5.31703 6.37744L11.1153 4.82884C11.1349 4.82378 11.1554 4.82327 11.1752 4.82734C11.195 4.8314 11.2136 4.83994 11.2297 4.85231C11.2457 4.86467 11.2587 4.88053 11.2676 4.89868C11.2766 4.91682 11.2812 4.93677 11.2813 4.95701V9.66953C11.2813 9.89365 11.1402 10.0806 10.9124 10.1569L10.9041 10.1599L9.21442 10.7612C8.88582 10.8693 8.60012 11.0791 8.39867 11.3603C8.19722 11.6415 8.09045 11.9795 8.09381 12.3254C8.09085 12.6222 8.15939 12.9154 8.29364 13.18C8.42789 13.4447 8.62391 13.6732 8.86512 13.8461C9.10128 14.0169 9.37508 14.1282 9.66337 14.1707C9.95165 14.2131 10.2459 14.1855 10.5213 14.0902L10.5332 14.0862L11.2584 13.8222C11.5729 13.7173 11.8467 13.5165 12.0414 13.2481C12.236 12.9797 12.3418 12.657 12.3438 12.3254V1.76951C12.3445 1.63699 12.3144 1.50613 12.2559 1.38725C12.1973 1.26837 12.112 1.16473 12.0065 1.08453Z"
	/>
</svg>
