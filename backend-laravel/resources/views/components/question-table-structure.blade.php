@if(!empty($rows))
    <table class="w-full border-collapse text-xs">
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach(($row['cells'] ?? []) as $cell)
                        <td class="min-w-[5rem] border border-slate-300 p-2 align-top">
                            @foreach(($cell['content'] ?? []) as $item)
                                @if(($item['type'] ?? null) === 'paragraph')
                                    <p dir="{{ in_array($item['direction'] ?? 'auto', ['rtl', 'ltr'], true) ? $item['direction'] : 'auto' }}" class="whitespace-pre-wrap leading-5">{{ $item['text'] ?? '' }}</p>
                                @elseif(($item['type'] ?? null) === 'table')
                                    <div class="my-2 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-1">
                                        @include('components.question-table-structure', ['rows' => $item['rows'] ?? []])
                                    </div>
                                @endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
