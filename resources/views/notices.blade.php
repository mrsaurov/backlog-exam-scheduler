@extends('layouts.master')
 
@section('title', 'Notices')
 
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Notices for {{$exam->exam_name}}</h2>
    <a href="/notices/{{$exam->id}}/create" class="btn btn-success">Add New Notice</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Exam Information</h5>
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-2">
                        <strong>Department:</strong><br>
                        <span class="text-muted">{{$exam->department}}</span>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6 mb-2">
                        <strong>Series:</strong><br>
                        <span class="text-muted">{{$exam->series}}</span>
                    </div>
                    <div class="col-lg-3 col-md-3 col-6 mb-2">
                        <strong>Deadline:</strong><br>
                        <span class="text-muted">{{$exam->deadline}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(count($notices) > 0)
    <div class="row">
        @foreach($notices as $notice)
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">{{$notice->title}}</h5>
                    <div>
                        @if($notice->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                        <a href="/notices/{{$exam->id}}/edit/{{$notice->id}}" class="btn btn-sm btn-primary ml-2">Edit</a>
                        <form action="/notices" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notice?')">
                            @csrf
                            <input type="hidden" name="notice_id" value="{{$notice->id}}">
                            <input type="hidden" name="exam_id" value="{{$exam->id}}">
                            <button type="submit" name="submit" value="delete" class="btn btn-sm btn-danger ml-1">Delete</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">{!! nl2br(strip_tags($notice->content, '<b><i><br>')) !!}</p>
                    <small class="text-muted">Created: {{$notice->created_at->format('d M Y, h:i A')}}</small>
                    @if($notice->updated_at != $notice->created_at)
                        <small class="text-muted"> | Updated: {{$notice->updated_at->format('d M Y, h:i A')}}</small>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="alert alert-info">
        <h4>No notices found</h4>
        <p>No notices have been created for this exam yet. <a href="/notices/{{$exam->id}}/create">Create the first notice</a>.</p>
    </div>
@endif

<div class="mt-4">
    <a href="/admin" class="btn btn-secondary">Back to Admin Panel</a>
</div>

@endsection
