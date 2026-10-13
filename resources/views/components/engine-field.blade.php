@props(['engine', 'model' => null, 'index' => 'BM25 index → fuzzy fallback', 'scan' => 'The table scan reads every row, so it is slow on a large table.'])
@php
    $hint = $scan . ($model ? ' Build the index once: php artisan fuzzy-search:rebuild "' . $model . '"' : '');
@endphp
<x-field label="Engine" for="engine" :hint="$hint">
    <select id="engine" name="engine" class="field-control">
        <option value="bm25" @selected($engine === 'bm25')>{{ $index }}</option>
        <option value="like" @selected($engine === 'like')>LIKE table scan (slow on large tables)</option>
    </select>
</x-field>
