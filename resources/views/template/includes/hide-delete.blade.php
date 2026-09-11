@if (! config('ams.show_delete_buttons', false))
    <style>
        /* Hide record delete forms (POST + _method delete) */
        form:has(input[name="_method"][value="delete"]) {
            display: none !important;
        }

        /* Users module delete trigger + modal */
        button.action-btn.btn-outline-danger[data-bs-target="#modal-notification"],
        #modal-notification {
            display: none !important;
        }
    </style>
@endif
