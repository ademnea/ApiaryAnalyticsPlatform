@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Farmers</h1>
        <a href="{{ route('admin.farmers.create') }}" class="btn btn-primary">Register New Farmer</a>
    </div>

    <table class="table table-striped">
        <thead><tr><th>Name</th><th>Phone</th><th>Country</th><th>Status</th><th>Apiaries</th></tr></thead>
        <tbody>
            @foreach($farmers as $farmer)
                <tr>
                    <td><a href="{{ route('admin.farmers.show', $farmer) }}">{{ $farmer->full_name }}</a></td>
                    <td>{{ $farmer->phone }}</td>
                    <td>{{ $farmer->country }}</td>
                    <td><span class="badge bg-{{ $farmer->status === 'Active' ? 'success' : 'secondary' }}">{{ ucfirst($farmer->status) }}</span></td>
                    <td>{{ $farmer->apiaries->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $farmers->links() }}
</div>
@endsection
