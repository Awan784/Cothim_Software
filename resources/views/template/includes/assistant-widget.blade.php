@if (auth()->check())
    <div id="ams-assistant"
        data-chat-url="{{ route('assistant.chat') }}"
        data-confirm-url="{{ route('assistant.confirm') }}"
        data-cancel-url="{{ route('assistant.cancel') }}"
        data-csrf="{{ csrf_token() }}"
        data-user-id="{{ auth()->id() }}"
        data-pending="{{ json_encode(session('assistant.pending'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">
        <button type="button" class="ams-assistant-toggle" id="ams-assistant-toggle" aria-label="Open assistant">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z"/>
            </svg>
        </button>
        <div class="ams-assistant-panel" id="ams-assistant-panel" hidden>
            <div class="ams-assistant-head">
                <div>
                    <strong>Accounts Assistant</strong>
                    <div class="small opacity-75">Draft an entry, then confirm to save</div>
                </div>
                <button type="button" class="btn-close" id="ams-assistant-close" aria-label="Close"></button>
            </div>
            <div class="ams-assistant-messages" id="ams-assistant-messages">
                <div class="ams-assistant-bubble bot">
                    Try: <em>receive 50000 cash from customer Ali</em>, <em>add investor Sara</em>, <em>add bank Meezan</em>, <em>stock in 10 of Chair</em>, or <em>balances</em>. Confirm before anything is saved.
                </div>
            </div>
            <div class="ams-assistant-pending" id="ams-assistant-pending" hidden>
                <div class="ams-assistant-pending-text" id="ams-assistant-pending-text"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success" id="ams-assistant-confirm">Confirm</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ams-assistant-cancel">Cancel</button>
                </div>
            </div>
            <form class="ams-assistant-form" id="ams-assistant-form">
                <input type="text" class="form-control form-control-sm" id="ams-assistant-input" autocomplete="off" placeholder="Type a command…">
                <button type="submit" class="btn btn-sm btn-primary">Send</button>
            </form>
        </div>
    </div>
@endif
