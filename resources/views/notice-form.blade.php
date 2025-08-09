@extends('layouts.master')
 
@section('title', $isNew ? 'Create Notice' : 'Edit Notice')
 
@section('content')
<div class="row">
    <div class="col-md-8">
        <h2>{{$isNew ? 'Create New Notice' : 'Edit Notice'}} for {{$exam->exam_name}}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form action="/notices" method="POST">
            @csrf
            <input type="hidden" name="exam_id" value="{{$exam->id}}">
            @if(!$isNew)
                <input type="hidden" name="notice_id" value="{{$notice->id}}">
            @endif
            
            <div class="form-group">
                <label for="title">Notice Title:</label>
                <input type="text" name="title" class="form-control" id="title" 
                       placeholder="Enter notice title" value="{{ old('title', $notice->title) }}" required maxlength="500">
                <small class="form-text text-muted">Maximum 500 characters</small>
            </div>
            
            <div class="form-group">
                <label for="content">Notice Content:</label>
                <div class="mb-2 btn-group" role="group" aria-label="Formatting toolbar">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-bold"><strong>B</strong></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-italic"><em>I</em></button>
                </div>
                <textarea name="content" class="form-control" id="content" rows="10" 
                          placeholder="Enter notice content" required>{{ old('content', $notice->content) }}</textarea>
                <small class="form-text text-muted">Use the toolbar to add <b>bold</b> or <i>italic</i>. Selected text will be wrapped. Allowed tags: &lt;b&gt;, &lt;i&gt;, &lt;br&gt;.</small>
            </div>
            
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $notice->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Active (visible to students)
                </label>
            </div>
            
            <div class="form-group">
                @if($isNew)
                    <button type="submit" name="submit" value="create" class="btn btn-success">Create Notice</button>
                @else
                    <button type="submit" name="submit" value="update" class="btn btn-primary">Update Notice</button>
                @endif
                <a href="/notices/{{$exam->id}}" class="btn btn-secondary ml-2">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Exam Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong><br><span class="text-muted">{{$exam->exam_name}}</span></p>
                <p><strong>Department:</strong><br><span class="text-muted">{{$exam->department}}</span></p>
                <p><strong>Series:</strong><br><span class="text-muted">{{$exam->series}}</span></p>
                <p><strong>Deadline:</strong><br><span class="text-muted">{{$exam->deadline}}</span></p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Notice Guidelines</h5>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li>Keep the title concise and descriptive</li>
                    <li>Use clear and simple language in the content</li>
                    <li>Include important dates and deadlines</li>
                    <li>Uncheck "Active" to hide the notice from students</li>
                    <li>Line breaks will be preserved when displayed</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    function wrapSelection(textarea, before, after){
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const value = textarea.value;
        const selected = value.substring(start, end);
        if(!selected){
            // No selection: insert at cursor and place cursor in between
            const insert = before + after;
            textarea.value = value.slice(0, start) + insert + value.slice(end);
            const cursor = start + before.length;
            textarea.setSelectionRange(cursor, cursor);
        } else {
            // If already wrapped, toggle off
            const hasWrap = selected.startsWith(before) && selected.endsWith(after);
            let replacement;
            if(hasWrap){
                replacement = selected.slice(before.length, selected.length - after.length);
            } else {
                replacement = before + selected + after;
            }
            textarea.value = value.slice(0, start) + replacement + value.slice(end);
            const newEnd = start + replacement.length;
            textarea.setSelectionRange(start, newEnd);
        }
        textarea.focus();
    }
    const contentEl = document.getElementById('content');
    const boldBtn = document.getElementById('btn-bold');
    const italicBtn = document.getElementById('btn-italic');
    if(!contentEl || !boldBtn || !italicBtn) return;
    boldBtn.addEventListener('click', function(){
        wrapSelection(contentEl, '<b>', '</b>');
    });
    italicBtn.addEventListener('click', function(){
        wrapSelection(contentEl, '<i>', '</i>');
    });
});
</script>
@endsection
