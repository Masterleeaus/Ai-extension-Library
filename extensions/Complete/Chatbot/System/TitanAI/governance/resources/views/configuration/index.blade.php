@extends('panel.layout.app')
@section('title', 'AI Governance Configuration')
@section('content')
<div class="container-fluid py-6"><h1>AI Governance Configuration</h1><p>Tenant-scoped memory, personas, skills, tool permissions and evaluation results.</p>
<div class="grid gap-4 md:grid-cols-2">
<section class="card p-4"><h2>Active personas</h2><ul>@foreach($personas as $item)<li>{{ $item->name }} — {{ $item->context_type }}</li>@endforeach</ul></section>
<section class="card p-4"><h2>Governed skills</h2><ul>@foreach($skills as $item)<li>{{ $item->name }} — {{ $item->active ? 'active' : 'disabled' }}</li>@endforeach</ul></section>
<section class="card p-4"><h2>Tool permission matrix</h2><ul>@foreach($permissions as $item)<li>{{ $item->subject_type }}:{{ $item->subject_id }} → {{ $item->tool_name }} = {{ $item->allowed ? 'allow' : 'deny' }}</li>@endforeach</ul></section>
<section class="card p-4"><h2>Memory lifecycle</h2><ul>@foreach($memories as $item)<li>{{ $item->scope }} — {{ $item->status }} — reinforcement {{ $item->reinforcement_count }}</li>@endforeach</ul></section>
</div></div>
@endsection
