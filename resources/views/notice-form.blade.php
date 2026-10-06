@extends('layouts.exam')
 
@section('title', $isNew ? 'New notice' : 'Edit notice')
@section('tab', 'notices')
 
@section('exam-content')
<div class="page-form">
    <div class="subpage-head">
        <a href="/notices/{{$exam->id}}" class="back-link"><i class="bi bi-arrow-left"></i> Back to notices</a>
        <h2>{{$isNew ? 'New notice' : 'Edit notice'}}</h2>
    </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>The notice was not saved.</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form action="/notices" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="exam_id" value="{{$exam->id}}">
            @if(!$isNew)
                <input type="hidden" name="notice_id" value="{{$notice->id}}">
            @endif
            
            <div class="panel">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" id="title" 
                           value="{{ old('title', $notice->title) }}" required maxlength="500">
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <label for="content" class="form-label mb-0">Content</label>
                        <div class="toolbar-group" role="group" aria-label="Formatting toolbar">
                            <button type="button" class="btn btn-quiet btn-sm" id="btn-bold" title="Bold" aria-label="Bold"><i class="bi bi-type-bold"></i></button>
                            <button type="button" class="btn btn-quiet btn-sm" id="btn-italic" title="Italic" aria-label="Italic"><i class="bi bi-type-italic"></i></button>
                        </div>
                    </div>
                    <textarea name="content" class="form-control" id="content" rows="10" 
                              required>{{ old('content', $notice->content) }}</textarea>
                    <div class="form-hint">Select text and use the buttons for <b>bold</b> or <i>italic</i>. Line breaks are kept as you type them.</div>
                </div>
                
                <div class="mb-3">
                    <label for="notice_file" class="form-label">Attachment <span class="optional">(optional)</span></label>
                    @if(!$isNew && $notice->file_name)
                        <div class="mb-2">
                            <a href="/notice-file/{{$notice->id}}" target="_blank" class="file-chip mt-0">
                                <i class="bi bi-paperclip"></i>
                                <span>
                                    {{$notice->file_name}}
                                    <small class="d-block">Current file, {{ number_format($notice->file_size / 1024, 1) }} KB</small>
                                </span>
                            </a>
                        </div>
                    @endif
                    <input type="file" name="notice_file" class="form-control" id="notice_file" 
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                    <div class="form-hint">
                        PDF, JPG, PNG, GIF, DOC or DOCX, up to 5 MB.
                        @if(!$isNew && $notice->file_name)
                            Choosing a new file replaces the current one.
                        @endif
                    </div>
                </div>
                
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" role="switch" id="is_active" name="is_active" value="1"
                           {{ old('is_active', $notice->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Visible to students
                    </label>
                    <div class="form-hint">Turn this off to keep the notice as a hidden draft.</div>
                </div>
            </div>
            
            <div class="form-actions">
                @if($isNew)
                    <button type="submit" name="submit" value="create" class="btn btn-primary">Publish notice</button>
                @else
                    <button type="submit" name="submit" value="update" class="btn btn-primary">Save changes</button>
                @endif
                <a href="/notices/{{$exam->id}}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
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
